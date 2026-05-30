<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\OperationalTask;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\StockItem;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class OwnerOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $totalRooms = Room::query()->count();
        $occupiedRooms = Reservation::query()
            ->where('status', '!=', 'cancelled')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->distinct('room_id')
            ->count('room_id');

        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100) : 0;

        $paidThisMonth = Payment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->sum('amount');

        $expensesThisMonth = Expense::query()
            ->whereBetween('expense_date', [$monthStart, $monthEnd])
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $pendingTasks = OperationalTask::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->count();

        $lowStock = StockItem::query()
            ->whereColumn('quantity_on_hand', '<=', 'minimum_quantity')
            ->count();

        return [
            Stat::make('Ocupacao hoje', $occupancyRate.'%')
                ->description($occupiedRooms.' de '.$totalRooms.' quartos ocupados')
                ->color($occupancyRate >= 70 ? 'success' : 'warning'),
            Stat::make('Receita recebida no mes', number_format((float) $paidThisMonth, 2).' MZN')
                ->description('Pagamentos com estado pago')
                ->color('success'),
            Stat::make('Despesas do mes', number_format((float) $expensesThisMonth, 2).' MZN')
                ->description('Despesas aprovadas ou pagas')
                ->color('danger'),
            Stat::make('Saldo operacional', number_format((float) $paidThisMonth - (float) $expensesThisMonth, 2).' MZN')
                ->description('Receita recebida menos despesas')
                ->color(((float) $paidThisMonth - (float) $expensesThisMonth) >= 0 ? 'success' : 'danger'),
            Stat::make('Tarefas pendentes', (string) $pendingTasks)
                ->description('Operacional por concluir')
                ->color($pendingTasks > 0 ? 'warning' : 'success'),
            Stat::make('Artigos abaixo do minimo', (string) $lowStock)
                ->description('Stock que precisa de reposicao')
                ->color($lowStock > 0 ? 'danger' : 'success'),
        ];
    }
}
