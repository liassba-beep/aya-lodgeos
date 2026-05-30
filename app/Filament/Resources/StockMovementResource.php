<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockItem;
use App\Models\StockMovement;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Movimento de stock';

    protected static ?string $pluralModelLabel = 'Movimentos de stock';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Movimento')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('stock_item_id')
                        ->label('Artigo')
                        ->relationship('stockItem', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $item = $state ? StockItem::find($state) : null;
                            $set('property_id', $item?->property_id);
                            $set('unit_cost', $item?->unit_cost ?? 0);
                        })
                        ->required(),
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'in' => 'Entrada',
                            'out' => 'Saida',
                            'adjustment' => 'Ajuste',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('quantity')->label('Quantidade')->numeric()->required(),
                    Forms\Components\TextInput::make('unit_cost')->label('Custo unitario')->numeric()->prefix('MZN')->required(),
                    Forms\Components\DatePicker::make('movement_date')->label('Data')->default(now())->required(),
                    Forms\Components\TextInput::make('reason')->label('Motivo')->maxLength(255),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('movement_date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('stockItem.name')->label('Artigo')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('quantity')->label('Quantidade')->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')->label('Custo')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN'),
                Tables\Columns\TextColumn::make('reason')->label('Motivo')->toggleable(),
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
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
        ];
    }
}
