<x-filament-panels::page>
    @php($calendar = $this->calendarData())

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <x-filament::button
                color="gray"
                tag="a"
                href="{{ \App\Filament\Pages\ReservationCalendar::getUrl(['month' => $calendar['previous']]) }}"
            >
                Mes anterior
            </x-filament::button>

            <h2 class="text-xl font-semibold text-gray-950 dark:text-white">
                {{ $calendar['title'] }}
            </h2>

            <x-filament::button
                color="gray"
                tag="a"
                href="{{ \App\Filament\Pages\ReservationCalendar::getUrl(['month' => $calendar['next']]) }}"
            >
                Mes seguinte
            </x-filament::button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-950/40">
                        <th class="sticky left-0 z-10 min-w-44 bg-gray-50 px-3 py-3 text-left font-semibold text-gray-700 dark:bg-gray-950 dark:text-gray-200">
                            Quarto
                        </th>
                        @foreach ($calendar['days'] as $day)
                            <th class="min-w-12 px-2 py-2 text-center font-medium text-gray-600 dark:text-gray-300">
                                <span class="block">{{ $day['label'] }}</span>
                                <span class="text-xs text-gray-400">{{ $day['weekday'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($calendar['rooms'] as $room)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white px-3 py-3 font-medium text-gray-900 dark:bg-gray-900 dark:text-white">
                                {{ $room['number'] ? $room['number'].' - ' : '' }}{{ $room['name'] }}
                            </td>
                            @foreach ($calendar['days'] as $day)
                                @php
                                    $reservation = collect($room['reservations'])->first(fn ($reservation) => $reservation['check_in'] <= $day['key'] && $reservation['check_out'] > $day['key']);
                                @endphp
                                <td
                                    class="border-l border-gray-100 px-1 py-2 text-center dark:border-gray-800"
                                    data-room-id="{{ $room['id'] }}"
                                    data-date="{{ $day['key'] }}"
                                >
                                    @if ($reservation)
                                        <div
                                            class="cursor-move rounded-md bg-amber-500 px-2 py-1 text-xs font-semibold text-gray-950"
                                            draggable="true"
                                            data-reservation-id="{{ $reservation['id'] }}"
                                            data-nights="{{ $reservation['nights'] }}"
                                            title="{{ $reservation['code'] }} - {{ $reservation['guest'] }}"
                                        >
                                            {{ \Illuminate\Support\Str::limit($reservation['guest'] ?: $reservation['code'], 8) }}
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
                                Ainda nao existem quartos para mostrar no calendario.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-reservation-id]').forEach((item) => {
            item.addEventListener('dragstart', (event) => {
                event.dataTransfer.setData('reservationId', item.dataset.reservationId);
                event.dataTransfer.setData('nights', item.dataset.nights);
            });
        });

        document.querySelectorAll('[data-room-id][data-date]').forEach((cell) => {
            cell.addEventListener('dragover', (event) => event.preventDefault());
            cell.addEventListener('drop', async (event) => {
                event.preventDefault();

                const reservationId = event.dataTransfer.getData('reservationId');

                if (!reservationId) {
                    return;
                }

                const response = await fetch(`/admin/reservations/${reservationId}/move`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        room_id: cell.dataset.roomId,
                        check_in: cell.dataset.date,
                    }),
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Nao foi possivel mover a reserva. Verifique disponibilidade.');
                }
            });
        });
    </script>
</x-filament-panels::page>
