<?php

namespace App\Filament\Pages;

use App\Models\StaffMember;
use App\Models\StaffSchedule;
use App\Support\AccessControl;
use App\Support\TenantContext;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class StaffScheduleCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Colaboradores';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Calendário de escalas';

    protected static ?string $title = 'Calendário mensal de escalas';

    protected static string $view = 'filament.pages.staff-schedule-calendar';

    public static function canAccess(): bool
    {
        return AccessControl::allows('staff-schedule', 'view') || AccessControl::allows('*', 'view');
    }

    public function calendarData(): array
    {
        try {
            $requestedMonth = (string) request()->query('month', now()->format('Y-m'));
            $month = preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
                ? Carbon::createFromFormat('Y-m-d', $requestedMonth.'-01')->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $start = $month->copy();
        $end = $month->copy()->endOfMonth();
        $propertyId = TenantContext::propertyId();

        $staffMembers = StaffMember::query()
            ->where('property_id', $propertyId)
            ->orderBy('name')
            ->get();

        $schedules = StaffSchedule::query()
            ->where('property_id', $propertyId)
            ->whereBetween('shift_date', [$start, $end])
            ->orderBy('shift_date')
            ->get()
            ->groupBy(fn (StaffSchedule $schedule): string => $schedule->staff_member_id.'-'.$schedule->shift_date?->format('Y-m-d'));

        $days = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $days[] = [
                'key' => $date->format('Y-m-d'),
                'label' => $date->format('d'),
                'weekday' => $date->isoFormat('dd'),
            ];
        }

        return [
            'title' => $month->isoFormat('MMMM YYYY'),
            'previous' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
            'days' => $days,
            'staffMembers' => $staffMembers->map(fn (StaffMember $staffMember): array => [
                'id' => $staffMember->id,
                'name' => $staffMember->name,
                'role' => $staffMember->role,
                'schedules' => collect($days)->mapWithKeys(function (array $day) use ($staffMember, $schedules): array {
                    $schedule = $schedules->get($staffMember->id.'-'.$day['key'])?->first();

                    return [$day['key'] => $schedule ? [
                        'shift_type' => $schedule->shift_type,
                        'starts_at' => $schedule->starts_at,
                        'ends_at' => $schedule->ends_at,
                        'status' => $schedule->status,
                    ] : null];
                })->all(),
            ])->all(),
        ];
    }
}
