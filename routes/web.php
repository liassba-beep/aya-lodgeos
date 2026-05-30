<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPropertyController;
use App\Models\DailyChecklist;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceReport;
use App\Models\OperationalTask;
use App\Models\Payment;
use App\Models\ProductRequisition;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StaffMember;
use App\Models\StockItem;
use App\Models\UtilityReading;
use App\Models\OwnerDailyReport;
use App\Models\OperationalAlert;
use App\Models\Receipt;
use App\Models\User;
use App\Support\ReservationAvailability;
use App\Support\SimplePdf;
use App\Support\TenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', PublicPropertyController::class)
    ->domain('{tenant}.lodgesos.com')
    ->where('tenant', '^(?!app$|www$|admin$)[a-z0-9-]+$')
    ->name('public.property.subdomain');

Route::get('/p/{tenant}', PublicPropertyController::class)
    ->where('tenant', '[a-z0-9-]+')
    ->name('public.property.preview');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/trabalhador', fn () => redirect()->route('worker.mobile'));

Route::get('/trabalhador/login', function () {
    return Inertia::render('Worker/Login');
})->name('worker.login');

Route::post('/trabalhador/login', function (Request $request) {
    $validated = $request->validate([
        'phone' => ['required', 'string'],
        'pin' => ['required', 'string'],
    ]);

    $phone = trim($validated['phone']);
    $pin = $validated['pin'];

    $staff = StaffMember::query()
        ->where('phone', $phone)
        ->where('status', 'active')
        ->where('mobile_access_enabled', true)
        ->first();

    if ($staff && $staff->mobile_pin_hash && Hash::check($pin, $staff->mobile_pin_hash)) {
        session(['worker_staff_member_id' => $staff->id]);
        $staff->forceFill(['last_mobile_login_at' => now()])->save();

        return redirect()->route('worker.mobile');
    }

    $user = User::query()
        ->where('phone', $phone)
        ->where('mobile_access_enabled', true)
        ->whereIn('role', ['staff', 'security'])
        ->first();

    if (! $user || ! $user->property_id || ! $user->mobile_pin_hash || ! Hash::check($pin, $user->mobile_pin_hash)) {
        return back()->withErrors(['phone' => 'Telemóvel ou PIN inválido.'])->onlyInput('phone');
    }

    $staff = StaffMember::query()->firstOrCreate(
        ['phone' => $phone, 'property_id' => $user->property_id],
        [
            'name' => $user->name,
            'role' => $user->role === 'security' ? 'security' : 'staff',
            'status' => 'active',
            'mobile_access_enabled' => true,
            'mobile_pin_hash' => $user->mobile_pin_hash,
        ],
    );

    $staff->forceFill([
        'name' => $staff->name ?: $user->name,
        'status' => 'active',
        'mobile_access_enabled' => true,
        'mobile_pin_hash' => $staff->mobile_pin_hash ?: $user->mobile_pin_hash,
        'last_mobile_login_at' => now(),
    ])->save();

    session(['worker_staff_member_id' => $staff->id]);

    return redirect()->route('worker.mobile');
})->name('worker.login.store');

Route::post('/trabalhador/logout', function () {
    session()->forget('worker_staff_member_id');

    return redirect()->route('worker.login');
})->name('worker.logout');

