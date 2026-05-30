<x-filament-panels::page>
    @php
        $calendar = $this->calendarData();
    @endphp

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <x-filament::button
                color="gray"
                tag="a"
                href="{{ \App\Filament\Pages\StaffScheduleCalendar::getUrl(['month' => $calendar['previous']]) }}"
            >
                Mês anterior
            </x-filament::button>

            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                {{ $calendar['title'] }}
            </h2>

            <x-filament::button
                color="gray"
                tag="a"
                href="{{ \App\Filament\Pages\StaffScheduleCalendar::getUrl(['month' => $calendar['next']]) }}"
            >
                Mês seguinte
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950/40">
                        <th class="sticky left-0 z-10 min-w-56 bg-gray-50 px-3 py-3 text-left font-semibold text-gray-700 dark:bg-gray-950 dark:text-gray-200">
                            Colaborador
                        </th>
                        @foreach ($calendar['days'] as $day)
                            <th class="min-w-20 px-2 py-2 text-center font-medium text-gray-600 dark:text-gray-300">
                                <span class="block">{{ $day['label'] }}</span>
                                <span class="text-xs text-gray-400">{{ $day['weekday'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($calendar['staffMembers'] as $staffMember)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white px-3 py-3 font-medium text-gray-900 dark:bg-gray-900 dark:text-white">
                                <span class="block">{{ $staffMember['name'] }}</span>
                                <span class="text-xs text-gray-500">{{ $staffMember['role'] }}</span>
                            </td>
                            @foreach ($calendar['days'] as $day)
                                @php($schedule = $staffMember['schedules'][$day['key']] ?? null)
                                <td class="border-l border-gray-100 px-1 py-2 text-center dark:border-gray-800">
                                    @if ($schedule)
                                        <div class="rounded-md bg-amber-500 px-2 py-1 text-xs font-semibold text-gray-950">
                                            <span class="block">{{ $schedule['shift_type'] }}</span>
                                            <span class="font-normal">
                                                {{ $schedule['starts_at'] ?: '--:--' }} - {{ $schedule['ends_at'] ?: '--:--' }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-gray-300 dark:text-gray-700">-</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($calendar['days']) + 1 }}" class="px-4 py-8 text-center text-gray-500">
                                Ainda não existem colaboradores para mostrar no calendário.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
