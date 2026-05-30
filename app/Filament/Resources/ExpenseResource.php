<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use App\Models\StockItem;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Despesa';

    protected static ?string $pluralModelLabel = 'Despesas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Despesa')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('category')
                        ->label('Categoria')
                        ->live()
                        ->options([
                            'energia' => 'Energia',
                            'agua' => 'Agua',
                            'salarios' => 'Salarios',
                            'consumiveis' => 'Consumiveis',
                            'manutencao' => 'Manutencao',
                            'stock' => 'Stock',
                            'outros' => 'Outros',
                        ])
                        ->required(),
                    Forms\Components\Select::make('stock_item_id')
                        ->label('Artigo de stock')
                        ->relationship('stockItem', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (Get $get): bool => $get('category') === 'stock')
                        ->required(fn (Get $get): bool => $get('category') === 'stock')
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $item = $state ? StockItem::find($state) : null;
                            $set('stock_unit_cost', $item?->unit_cost ?? 0);
                        }),
                    Forms\Components\TextInput::make('stock_quantity')
                        ->label('Quantidade para stock')
                        ->numeric()
                        ->minValue(0.01)
                        ->visible(fn (Get $get): bool => $get('category') === 'stock')
                        ->required(fn (Get $get): bool => $get('category') === 'stock'),
                    Forms\Components\TextInput::make('stock_unit_cost')
                        ->label('Custo unitario')
                        ->numeric()
                        ->prefix('MZN')
                        ->visible(fn (Get $get): bool => $get('category') === 'stock')
                        ->required(fn (Get $get): bool => $get('category') === 'stock'),
                    Forms\Components\TextInput::make('supplier')->label('Fornecedor')->maxLength(255),
                    Forms\Components\TextInput::make('amount')->label('Valor')->numeric()->prefix('MZN')->required(),
                    Forms\Components\DatePicker::make('expense_date')->label('Data')->default(now())->required(),
                    Forms\Components\Select::make('payment_method')
                        ->label('Metodo de pagamento')
                        ->options([
                            'cash' => 'Dinheiro',
                            'mpesa' => 'M-Pesa',
                            'emola' => 'e-Mola',
                            'bank_transfer' => 'Transferencia',
                            'card' => 'Cartao',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendente',
                            'approved' => 'Aprovada',
                            'paid' => 'Paga',
                            'rejected' => 'Rejeitada',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('reference')->label('Referencia')->maxLength(255),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Categoria')->badge(),
                Tables\Columns\TextColumn::make('supplier')->label('Fornecedor')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('Valor')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN')->sortable(),
                Tables\Columns\TextColumn::make('payment_method')->label('Metodo')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Categoria')->options([
                    'energia' => 'Energia',
                    'agua' => 'Agua',
                    'salarios' => 'Salarios',
                    'consumiveis' => 'Consumiveis',
                    'manutencao' => 'Manutencao',
                    'stock' => 'Stock',
                    'outros' => 'Outros',
                ]),
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}
