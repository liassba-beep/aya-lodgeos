<?php

use App\Http\Controllers\ProfileController;
use App\Models\DailyChecklist;
use App\Models\Expense;
use App\Models\OperationalTask;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StockItem;
use App\Support\TenantContext;
use Illuminate\Foundation\Application;
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
            'title' => $checklist->title,
            'area' => $checklist->area,
            'staff' => $checklist->staffMember?->name,
            'status' => $checklist->status,
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
