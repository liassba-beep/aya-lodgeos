<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationResource\Pages;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Reservas';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Reserva';

    protected static ?string $pluralModelLabel = 'Reservas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reserva')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Codigo')
                            ->placeholder('Gerado automaticamente se ficar vazio')
                            ->maxLength(255),
                        Forms\Components\Hidden::make('property_id')
                            ->required(),
                        Forms\Components\Select::make('room_id')
                            ->label('Quarto')
                            ->options(fn (): array => Room::query()
                                ->with('property')
                                ->where('property_id', TenantContext::propertyId())
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Room $room): array => [
                                    $room->id => $room->name.' - '.$room->property?->name,
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                $room = $state ? Room::find($state) : null;

                                $set('property_id', $room?->property_id);
                                $set('nightly_rate', $room?->base_rate ?? 0);

                                self::updateTotal($set, $get);
                            })
                            ->required(),
                    ]),
                Forms\Components\Section::make('Hospede e estadia')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('guest_id')
                            ->label('Hospede')
                            ->relationship('guest', 'first_name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                            ->getOptionLabelFromRecordUsing(fn (Guest $record): string => $record->full_name)
                            ->searchable(['first_name', 'last_name', 'email', 'phone'])
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('check_in')
                            ->label('Entrada')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get))
                            ->required(),
                        Forms\Components\DatePicker::make('check_out')
                            ->label('Saida')
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get))
                            ->required()
                            ->after('check_in'),
                        Forms\Components\TextInput::make('adults')
                            ->label('Adultos')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Forms\Components\TextInput::make('children')
                            ->label('Criancas')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        Forms\Components\Toggle::make('breakfast_included')
                            ->label('Pequeno almoco incluido')
                            ->default(false),
                    ]),
                Forms\Components\Section::make('Valores')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('nightly_rate')
                            ->label('Preco do quarto por noite')
                            ->numeric()
                            ->prefix('MZN')
                            ->readOnly()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Desconto')
                            ->numeric()
                            ->prefix('MZN')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get))
                            ->required(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total')
                            ->numeric()
                            ->prefix('MZN')
                            ->readOnly()
                            ->dehydrated()
                            ->default(0)
                            ->required(),
                    ]),
                Forms\Components\Section::make('Estado e origem')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendente',
                                'confirmed' => 'Confirmada',
                                'checked_in' => 'Entrada efetuada',
                                'checked_out' => 'Saida efetuada',
                                'cancelled' => 'Cancelada',
                            ])
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Origem')
                            ->options([
                                'direct' => 'Direta',
                                'phone' => 'Telefone',
                                'walk_in' => 'Entrada sem reserva previa',
                                'booking' => 'Booking',
                                'airbnb' => 'Airbnb',
                                'other' => 'Outra',
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Codigo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest.full_name')
                    ->label('Hospede')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Alojamento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Quarto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Entrada')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Saida')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Desconto')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('breakfast_included')
                    ->label('Pequeno almoco')
                    ->boolean(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmada',
                        'checked_in' => 'Entrada efetuada',
                        'checked_out' => 'Saida efetuada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed', 'checked_in' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendente',
                        'confirmed' => 'Confirmada',
                        'checked_in' => 'Entrada efetuada',
                        'checked_out' => 'Saida efetuada',
                        'cancelled' => 'Cancelada',
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
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }

    private static function updateTotal(Set $set, Get $get): null
    {
        $set('total_amount', Reservation::calculateTotal(
            $get('check_in'),
            $get('check_out'),
            $get('nightly_rate'),
            $get('discount_amount'),
        ));

        self::notifyIfRoomIsUnavailable($get);

        return null;
    }

    private static function notifyIfRoomIsUnavailable(Get $get): void
    {
        if (! $get('room_id') || ! $get('check_in') || ! $get('check_out')) {
            return;
        }

        $conflict = Reservation::query()
            ->where('room_id', $get('room_id'))
            ->where('status', '!=', 'cancelled')
            ->when($get('id'), fn ($query, $id) => $query->whereKeyNot($id))
            ->whereDate('check_in', '<', $get('check_out'))
            ->whereDate('check_out', '>', $get('check_in'))
            ->exists();

        if (! $conflict) {
            return;
        }

        Notification::make()
            ->title('Quarto indisponivel nestas datas')
            ->body('Escolha outro quarto ou altere as datas da reserva.')
            ->danger()
            ->send();
    }
}
