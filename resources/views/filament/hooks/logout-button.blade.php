@auth
    <form method="POST" action="{{ route('logout') }}" class="aya-panel-logout">
        @csrf
        <button type="submit" aria-label="Terminar sessão">
            <span>Terminar sessão</span>
        </button>
    </form>

    <style>
        .aya-panel-logout {
            position: fixed;
            top: 0.875rem;
            right: 4.5rem;
            z-index: 50;
        }

        .aya-panel-logout button {
            display: inline-flex;
            min-height: 2.25rem;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 0.5rem;
            background: rgba(24, 24, 27, 0.92);
            padding: 0 0.875rem;
            color: rgb(244, 244, 245);
            font: 600 0.875rem/1.25rem ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            white-space: nowrap;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.18);
            transition: border-color 150ms ease, background 150ms ease, color 150ms ease;
        }

        .aya-panel-logout button:hover,
        .aya-panel-logout button:focus-visible {
            border-color: rgb(245, 158, 11);
            background: rgb(245, 158, 11);
            color: rgb(24, 24, 27);
            outline: none;
        }

        @media (max-width: 640px) {
            .aya-panel-logout {
                right: 3.75rem;
            }

            .aya-panel-logout span {
                display: none;
            }

            .aya-panel-logout button::before {
                content: "Sair";
            }
        }
    </style>
@endauth
