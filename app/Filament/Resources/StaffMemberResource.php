<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffMemberResource\Pages;
use App\Models\StaffMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffMemberResource extends Resource
{
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
                    Forms\Components\Select::make('property_id')->label('Alojamento')->relationship('property', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Forms\Components\Select::make('role')
                        ->label('Funcao')
                        ->options([
                            'manager' => 'Gerente',
                            'reception' => 'Recepcao',
                            'housekeeping' => 'Camareira',
                            'maintenance' => 'Manutencao',
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
                            'temporary' => 'Temporario',
                            'service' => 'Prestador',
                        ]),
                    Forms\Components\DatePicker::make('hired_at')->label('Data de admissao'),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'active' => 'Ativo',
                            'inactive' => 'Inativo',
                            'on_leave' => 'Ausente',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->label('Funcao')->badge(),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->searchable(),
                Tables\Columns\TextColumn::make('property.name')->label('Alojamento')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Editar')])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
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
