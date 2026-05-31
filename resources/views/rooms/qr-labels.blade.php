<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR dos quartos</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        body {
            margin: 0;
            background: #f4f4f5;
            color: #18181b;
        }

        header {
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid #d4d4d8;
            display: flex;
            justify-content: space-between;
            padding: 20px 28px;
        }

        h1 {
            font-size: 22px;
            margin: 0;
        }

        p {
            margin: 4px 0 0;
        }

        button {
            background: #fbbf24;
            border: 0;
            border-radius: 8px;
            color: #18181b;
            cursor: pointer;
            font-weight: 800;
            padding: 10px 14px;
        }

        main {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            padding: 28px;
        }

        article {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d4d4d8;
            border-radius: 10px;
            display: grid;
            gap: 12px;
            justify-items: center;
            min-height: 290px;
            padding: 18px;
            text-align: center;
        }

        img {
            height: 170px;
            width: 170px;
        }

        .room {
            font-size: 19px;
            font-weight: 800;
        }

        .code {
            color: #52525b;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        @media print {
            body {
                background: #ffffff;
            }

            header {
                display: none;
            }

            main {
                gap: 10mm;
                grid-template-columns: repeat(2, 1fr);
                padding: 0;
            }

            article {
                break-inside: avoid;
                min-height: 88mm;
            }
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>QR dos quartos</h1>
            <p>{{ $property?->name ?? 'Alojamento' }} · imprima e coloque o QR no respectivo quarto.</p>
        </div>
        <button type="button" onclick="window.print()">Imprimir</button>
    </header>

    <main>
        @forelse ($rooms as $room)
            <article>
                <div>
                    <div class="room">{{ trim(($room->room_number ? $room->room_number.' - ' : '').$room->name) }}</div>
                    <p>Validar limpeza neste quarto</p>
                </div>
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&amp;margin=12&amp;data={{ rawurlencode($room->qr_code) }}"
                    alt="QR {{ trim(($room->room_number ? $room->room_number.' - ' : '').$room->name) }}"
                >
                <div class="code">{{ $room->qr_code }}</div>
            </article>
        @empty
            <article>
                <div class="room">Sem quartos</div>
                <p>Crie quartos antes de imprimir os QRs.</p>
            </article>
        @endforelse
    </main>
</body>
</html>
