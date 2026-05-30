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

class UserResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = User::class;

    protected static ?string $permissionModule = 'user';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'SaaS';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Utilizador';

    protected static ?string $pluralModelLabel = 'Utilizadores e permissões';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Acesso')
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
                        ->options(fn (): array => Property::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('role')
                        ->label('Perfil')
                        ->options(AccessControl::roleLabels())
                        ->default('manager')
                        ->live()
                        ->required(),
                    Forms\Components\Toggle::make('mobile_access_enabled')
                        ->label('Permitir acesso mobile por telemóvel e PIN')
                        ->helperText('Active para camareira, cozinheiro, guarda ou outro perfil operacional.')
                        ->default(false),
                    Forms\Components\TextInput::make('mobile_pin')
                        ->label('PIN mobile')
                        ->password()
                        ->revealable()
                        ->numeric()
                        ->minLength(4)
                        ->maxLength(8)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('Deixe vazio para manter o PIN actual.'),
                    Forms\Components\TextInput::make('password')
                        ->label('Palavra-passe')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
