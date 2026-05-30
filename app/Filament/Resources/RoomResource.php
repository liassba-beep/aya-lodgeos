<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomResource\Pages;
use App\Models\Room;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Quarto';

    protected static ?string $pluralModelLabel = 'Quartos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do quarto')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Hidden::make('property_id')
                            ->default(fn (): ?int => TenantContext::propertyId())
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('room_number')
                            ->label('Numero')
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'standard' => 'Standard',
                                'double' => 'Duplo',
                                'suite' => 'Suite',
                                'family' => 'Familiar',
                                'dorm' => 'Dormitorio',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Capacidade')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('base_rate')
                            ->label('Preco base')
                            ->numeric()
                            ->prefix('MZN')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'available' => 'Disponivel',
                                'occupied' => 'Ocupado',
                                'maintenance' => 'Manutencao',
                                'inactive' => 'Inativo',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Quarto')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Alojamento')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('room_number')
                    ->label('Numero')
                    ->searchable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Cap.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_rate')
                    ->label('Preco')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'warning',
                        'maintenance' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'available' => 'Disponivel',
                        'occupied' => 'Ocupado',
                        'maintenance' => 'Manutencao',
                        'inactive' => 'Inativo',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }
}
