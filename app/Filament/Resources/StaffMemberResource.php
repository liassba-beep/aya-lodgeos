<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\StaffMemberResource\Pages;
use App\Models\StaffMember;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffMemberResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = StaffMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Colaboradores';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Colaborador')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Forms\Components\Select::make('role')
                        ->label('Função')
                        ->options([
                            'manager' => 'Gerente',
                            'reception' => 'Recepção',
                            'housekeeping' => 'Camareira',
                            'maintenance' => 'Manutenção',
                            'security' => 'Guarda',
                            'kitchen' => 'Cozinha',
                            'staff' => 'Outro',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('phone')->label('Telefone')->tel()->maxLength(255),
                    Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
                    Forms\Components\Select::make('contract_type')
                        ->label('Contrato')
                        ->options([
                            'full_time' => 'Tempo inteiro',
                            'part_time' => 'Tempo parcial',
                            'temporary' => 'Temporário',
                            'service' => 'Prestador',
                        ]),
                    Forms\Components\DatePicker::make('hired_at')->label('Data de admissão'),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'active' => 'Activo',
                            'inactive' => 'Inactivo',
                            'on_leave' => 'Ausente',
                        ])
                        ->required(),
                    Forms\Components\Toggle::make('mobile_access_enabled')
                        ->label('Acesso mobile activo')
                        ->default(false),
                    Forms\Components\TextInput::make('mobile_pin')
                        ->label('PIN mobile')
                        ->password()
                        ->revealable()
                        ->numeric()
                        ->minLength(4)
                        ->maxLength(8)
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->helperText('O trabalhador entra com telefone e este PIN.'),
                    Forms\Components\FileUpload::make('checkin_photo_path')
                        ->label('Foto do último check-in')
                        ->image()
                        ->directory('staff-checkins')
                        ->visibility('public')
                        ->downloadable()
                        ->openable()
                        ->disabled()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->label('Função')->badge(),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->searchable(),
                Tables\Columns\TextColumn::make('property.name')->label('Alojamento')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('mobile_access_enabled')->label('Mobile')->boolean(),
                Tables\Columns\TextColumn::make('checked_in_at')->label('Check-in')->dateTime()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
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
            'index' => Pages\ListStaffMembers::route('/'),
            'create' => Pages\CreateStaffMember::route('/create'),
            'edit' => Pages\EditStaffMember::route('/{record}/edit'),
        ];
    }
}
