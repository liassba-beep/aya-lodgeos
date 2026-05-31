<?php

namespace App\Support;

use App\Models\DailyChecklist;
use App\Models\OperationalAlert;
use App\Models\OperationalTask;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class OperationalNotificationSummary
{
    public static function forProperty(?int $propertyId, ?int $staffMemberId = null): array
    {
        $today = Carbon::today();
        $now = now();

        $taskBase = OperationalTask::query()
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->when($staffMemberId, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('staff_member_id')
                ->orWhere('staff_member_id', $staffMemberId)))
            ->whereIn('status', ['pending', 'in_progress']);

        $checklistBase = DailyChecklist::query()
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->when($staffMemberId, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('staff_member_id')
                ->orWhere('staff_member_id', $staffMemberId)))
            ->where('status', '!=', 'done');

        $overdueTasks = (clone $taskBase)
            ->where(function (Builder $query) use ($today, $now): void {
                $query
                    ->whereDate('due_date', '<', $today)
                    ->orWhere(function (Builder $query) use ($today, $now): void {
                        $query
                            ->whereDate('due_date', $today)
                            ->whereNotNull('due_time')
                            ->whereTime('due_time', '<', $now->format('H:i:s'));
                    });
            })
            ->count();

        $overdueChecklists = (clone $checklistBase)
            ->whereDate('checklist_date', '<', $today)
            ->count();

        $pendingChecklistsToday = (clone $checklistBase)
            ->whereDate('checklist_date', $today)
            ->count();

        $pendingTasks = (clone $taskBase)->count();

        $latestTask = (clone $taskBase)
            ->with('room')
            ->latest('updated_at')
            ->first(['id', 'room_id', 'title', 'priority', 'status', 'due_date', 'updated_at']);

        $latestChecklist = (clone $checklistBase)
            ->with('room')
            ->latest('updated_at')
            ->first(['id', 'room_id', 'title', 'area', 'status', 'checklist_date', 'updated_at']);

        $latestReservation = Reservation::query()
            ->with(['guest', 'room'])
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->where('status', '!=', 'cancelled')
            ->whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>=', $today)
            ->latest('updated_at')
            ->first(['id', 'guest_id', 'room_id', 'code', 'status', 'updated_at']);

        $latestAlert = $staffMemberId ? null : OperationalAlert::query()
            ->when($propertyId, fn (Builder $query) => $query->where('property_id', $propertyId))
            ->where('status', 'open')
            ->latest()
            ->first(['id', 'severity', 'title', 'message', 'created_at']);

        $overdueCount = $overdueTasks + $overdueChecklists;
        $latest = self::messageFor(
            overdueCount: $overdueCount,
            pendingChecklistsToday: $pendingChecklistsToday,
            latestTask: $latestTask,
            latestChecklist: $latestChecklist,
            latestReservation: $latestReservation,
            latestAlert: $latestAlert,
        );

        $revisionParts = [
            $pendingTasks,
            $pendingChecklistsToday,
            $overdueCount,
            $latestTask?->id,
            $latestTask?->updated_at?->timestamp,
            $latestChecklist?->id,
            $latestChecklist?->updated_at?->timestamp,
            $latestReservation?->id,
            $latestReservation?->updated_at?->timestamp,
            $latestAlert?->id,
        ];

        $overdueSignatureParts = [
            $propertyId,
            $staffMemberId,
            $today->toDateString(),
            $overdueCount,
            $pendingChecklistsToday,
            $latestTask?->id,
            $latestTask?->updated_at?->timestamp,
            $latestChecklist?->id,
            $latestChecklist?->updated_at?->timestamp,
        ];

        return [
            'revision' => sha1(implode('|', array_map(fn ($value) => (string) $value, $revisionParts))),
            'new_count' => $pendingTasks + $pendingChecklistsToday,
            'overdue_count' => $overdueCount,
            'pending_today_count' => $pendingChecklistsToday,
            'latest' => $latest,
            'overdue' => [
                'count' => $overdueCount,
                'signature' => sha1(implode('|', array_map(fn ($value) => (string) $value, $overdueSignatureParts))),
                'title' => $overdueCount > 0 ? 'Atividade atrasada' : 'Atividades do dia',
                'message' => $overdueCount > 0
                    ? 'Há '.$overdueCount.' atividade(s) atrasada(s) ou não realizada(s).'
                    : ($pendingChecklistsToday > 0 ? 'Há '.$pendingChecklistsToday.' limpeza(s) por concluir hoje.' : 'Tudo certo por agora.'),
                'severity' => $overdueCount > 0 ? 'warning' : 'info',
            ],
        ];
    }

    protected static function messageFor(
        int $overdueCount,
        int $pendingChecklistsToday,
        ?OperationalTask $latestTask,
        ?DailyChecklist $latestChecklist,
        ?Reservation $latestReservation,
        ?OperationalAlert $latestAlert,
    ): ?array {
        if ($overdueCount > 0) {
            return [
                'severity' => 'warning',
                'title' => 'Atividade atrasada',
                'message' => 'Há '.$overdueCount.' atividade(s) atrasada(s) ou não realizada(s).',
            ];
        }

        if ($latestAlert) {
            return [
                'id' => 'alert-'.$latestAlert->id,
                'severity' => $latestAlert->severity,
                'title' => $latestAlert->title,
                'message' => $latestAlert->message,
            ];
        }

        if ($latestTask) {
            return [
                'id' => 'task-'.$latestTask->id,
                'severity' => $latestTask->priority === 'critical' ? 'warning' : 'info',
                'title' => 'Nova tarefa',
                'message' => trim($latestTask->title.' - '.($latestTask->room?->name ?: 'Área geral')),
            ];
        }

        if ($latestChecklist) {
            return [
                'id' => 'checklist-'.$latestChecklist->id,
                'severity' => 'info',
                'title' => $pendingChecklistsToday > 0 ? 'Limpeza por fazer' : 'Checklist por fazer',
                'message' => trim($latestChecklist->title.' - '.($latestChecklist->room?->name ?: $latestChecklist->area)),
            ];
        }

        if ($latestReservation) {
            $roomName = $latestReservation->room?->name ?: 'quarto por confirmar';

            return [
                'id' => 'reservation-'.$latestReservation->id,
                'severity' => 'info',
                'title' => 'Reserva para hoje',
                'message' => trim(($latestReservation->guest?->full_name ?: $latestReservation->code).' - '.$roomName),
            ];
        }

        return null;
    }
}
