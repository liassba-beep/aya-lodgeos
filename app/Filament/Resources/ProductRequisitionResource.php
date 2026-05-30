<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\ProductRequisitionResource\Pages;
use App\Models\ProductRequisition;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductRequisitionResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = ProductRequisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Requisicao';

    protected static ?string $pluralModelLabel = 'Requisicoes de produtos';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Requisicao')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('stock_item_id')->label('Produto')->relationship('stockItem', 'name')->required(),
                    Forms\Components\TextInput::make('quantity')->label('Quantidade')->numeric()->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'requested' => 'Solicitada',
                            'approved' => 'Aprovada',
                            'delivered' => 'Entregue',
                            'rejected' => 'Rejeitada',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('stockItem.name')->label('Produto')->searchable(),
                Tables\Columns\TextColumn::make('quantity')->label('Qtd'),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Solicitado por')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->where('property_id', $propertyId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductRequisitions::route('/'),
            'edit' => Pages\EditProductRequisition::route('/{record}/edit'),
        ];
    }
}
