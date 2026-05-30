<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\InvoiceLineResource\Pages;
use App\Models\InvoiceLine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvoiceLineResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = InvoiceLine::class;
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Linha de factura';
    protected static ?string $pluralModelLabel = 'Linhas de factura';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Linha')->columns(2)->schema([
            Forms\Components\Select::make('invoice_id')->label('Fatura')->relationship('invoice', 'number')->required(),
            Forms\Components\TextInput::make('description')->label('Descricao')->required(),
            Forms\Components\TextInput::make('quantity')->label('Quantidade')->numeric()->required(),
            Forms\Components\TextInput::make('unit_price')->label('Preço unitário')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('tax_rate')->label('IVA')->suffix('%')->numeric()->required(),
            Forms\Components\TextInput::make('line_total')->label('Total')->prefix('MZN')->numeric()->disabled()->dehydrated(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('invoice.number')->label('Fatura'),
            Tables\Columns\TextColumn::make('description')->label('Descricao')->searchable(),
            Tables\Columns\TextColumn::make('quantity')->label('Qtd'),
            Tables\Columns\TextColumn::make('line_total')->label('Total')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getPages(): array { return ['index' => Pages\ListInvoiceLines::route('/'), 'create' => Pages\CreateInvoiceLine::route('/create'), 'edit' => Pages\EditInvoiceLine::route('/{record}/edit')]; }
}
