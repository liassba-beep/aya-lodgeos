<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Property;
use App\Models\User;
use App\Support\AccessControl;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = User::class;

    protected static ?string $permissionModule = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Administração';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Acesso';

    protected static ?string $pluralModelLabel = 'Equipa e acessos';

    protected static ?string $navigationLabel = 'Equipa e acessos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificação')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Telemóvel')
                        ->tel()
                        ->maxLength(30)
                        ->helperText('Usado no login mobile dos trabalhadores operacionais.'),
                    Forms\Components\Select::make('property_id')
                        ->label('Alojamento')
                        ->options(fn (): array => self::propertyOptions())
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => TenantContext::propertyId())
                        ->disabled(fn (): bool => auth()->user()?->role !== 'super_admin')
                        ->dehydrated(),
                    Forms\Components\Select::make('role')
                        ->label('Perfil')
                        ->options(fn (): array => self::roleOptions())
                        ->default('manager')
                        ->live()
                        ->required(),
                ]),
            Forms\Components\Section::make('Autorizações de acesso')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('web_access_enabled')
                        ->label('Permitir acesso web')
                        ->helperText('Autoriza o utilizador a entrar no painel web do alojamento.')
                        ->default(true)
                        ->live(),
                    Forms\Components\Toggle::make('mobile_access_enabled')
                        ->label('Permitir acesso mobile por telemóvel e PIN')
                        ->helperText('Active para camareira, cozinheiro, guarda ou outro perfil operacional.')
                        ->default(false)
                        ->live(),
                    Forms\Components\TextInput::make('mobile_pin')
                        ->label('PIN mobile')
                        ->password()
                        ->revealable()
                        ->numeric()
                        ->minLength(4)
                        ->maxLength(8)
                        ->required(fn (Forms\Get $get, string $operation): bool => $operation === 'create' && (bool) $get('mobile_access_enabled'))
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('mobile_access_enabled'))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Deixe vazio para manter o PIN actual.'),
                    Forms\Components\TextInput::make('password')
                        ->label('Palavra-passe')
                        ->password()
                        ->revealable()
                        ->required(fn (Forms\Get $get, string $operation): bool => $operation === 'create' && (bool) $get('web_access_enabled'))
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('web_access_enabled'))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->maxLength(255),
                ]),
            Forms\Components\Section::make('Preferências')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('locale')
                        ->label('Língua')
                        ->options([
                            'pt_PT' => 'Português',
                            'en' => 'Inglês',
                        ])
                        ->default('pt_PT')
                        ->required(),
                    Forms\Components\Select::make('theme_mode')
                        ->label('Tema')
                        ->options([
                            'system' => 'Automático',
                            'dark' => 'Escuro',
                            'light' => 'Claro',
                        ])
                        ->default('system')
                        ->required(),
                ]),
            Forms\Components\Section::make('Módulos permitidos')
                ->description('Só aparecem os módulos activos para este tenant. Deixe vazio para usar as permissões padrão do perfil.')
                ->columns(3)
                ->collapsed()
                ->schema(collect(self::permissionModuleOptions())
                    ->map(fn (string $label, string $module) => Forms\Components\CheckboxList::make('permissions.'.$module)
                        ->label($label)
                        ->options(AccessControl::actionLabels())
                        ->columns(2)
                        ->bulkToggleable())
                    ->values()
                    ->all()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('Telemóvel')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('property.name')->label('Alojamento')->toggleable(),
                Tables\Columns\IconColumn::make('web_access_enabled')->label('Web')->boolean()->toggleable(),
                Tables\Columns\IconColumn::make('mobile_access_enabled')->label('Mobile')->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Perfil')
                    ->formatStateUsing(fn (?string $state): string => AccessControl::roleLabels()[$state] ?? (string) $state)
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Editar')])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->where('property_id', $propertyId));
    }

    public static function propertyOptions(): array
    {
        return Property::query()
            ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->whereKey($propertyId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function roleOptions(): array
    {
        $roles = AccessControl::roleLabels();

        if (auth()->user()?->role === 'super_admin') {
            return $roles;
        }

        return collect($roles)
            ->only(['owner', 'manager', 'staff', 'security'])
            ->all();
    }

    public static function permissionModuleOptions(): array
    {
        if (auth()->user()?->role === 'super_admin') {
            return AccessControl::moduleLabels();
        }

        return AccessControl::currentTenantModuleLabels();
    }

    public static function fallbackPassword(): string
    {
        return Str::password(32);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
