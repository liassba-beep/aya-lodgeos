<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\TenantAccountResource\Pages;
use App\Models\Property;
use App\Models\TenantAccount;
use App\Models\User;
use App\Support\AccessControl;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantAccountResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = TenantAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'SaaS';
    protected static ?string $modelLabel = 'Tenant';
    protected static ?string $pluralModelLabel = 'Tenants';

    public static function initialAccessForm(): array
    {
        return [
            Forms\Components\TextInput::make('property_name')
                ->label('Alojamento')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('owner_name')
                ->label('Nome do proprietário')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('owner_email')
                ->label('Email de acesso')
                ->email()
                ->rules(['unique:users,email'])
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('owner_phone')
                ->label('Telemóvel')
                ->tel()
                ->maxLength(30),
            Forms\Components\TextInput::make('owner_password')
                ->label('Palavra-passe inicial')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->maxLength(255),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tenant')->columns(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                        $set('slug', Str::slug($state ?? ''));

                        if (! $get('onboarding_property_name')) {
                            $set('onboarding_property_name', $state);
                        }
                    }),
                Forms\Components\TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('billing_email')
                    ->label('Email de cobranca')
                    ->email()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                        if (! $get('onboarding_owner_email')) {
                            $set('onboarding_owner_email', $state);
                        }
                    }),
                Forms\Components\TextInput::make('billing_phone')->label('Telefone de cobranca'),
                Forms\Components\Select::make('status')->label('Estado')->options(['active' => 'Activo', 'suspended' => 'Suspenso'])->default('active')->required(),
                Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Módulos autorizados')
                ->description('Seleccione os módulos que este tenant pode usar. Utilizadores e permissões continuam a aplicar-se dentro destes limites.')
                ->schema([
                    Forms\Components\CheckboxList::make('enabled_modules')
                        ->label('Módulos activos')
                        ->options(AccessControl::tenantModuleFlatOptions())
                        ->columns(2)
                        ->bulkToggleable()
                        ->default(AccessControl::tenantModuleKeys())
                        ->afterStateHydrated(function (Forms\Components\CheckboxList $component, ?array $state): void {
                            $component->state($state ?? AccessControl::tenantModuleKeys());
                        }),
                ]),
            Forms\Components\Section::make('Primeiro acesso ao APP')
                ->description('Opcional na criação: cria o alojamento inicial e o utilizador proprietário já com acesso web.')
                ->columns(2)
                ->visible(fn (string $operation): bool => $operation === 'create')
                ->schema([
                    Forms\Components\Toggle::make('onboarding_create_access')
                        ->label('Criar acesso inicial')
                        ->default(true)
                        ->live(),
                    Forms\Components\TextInput::make('onboarding_property_name')
                        ->label('Alojamento inicial')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->required(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access')),
                    Forms\Components\TextInput::make('onboarding_owner_name')
                        ->label('Nome do proprietário')
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->required(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access')),
                    Forms\Components\TextInput::make('onboarding_owner_email')
                        ->label('Email de acesso')
                        ->email()
                        ->maxLength(255)
                        ->rules(['unique:users,email'])
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->required(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access')),
                    Forms\Components\TextInput::make('onboarding_owner_phone')
                        ->label('Telemóvel')
                        ->tel()
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->maxLength(30),
                    Forms\Components\TextInput::make('onboarding_owner_password')
                        ->label('Palavra-passe inicial')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(255)
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->required(fn (Forms\Get $get): bool => (bool) $get('onboarding_create_access'))
                        ->helperText('Entregue esta palavra-passe ao cliente e peça troca após o primeiro login.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->label('Slug'),
            Tables\Columns\TextColumn::make('properties_count')->label('Propriedades')->counts('properties'),
            Tables\Columns\TextColumn::make('enabled_modules')
                ->label('Módulos')
                ->formatStateUsing(fn ($state): string => is_array($state) ? (string) count($state) : 'Todos')
                ->badge(),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([
            Tables\Actions\Action::make('createInitialAccess')
                ->label('Criar acesso')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                ->fillForm(fn (TenantAccount $record): array => [
                    'property_name' => $record->properties()->orderBy('id')->value('name') ?: $record->name,
                    'owner_email' => $record->billing_email,
                    'owner_phone' => $record->billing_phone,
                ])
                ->form(self::initialAccessForm())
                ->action(function (TenantAccount $record, array $data): void {
                    self::createInitialAccess($record, $data);

                    Notification::make()
                        ->title('Acesso inicial criado')
                        ->body('O proprietário já pode entrar no painel web do tenant.')
                        ->success()
                        ->send();
                }),
            Tables\Actions\EditAction::make()->label('Editar'),
        ]);
    }

    public static function createInitialAccess(TenantAccount $tenant, array $data): User
    {
        return DB::transaction(function () use ($tenant, $data): User {
            $property = $tenant->properties()->orderBy('id')->first();

            if (! $property) {
                $property = Property::query()->create([
                    'tenant_account_id' => $tenant->id,
                    'name' => $data['property_name'],
                    'type' => 'guest_house',
                    'status' => 'active',
                    'email' => $data['owner_email'],
                    'phone' => $data['owner_phone'] ?? null,
                    'country' => 'Mozambique',
                ]);
            }

            $owner = User::query()->create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'phone' => $data['owner_phone'] ?? null,
                'property_id' => $property->id,
                'role' => 'owner',
                'web_access_enabled' => true,
                'mobile_access_enabled' => false,
                'permissions' => null,
                'locale' => 'pt_PT',
                'theme_mode' => 'system',
                'password' => $data['owner_password'],
            ]);

            $owner->properties()->syncWithoutDetaching([
                $property->id => ['role' => 'owner', 'permissions' => null],
            ]);

            return $owner;
        });
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTenantAccounts::route('/'), 'create' => Pages\CreateTenantAccount::route('/create'), 'edit' => Pages\EditTenantAccount::route('/{record}/edit')];
    }
}
