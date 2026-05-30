<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use App\Models\Reservation;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Fatura';

    protected static ?string $pluralModelLabel = 'Faturacao';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Fatura')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')->label('Numero')->required()->maxLength(255),
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('reservation_id')
                        ->label('Reserva')
                        ->options(fn (): array => Reservation::query()
                            ->with('guest')
                            ->where('property_id', TenantContext::propertyId())
                            ->orderByDesc('created_at')
                            ->get()
                            ->mapWithKeys(fn (Reservation $reservation): array => [
                                $reservation->id => $reservation->code.' - '.$reservation->guest?->full_name,
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $reservation = $state ? Reservation::find($state) : null;
                            $set('property_id', $reservation?->property_id);
                            $set('subtotal', $reservation?->total_amount ?? 0);
                            self::updateTotal($set, 0, $reservation?->total_amount ?? 0, 0);
                        }),
                    Forms\Components\DatePicker::make('issued_at')->label('Data de emissao')->default(now())->required(),
                    Forms\Components\DatePicker::make('due_at')->label('Vencimento'),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'draft' => 'Rascunho',
                            'issued' => 'Emitida',
                            'paid' => 'Paga',
                            'cancelled' => 'Anulada',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('MZN')->live(onBlur: true)->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get('discount_amount'), $get('subtotal'), $get('tax_amount')))->required(),
                    Forms\Components\TextInput::make('discount_amount')->label('Desconto')->numeric()->prefix('MZN')->default(0)->live(onBlur: true)->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get('discount_amount'), $get('subtotal'), $get('tax_amount')))->required(),
                    Forms\Components\TextInput::make('tax_amount')->label('Imposto')->numeric()->prefix('MZN')->default(0)->live(onBlur: true)->afterStateUpdated(fn (Set $set, Get $get): null => self::updateTotal($set, $get('discount_amount'), $get('subtotal'), $get('tax_amount')))->required(),
                    Forms\Components\TextInput::make('total_amount')->label('Total')->numeric()->prefix('MZN')->readOnly()->dehydrated()->required(),
                    Forms\Components\Placeholder::make('paid_amount')
                        ->label('Pago')
                        ->content(fn (?Invoice $record): string => number_format((float) ($record?->paid_amount ?? 0), 2).' MZN'),
                    Forms\Components\Placeholder::make('balance_amount')
                        ->label('Saldo')
                        ->content(fn (?Invoice $record): string => number_format((float) ($record?->balance_amount ?? 0), 2).' MZN'),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Numero')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reservation.code')->label('Reserva')->toggleable(),
                Tables\Columns\TextColumn::make('issued_at')->label('Emissao')->date()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')->label('Pago')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN'),
                Tables\Columns\TextColumn::make('balance_amount')->label('Saldo')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Invoice $record): string => route('invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    private static function updateTotal(Set $set, mixed $discount, mixed $subtotal, mixed $tax): null
    {
        $set('total_amount', max(0, (float) ($subtotal ?: 0) - (float) ($discount ?: 0) + (float) ($tax ?: 0)));

        return null;
    }
}
