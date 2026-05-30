<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ReceiptResource\Pages;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Reservation;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReceiptResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = Receipt::class;
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Recibo';
    protected static ?string $pluralModelLabel = 'Recibos';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Recibo')->columns(3)->schema([
            Forms\Components\TextInput::make('number')->label('Número')->required()->unique(ignoreRecord: true),
            Forms\Components\Hidden::make('property_id')->default(fn () => TenantContext::propertyId()),
            Forms\Components\Select::make('reservation_id')
                ->label('Reserva')
                ->options(fn (): array => Reservation::query()
                    ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->where('property_id', $propertyId))
                    ->latest()
                    ->limit(100)
                    ->pluck('code', 'id')
                    ->all())
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('payment_id')
                ->label('Pagamento')
                ->options(fn (): array => Payment::query()
                    ->with('reservation')
                    ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->whereHas('reservation', fn ($reservationQuery) => $reservationQuery->where('property_id', $propertyId)))
                    ->latest()
                    ->limit(100)
                    ->get()
                    ->mapWithKeys(fn (Payment $payment): array => [
                        $payment->id => trim(($payment->reference ?: 'Pagamento #'.$payment->id).' - '.($payment->reservation?->code ?: 'sem reserva').' - '.number_format((float) $payment->amount, 2).' MZN'),
                    ])
                    ->all())
                ->searchable()
                ->preload(),
            Forms\Components\DatePicker::make('issued_at')->label('Emissão')->default(now())->required(),
            Forms\Components\TextInput::make('amount')->label('Valor')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('method')->label('Método')->required(),
            Forms\Components\Select::make('status')->label('Estado')->options(['issued' => 'Emitido', 'cancelled' => 'Anulado'])->required(),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('number')->label('Número')->searchable(),
            Tables\Columns\TextColumn::make('reservation.code')->label('Reserva'),
            Tables\Columns\TextColumn::make('issued_at')->label('Emissão')->date(),
            Tables\Columns\TextColumn::make('amount')->label('Valor')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->where('property_id', $propertyId));
    }

    public static function getPages(): array { return ['index' => Pages\ListReceipts::route('/'), 'create' => Pages\CreateReceipt::route('/create'), 'edit' => Pages\EditReceipt::route('/{record}/edit')]; }
}
