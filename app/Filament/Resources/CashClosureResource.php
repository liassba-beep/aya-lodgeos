<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\CashClosureResource\Pages;
use App\Models\CashClosure;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashClosureResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = CashClosure::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financeiro';
    protected static ?string $modelLabel = 'Fecho de caixa';
    protected static ?string $pluralModelLabel = 'Fechos de caixa';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Fecho de caixa')->columns(3)->schema([
            Forms\Components\Hidden::make('property_id')->default(fn () => TenantContext::propertyId()),
            Forms\Components\Select::make('staff_member_id')->label('Responsavel')->relationship('staffMember', 'name'),
            Forms\Components\DatePicker::make('closure_date')->label('Data')->default(now())->required(),
            Forms\Components\TextInput::make('opening_balance')->label('Abertura')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('cash_received')->label('Dinheiro')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('card_received')->label('Cartao/MPesa')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('expenses_paid')->label('Despesas pagas')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('counted_balance')->label('Contado')->prefix('MZN')->numeric()->required(),
            Forms\Components\Select::make('status')->label('Estado')->options(['draft' => 'Rascunho', 'submitted' => 'Submetido', 'approved' => 'Aprovado'])->required(),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('closure_date')->label('Data')->date()->sortable(),
            Tables\Columns\TextColumn::make('staffMember.name')->label('Responsavel'),
            Tables\Columns\TextColumn::make('expected_balance')->label('Esperado')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('counted_balance')->label('Contado')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('difference')->label('Diferenca')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->where('property_id', $propertyId));
    }

    public static function getPages(): array { return ['index' => Pages\ListCashClosures::route('/'), 'create' => Pages\CreateCashClosure::route('/create'), 'edit' => Pages\EditCashClosure::route('/{record}/edit')]; }
}
