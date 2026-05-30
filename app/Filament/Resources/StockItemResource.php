<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\StockItemResource\Pages;
use App\Models\StockItem;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockItemResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = StockItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Stock';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Artigo';

    protected static ?string $pluralModelLabel = 'Artigos de stock';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Artigo')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Categoria')
                        ->options([
                            'consumable' => 'Consumivel',
                            'asset' => 'Patrimonial',
                            'food' => 'Alimentos e bebidas',
                            'cleaning' => 'Limpeza',
                            'amenity' => 'Amenity',
                            'linen' => 'Roupa de cama/toalhas',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('unit')->label('Unidade')->default('un')->required()->maxLength(30),
                    Forms\Components\TextInput::make('unit_cost')->label('Custo unitario')->numeric()->prefix('MZN')->required(),
                    Forms\Components\TextInput::make('quantity_on_hand')->label('Quantidade atual')->numeric()->required(),
                    Forms\Components\TextInput::make('minimum_quantity')->label('Stock minimo')->numeric()->required(),
                    Forms\Components\TextInput::make('location')->label('Local')->maxLength(255),
                    Forms\Components\Select::make('status')->label('Estado')->options(['active' => 'Activo', 'inactive' => 'Inactivo'])->required(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Artigo')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Categoria')->badge(),
                Tables\Columns\TextColumn::make('quantity_on_hand')->label('Qtd. atual')->sortable(),
                Tables\Columns\TextColumn::make('minimum_quantity')->label('Minimo')->sortable(),
                Tables\Columns\TextColumn::make('unit_cost')->label('Custo')->formatStateUsing(fn ($state): string => number_format((float) $state, 2).' MZN'),
                Tables\Columns\IconColumn::make('needs_restock')
                    ->label('Repor')
                    ->state(fn (StockItem $record): bool => (float) $record->quantity_on_hand <= (float) $record->minimum_quantity)
                    ->boolean(),
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
            'index' => Pages\ListStockItems::route('/'),
            'create' => Pages\CreateStockItem::route('/create'),
            'edit' => Pages\EditStockItem::route('/{record}/edit'),
        ];
    }
}