Route::get('/trabalhador/app', function () {
    $staff = StaffMember::query()->with('property')->find(session('worker_staff_member_id'));

    if (! $staff) {
        return redirect()->route('worker.login');
    }

    $today = Carbon::today();
    $propertyId = $staff->property_id;

    $tasks = OperationalTask::query()
        ->with('room')
        ->where('property_id', $propertyId)
        ->whereIn('status', ['pending', 'in_progress'])
        ->where(fn ($query) => $query->whereNull('staff_member_id')->orWhere('staff_member_id', $staff->id))
        ->orderBy('due_date')
        ->limit(8)
        ->get()
        ->map(fn (OperationalTask $task): array => [
            'id' => $task->id,
            'title' => $task->title,
            'type' => $task->type,
            'room' => $task->room?->name,
            'priority' => $task->priority,
            'status' => $task->status,
            'date' => $task->due_date?->format('d/m/Y'),
        ]);

    $checklists = DailyChecklist::query()
        ->where('property_id', $propertyId)
        ->whereDate('checklist_date', $today)
        ->where(fn ($query) => $query->whereNull('staff_member_id')->orWhere('staff_member_id', $staff->id))
        ->orderBy('area')
        ->limit(8)
        ->get()
        ->map(fn (DailyChecklist $checklist): array => [
            'id' => $checklist->id,
            'title' => $checklist->title,
            'area' => $checklist->area,
            'status' => $checklist->status,
        ]);

    $reservations = Reservation::query()
        ->with(['guest', 'room'])
        ->where('property_id', $propertyId)
        ->where('status', '!=', 'cancelled')
        ->whereDate('check_in', '<=', $today)
        ->whereDate('check_out', '>=', $today)
        ->orderBy('check_in')
        ->limit(10)
        ->get()
        ->map(fn (Reservation $reservation): array => [
            'id' => $reservation->id,
            'code' => $reservation->code,
            'guest' => $reservation->guest?->full_name,
            'room' => $reservation->room?->name,
            'status' => $reservation->status,
            'check_in' => $reservation->check_in?->format('d/m/Y'),
            'check_out' => $reservation->check_out?->format('d/m/Y'),
        ]);

    return Inertia::render('Worker/App', [
        'staff' => [
            'name' => $staff->name,
            'role' => $staff->role,
            'property' => $staff->property?->name,
            'checked_in' => filled($staff->checked_in_at) && blank($staff->checked_out_at?->greaterThan($staff->checked_in_at) ? $staff->checked_out_at : null),
            'checked_in_at' => $staff->checked_in_at?->format('d/m/Y H:i'),
        ],
        'tasks' => $tasks,
        'checklists' => $checklists,
        'reservations' => $reservations,
        'stockItems' => StockItem::query()
            ->where('property_id', $propertyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->limit(40)
            ->get(['id', 'name', 'unit'])
            ->map(fn (StockItem $item): array => ['id' => $item->id, 'name' => $item->name, 'unit' => $item->unit]),
        'rooms' => Room::query()
            ->where('property_id', $propertyId)
            ->orderBy('room_number')
            ->get(['id', 'name', 'room_number'])
            ->map(fn (Room $room): array => ['id' => $room->id, 'name' => trim(($room->room_number ? $room->room_number.' - ' : '').$room->name)]),
    ]);
})->name('worker.mobile');

Route::middleware([])->prefix('trabalhador')->name('worker.')->group(function () {
    Route::post('/check-in', function (Request $request) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        $validated = $request->validate(['photo' => ['required', 'image', 'max:5120']]);

        $staff->forceFill([
            'checkin_photo_path' => $request->file('photo')->store('staff-checkins', 'public'),
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ])->save();

        return back();
    })->name('check-in');

    Route::post('/check-out', function () {
        StaffMember::findOrFail(session('worker_staff_member_id'))->forceFill(['checked_out_at' => now()])->save();

        return back();
    })->name('check-out');

    Route::post('/tarefas/{task}/concluir', function (Request $request, OperationalTask $task) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        abort_unless((int) $task->property_id === (int) $staff->property_id, 403);

        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
            'qr_code' => ['nullable', 'string', 'max:255'],
        ]);

        $task->forceFill([
            'status' => 'done',
            'completed_at' => now(),
            'completed_by_staff_member_id' => $staff->id,
            'evidence_photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('task-evidence', 'public') : $task->evidence_photo_path,
            'evidence_qr_code' => $validated['qr_code'] ?? null,
        ])->save();

        OperationalAlert::create([
            'property_id' => $task->property_id,
            'source_type' => OperationalTask::class,
            'source_id' => $task->id,
            'severity' => 'info',
            'title' => 'Tarefa concluída na app mobile',
            'message' => $task->title.' por '.$staff->name,
            'status' => 'open',
        ]);

        return back();
    })->name('tasks.complete');

    Route::post('/checklists/{dailyChecklist}/concluir', function (Request $request, DailyChecklist $dailyChecklist) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        abort_unless((int) $dailyChecklist->property_id === (int) $staff->property_id, 403);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'qr_code' => ['nullable', 'string', 'max:255'],
        ]);

        $dailyChecklist->forceFill([
            'status' => 'done',
            'completed_at' => now(),
            'evidence_photo_path' => $request->file('photo')->store('checklist-evidence', 'public'),
            'evidence_latitude' => $validated['latitude'] ?? null,
            'evidence_longitude' => $validated['longitude'] ?? null,
            'evidence_qr_code' => $validated['qr_code'] ?? null,
        ])->save();

        OperationalAlert::create([
            'property_id' => $dailyChecklist->property_id,
            'source_type' => DailyChecklist::class,
            'source_id' => $dailyChecklist->id,
            'severity' => 'info',
            'title' => 'Checklist concluída na app mobile',
            'message' => $dailyChecklist->title.' por '.$staff->name,
            'status' => 'open',
        ]);

        return back();
    })->name('checklists.complete');

    Route::post('/avarias', function (Request $request) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        $validated = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'string', 'max:30'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);
        unset($validated['photo']);

        MaintenanceReport::create([
            ...$validated,
            'property_id' => $staff->property_id,
            'staff_member_id' => $staff->id,
            'photo_path' => $request->file('photo')->store('maintenance-reports', 'public'),
        ]);

        return back();
    })->name('reports.store');

    Route::post('/credelec', function (Request $request) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        $validated = $request->validate([
            'meter_number' => ['nullable', 'string', 'max:255'],
            'balance_kwh' => ['nullable', 'numeric'],
            'balance_amount' => ['nullable', 'numeric'],
            'qr_code' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'photo' => ['required', 'image', 'max:5120'],
        ]);
        unset($validated['photo']);

        UtilityReading::create([
            ...$validated,
            'property_id' => $staff->property_id,
            'staff_member_id' => $staff->id,
            'reading_date' => now(),
            'photo_path' => $request->file('photo')->store('utility-readings', 'public'),
        ]);

        return back();
    })->name('utility.store');

    Route::post('/requisicoes', function (Request $request) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        $validated = $request->validate([
            'stock_item_id' => ['required', 'exists:stock_items,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ]);

        ProductRequisition::create([
            ...$validated,
            'property_id' => $staff->property_id,
            'staff_member_id' => $staff->id,
            'needed_at' => now(),
        ]);

        return back();
    })->name('requisitions.store');

    Route::post('/reservas/{reservation}/check-in', function (Request $request, Reservation $reservation) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        abort_unless((int) $reservation->property_id === (int) $staff->property_id, 403);

        $request->validate(['photo' => ['nullable', 'image', 'max:5120']]);

        $reservation->forceFill([
            'status' => 'checked_in',
            'mobile_checkin_photo_path' => $request->hasFile('photo') ? $request->file('photo')->store('guest-checkins', 'public') : $reservation->mobile_checkin_photo_path,
            'mobile_checked_in_by' => $staff->id,
            'mobile_checked_in_at' => now(),
        ])->save();

        OperationalAlert::create([
            'property_id' => $reservation->property_id,
            'source_type' => Reservation::class,
            'source_id' => $reservation->id,
            'severity' => 'info',
            'title' => 'Check-in de hóspede pela app mobile',
            'message' => $reservation->code.' por '.$staff->name,
            'status' => 'open',
        ]);

        return back();
    })->name('reservations.check-in');

    Route::post('/reservas/{reservation}/check-out', function (Reservation $reservation) {
        $staff = StaffMember::findOrFail(session('worker_staff_member_id'));
        abort_unless((int) $reservation->property_id === (int) $staff->property_id, 403);

        $reservation->forceFill([
            'status' => 'checked_out',
            'mobile_checked_out_by' => $staff->id,
            'mobile_checked_out_at' => now(),
        ])->save();

        OperationalAlert::create([
            'property_id' => $reservation->property_id,
            'source_type' => Reservation::class,
            'source_id' => $reservation->id,
            'severity' => 'info',
            'title' => 'Check-out de hóspede pela app mobile',
            'message' => $reservation->code.' por '.$staff->name,
            'status' => 'open',
        ]);

        return back();
    })->name('reservations.check-out');
});

