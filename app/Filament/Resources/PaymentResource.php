<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Operacoes';

    protected static ?string $modelLabel = 'Pagamento';

    protected static ?string $pluralModelLabel = 'Pagamentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pagamento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('reservation_id')
                            ->label('Reserva')
                            ->options(fn (): array => Reservation::query()
                                ->with('guest')
                                ->orderByDesc('created_at')
                                ->get()
                                ->mapWithKeys(fn (Reservation $reservation): array => [
                                    $reservation->id => $reservation->code.' - '.$reservation->guest?->full_name.' - '.number_format((float) $reservation->total_amount, 2).' MZN',
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $reservation = $state ? Reservation::find($state) : null;

                                $set('amount', $reservation?->total_amount ?? 0);
                            })
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->label('Valor')
                            ->numeric()
                            ->prefix('MZN')
                            ->default(0)
                            ->required(),
                        Forms\Components\Select::make('method')
                            ->label('Metodo')
                            ->options([
                                'cash' => 'Dinheiro',
                                'mpesa' => 'M-Pesa',
                                'emola' => 'e-Mola',
                                'card' => 'Cartao',
                                'bank_transfer' => 'Transferencia',
                                'other' => 'Outro',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendente',
                                'paid' => 'Pago',
                                'failed' => 'Falhou',
                                'refunded' => 'Reembolsado',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Data de pagamento')
                            ->seconds(false),
                        Forms\Components\TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255),
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
                Tables\Columns\TextColumn::make('reservation.code')
                    ->label('Reserva')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reservation.guest.full_name')
                    ->label('Hospede'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Valor')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Metodo')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Dinheiro',
                        'mpesa' => 'M-Pesa',
                        'emola' => 'e-Mola',
                        'card' => 'Cartao',
                        'bank_transfer' => 'Transferencia',
                        'other' => 'Outro',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'failed' => 'Falhou',
                        'refunded' => 'Reembolsado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Pago em')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Pago',
                        'failed' => 'Falhou',
                        'refunded' => 'Reembolsado',
                    ]),
                Tables\Filters\SelectFilter::make('method')
                    ->label('Metodo')
                    ->options([
                        'cash' => 'Dinheiro',
                        'mpesa' => 'M-Pesa',
                        'emola' => 'e-Mola',
                        'card' => 'Cartao',
                        'bank_transfer' => 'Transferencia',
                        'other' => 'Outro',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
