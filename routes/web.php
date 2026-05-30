<?php

use App\Http\Controllers\ProfileController;
use App\Models\DailyChecklist;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\OperationalTask;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StockItem;
use App\Support\SimplePdf;
use App\Support\TenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

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

        $pdf = SimplePdf::make([
            'AYA LodgeOS',
            'Fatura: '.$invoice->number,
            'Alojamento: '.($invoice->property?->name ?? '-'),
            'Data de emissao: '.($invoice->issued_at?->format('d/m/Y') ?? '-'),
            'Vencimento: '.($invoice->due_at?->format('d/m/Y') ?? '-'),
            'Reserva: '.($reservation?->code ?? '-'),
            'Hospede: '.($reservation?->guest?->full_name ?? '-'),
            'Quarto: '.($reservation?->room?->name ?? '-'),
            'Subtotal: '.number_format((float) $invoice->subtotal, 2).' MZN',
            'Desconto: '.number_format((float) $invoice->discount_amount, 2).' MZN',
            'Imposto: '.number_format((float) $invoice->tax_amount, 2).' MZN',
            'Total: '.number_format((float) $invoice->total_amount, 2).' MZN',
            'Pago: '.number_format($paidAmount, 2).' MZN',
            'Saldo: '.number_format($balanceAmount, 2).' MZN',
            'Estado: '.$invoice->status,
            'Notas: '.($invoice->notes ?: '-'),
        ]);

        $filename = str($invoice->number)->replaceMatches('/[^A-Za-z0-9_-]/', '-')->lower();

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="fatura-'.$filename.'.pdf"',
        ]);
    })->name('invoices.pdf');

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

        return back()->with('status', 'Checklist concluida com prova.');
    })->name('mobile.checklists.complete');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