Route::get('/mobile', function () {
    $today = Carbon::today();
    $propertyId = TenantContext::propertyId();

    $reservationsToday = Reservation::query()
        ->with(['guest', 'room'])
        ->where('property_id', $propertyId)
        ->where('status', '!=', 'cancelled')
        ->whereDate('check_in', '<=', $today)
        ->whereDate('check_out', '>=', $today)
        ->orderBy('check_in')
        ->limit(6)
        ->get()
        ->map(fn (Reservation $reservation): array => [
            'code' => $reservation->code,
            'guest' => $reservation->guest?->full_name,
            'room' => $reservation->room?->name,
            'check_in' => $reservation->check_in?->format('d/m/Y'),
            'check_out' => $reservation->check_out?->format('d/m/Y'),
            'status' => $reservation->status,
            'total' => number_format((float) $reservation->total_amount, 2).' MZN',
        ]);

    $tasks = OperationalTask::query()
        ->with(['room', 'staffMember'])
        ->where('property_id', $propertyId)
        ->whereIn('status', ['pending', 'in_progress'])
        ->orderBy('due_date')
        ->limit(6)
        ->get()
        ->map(fn (OperationalTask $task): array => [
            'title' => $task->title,
            'room' => $task->room?->name,
            'staff' => $task->staffMember?->name,
            'date' => $task->due_date?->format('d/m/Y'),
            'priority' => $task->priority,
            'status' => $task->status,
        ]);

    $checklists = DailyChecklist::query()
        ->with('staffMember')
        ->where('property_id', $propertyId)
        ->whereDate('checklist_date', $today)
        ->orderBy('area')
        ->limit(6)
        ->get()
        ->map(fn (DailyChecklist $checklist): array => [
            'id' => $checklist->id,
            'title' => $checklist->title,
            'area' => $checklist->area,
            'staff' => $checklist->staffMember?->name,
            'status' => $checklist->status,
            'has_evidence' => filled($checklist->evidence_photo_path) || filled($checklist->evidence_qr_code),
        ]);

    $lowStock = StockItem::query()
        ->where('property_id', $propertyId)
        ->whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
        ->orderBy('name')
        ->limit(6)
        ->get()
        ->map(fn (StockItem $item): array => [
            'name' => $item->name,
            'quantity' => (float) $item->quantity_on_hand,
            'minimum' => (float) $item->minimum_quantity,
            'unit' => $item->unit,
        ]);

    $occupiedRooms = Reservation::query()
        ->where('property_id', $propertyId)
        ->where('status', '!=', 'cancelled')
        ->whereDate('check_in', '<=', $today)
        ->whereDate('check_out', '>', $today)
        ->distinct('room_id')
        ->count('room_id');

    $totalRooms = Room::query()->where('property_id', $propertyId)->count();

    return Inertia::render('Mobile/App', [
        'summary' => [
            'date' => $today->format('d/m/Y'),
            'occupancy' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0,
            'rooms' => $occupiedRooms.' / '.$totalRooms,
            'revenue_today' => number_format((float) Payment::query()
                ->whereHas('reservation', fn ($query) => $query->where('property_id', $propertyId))
                ->where('status', 'paid')
                ->whereDate('paid_at', $today)
                ->sum('amount'), 2).' MZN',
            'expenses_month' => number_format((float) Expense::query()
                ->where('property_id', $propertyId)
                ->whereBetween('expense_date', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('amount'), 2).' MZN',
            'pending_tasks' => OperationalTask::query()
                ->where('property_id', $propertyId)
                ->whereIn('status', ['pending', 'in_progress'])
                ->count(),
            'low_stock' => StockItem::query()
                ->where('property_id', $propertyId)
                ->whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
                ->count(),
        ],
        'reservations' => $reservationsToday,
        'tasks' => $tasks,
        'checklists' => $checklists,
        'lowStock' => $lowStock,
    ]);
})->middleware(['auth', 'verified'])->name('mobile');

Route::middleware('auth')->group(function () {
    Route::get('/invoices/{invoice}/pdf', function (Invoice $invoice) {
        $propertyId = TenantContext::propertyId();

        abort_unless(! $propertyId || (int) $invoice->property_id === $propertyId, 403);

        $invoice->load(['property', 'reservation.guest', 'reservation.room']);

        $reservation = $invoice->reservation;
        $paidAmount = (float) $invoice->paid_amount;
        $balanceAmount = (float) $invoice->balance_amount;

        $property = $invoice->property;
        $guest = $reservation?->guest;

        $pdf = SimplePdf::invoice([
            'number' => $invoice->number,
            'issued_at' => $invoice->issued_at?->format('d/m/Y') ?? '-',
            'due_at' => $invoice->due_at?->format('d/m/Y') ?? '-',
            'logo_path' => $property?->invoice_logo_path,
            'issuer_name' => $property?->legal_name ?: ($property?->name ?? 'AYA LodgeOS'),
            'issuer_nuit' => $property?->nuit ?: '-',
            'issuer_address' => trim(collect([$property?->address, $property?->city, $property?->country])->filter()->implode(', ')) ?: '-',
            'issuer_contacts' => trim(collect([$property?->invoice_phone ?: $property?->phone, $property?->invoice_email ?: $property?->email])->filter()->implode(' | ')) ?: '-',
            'client_name' => $guest?->full_name ?: '-',
            'client_nuit' => $guest?->nuit ?: '-',
            'client_contact' => $guest?->phone ?: '-',
            'reservation_code' => $reservation?->code ?? '-',
            'room' => $reservation?->room?->name ?? '-',
            'stay_dates' => trim(($reservation?->check_in?->format('d/m/Y') ?? '-').' a '.($reservation?->check_out?->format('d/m/Y') ?? '-')),
            'subtotal' => (float) $invoice->subtotal,
            'discount' => (float) $invoice->discount_amount,
            'tax_rate' => (float) $invoice->tax_rate,
            'tax' => (float) $invoice->tax_amount,
            'total' => (float) $invoice->total_amount,
            'paid' => $paidAmount,
            'balance' => $balanceAmount,
            'status' => $invoice->status,
            'notes' => $invoice->notes ?: '',
            'footer' => $property?->invoice_footer ?: 'Obrigado pela preferência. Valores expressos em Meticais.',
        ]);

        $filename = str($invoice->number)->replaceMatches('/[^A-Za-z0-9_-]/', '-')->lower();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="factura-'.$filename.'.pdf"',
        ]);
    })->name('invoices.pdf');

    Route::get('/admin/exports/invoices.csv', function () {
        $propertyId = TenantContext::propertyId();
        $rows = Invoice::query()
            ->with('reservation.guest')
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderByDesc('issued_at')
            ->get();

        $csv = "Número,Data,Cliente,Subtotal,Desconto,IVA,Total,Estado\n";

        foreach ($rows as $invoice) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', [
                $invoice->number,
                $invoice->issued_at?->format('Y-m-d'),
                $invoice->reservation?->guest?->full_name,
                $invoice->subtotal,
                $invoice->discount_amount,
                $invoice->tax_amount,
                $invoice->total_amount,
                $invoice->status,
            ]))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="facturas.csv"',
        ]);
    })->name('exports.invoices');

    Route::get('/admin/exports/reservations.csv', function () {
        $propertyId = TenantContext::propertyId();
        $rows = Reservation::query()
            ->with(['guest', 'room'])
            ->when($propertyId, fn ($query) => $query->where('property_id', $propertyId))
            ->orderByDesc('check_in')
            ->get();

        $csv = "Código,Hóspede,Quarto,Entrada,Saída,Total,Estado\n";

        foreach ($rows as $reservation) {
            $csv .= implode(',', array_map(fn ($value) => '"'.str_replace('"', '""', (string) $value).'"', [
                $reservation->code,
                $reservation->guest?->full_name,
                $reservation->room?->name,
                $reservation->check_in?->format('Y-m-d'),
                $reservation->check_out?->format('Y-m-d'),
                $reservation->total_amount,
                $reservation->status,
            ]))."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reservas.csv"',
        ]);
    })->name('exports.reservations');

    Route::post('/admin/reservations/{reservation}/move', function (Request $request, Reservation $reservation) {
        $propertyId = TenantContext::propertyId();
        abort_unless(! $propertyId || (int) $reservation->property_id === $propertyId, 403);

        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'check_in' => ['required', 'date'],
        ]);

        $nights = max(1, $reservation->check_in?->diffInDays($reservation->check_out) ?? 1);
        $reservation->room_id = (int) $validated['room_id'];
        $reservation->check_in = Carbon::parse($validated['check_in']);
        $reservation->check_out = Carbon::parse($validated['check_in'])->addDays($nights);

        ReservationAvailability::assertAvailable($reservation);
        $reservation->save();

        return response()->json(['ok' => true]);
    })->name('reservations.move');

    Route::post('/admin/payments/{payment}/receipt', function (Payment $payment) {
        $payment->load('reservation');
        $propertyId = TenantContext::propertyId();
        abort_unless(! $propertyId || (int) $payment->reservation?->property_id === $propertyId, 403);

        Receipt::firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'reservation_id' => $payment->reservation_id,
                'property_id' => $payment->reservation?->property_id,
                'number' => 'REC-'.now()->format('ymd').'-'.$payment->id,
                'issued_at' => now(),
                'amount' => $payment->amount,
                'method' => $payment->method,
                'status' => 'issued',
            ],
        );

        return back();
    })->name('payments.receipt');

    Route::get('/admin/owner-daily-report/generate', function () {
        $propertyId = TenantContext::propertyId();
        $today = Carbon::today();

        OwnerDailyReport::updateOrCreate(
            ['property_id' => $propertyId, 'report_date' => $today],
            [
                'revenue' => Payment::query()->whereHas('reservation', fn ($query) => $query->where('property_id', $propertyId))->where('status', 'paid')->whereDate('paid_at', $today)->sum('amount'),
                'expenses' => Expense::query()->where('property_id', $propertyId)->whereDate('expense_date', $today)->sum('amount'),
                'arrivals' => Reservation::query()->where('property_id', $propertyId)->whereDate('check_in', $today)->count(),
                'departures' => Reservation::query()->where('property_id', $propertyId)->whereDate('check_out', $today)->count(),
                'occupied_rooms' => Reservation::query()->where('property_id', $propertyId)->whereDate('check_in', '<=', $today)->whereDate('check_out', '>', $today)->distinct('room_id')->count('room_id'),
                'open_tasks' => OperationalTask::query()->where('property_id', $propertyId)->whereIn('status', ['pending', 'in_progress'])->count(),
                'open_alerts' => OperationalAlert::query()->where('property_id', $propertyId)->where('status', 'open')->count(),
                'status' => 'draft',
                'summary' => 'Relatório diário gerado automaticamente.',
            ],
        );

        return back();
    })->name('owner-daily-report.generate');

    Route::post('/mobile/checklists/{dailyChecklist}/complete', function (Request $request, DailyChecklist $dailyChecklist) {
        $propertyId = TenantContext::propertyId();

        abort_unless(! $propertyId || (int) $dailyChecklist->property_id === $propertyId, 403);

        $validated = $request->validate([
            'photo' => ['nullable', 'image', 'max:5120'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'qr_code' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['evidence_photo_path'] = $request->file('photo')->store('checklist-evidence', 'public');
        }

        $dailyChecklist->forceFill([
            'status' => 'done',
            'completed_at' => now(),
            'completed_by_user_id' => $request->user()->id,
            'evidence_photo_path' => $validated['evidence_photo_path'] ?? $dailyChecklist->evidence_photo_path,
            'evidence_latitude' => $validated['latitude'] ?? null,
            'evidence_longitude' => $validated['longitude'] ?? null,
            'evidence_qr_code' => $validated['qr_code'] ?? null,
        ])->save();

        OperationalAlert::create([
            'property_id' => $dailyChecklist->property_id,
            'source_type' => DailyChecklist::class,
            'source_id' => $dailyChecklist->id,
            'severity' => 'info',
            'title' => 'Checklist concluída no mobile',
            'message' => $dailyChecklist->title.' por '.$request->user()->name,
            'status' => 'open',
        ]);

        return back()->with('status', 'Checklist concluída com prova.');
    })->name('mobile.checklists.complete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
