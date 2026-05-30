<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Models\Expense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Despesa';

    protected static ?string $pluralModelLabel = 'Despesas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Despesa')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('property_id')->label('Alojamento')->relationship('property', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('category')
                        ->label('Categoria')
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
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
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
