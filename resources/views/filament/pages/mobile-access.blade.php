<x-filament-panels::page>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600">
                        Equipa operacional
                    </p>
                    <h2 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">
                        Entrada dos trabalhadores
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Use esta área para colaboradores que entram com número de telemóvel e PIN. O acesso é controlado em
                        <strong>Equipa e acessos</strong>, activando a opção <strong>Mobile</strong> em cada utilizador.
                    </p>
                </div>
                <x-filament::icon
                    icon="heroicon-o-device-phone-mobile"
                    class="h-10 w-10 text-primary-500"
                />
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button tag="a" href="{{ url('/trabalhador/login') }}" target="_blank">
                    Abrir app do trabalhador
                </x-filament::button>
                @if (\App\Support\AccessControl::allows('user', 'view'))
                    <x-filament::button color="gray" tag="a" href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}">
                        Gerir acessos
                    </x-filament::button>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-primary-600">
                        Utilizadores web
                    </p>
                    <h2 class="mt-2 text-xl font-semibold text-gray-950 dark:text-white">
                        App mobile autenticada
                    </h2>
                    <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Proprietários, gerentes e utilizadores autorizados podem abrir a versão mobile ligada ao painel web. O master
                        controla se este módulo fica disponível para cada tenant.
                    </p>
                </div>
                <x-filament::icon
                    icon="heroicon-o-shield-check"
                    class="h-10 w-10 text-primary-500"
                />
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <x-filament::button tag="a" href="{{ url('/mobile') }}" target="_blank">
                    Abrir app mobile
                </x-filament::button>
                @if (\App\Support\AccessControl::allows('tenant-account', 'view'))
                    <x-filament::button color="gray" tag="a" href="{{ \App\Filament\Resources\TenantAccountResource::getUrl('index') }}">
                        Módulos do tenant
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 text-sm leading-6 text-primary-950 dark:border-primary-800 dark:bg-primary-950/30 dark:text-primary-100">
        <strong>Fluxo recomendado:</strong> o master activa o módulo <strong>App mobile</strong> no tenant; o proprietário fica como
        administrador da propriedade; depois, em <strong>Equipa e acessos</strong>, cria trabalhadores com telefone, PIN e permissões
        para web e/ou mobile.
    </div>
</x-filament-panels::page>
