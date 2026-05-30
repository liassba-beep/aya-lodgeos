<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\SaasPlanResource\Pages;
use App\Models\SaasPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SaasPlanResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = SaasPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'SaaS';
    protected static ?string $modelLabel = 'Plano SaaS';
    protected static ?string $pluralModelLabel = 'Planos SaaS';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plano')->columns(3)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required(),
                Forms\Components\TextInput::make('code')->label('Codigo')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('monthly_price')->label('Mensalidade')->prefix('MZN')->numeric()->required(),
                Forms\Components\TextInput::make('property_limit')->label('Limite propriedades')->numeric(),
                Forms\Components\TextInput::make('user_limit')->label('Limite utilizadores')->numeric(),
                Forms\Components\Select::make('status')->label('Estado')->options(['active' => 'Ativo', 'inactive' => 'Inativo'])->required(),
                Forms\Components\KeyValue::make('features')->label('Funcionalidades')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Plano')->searchable(),
            Tables\Columns\TextColumn::make('monthly_price')->label('Mensalidade')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('property_limit')->label('Propriedades')->placeholder('Ilimitado'),
            Tables\Columns\TextColumn::make('user_limit')->label('Utilizadores')->placeholder('Ilimitado'),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSaasPlans::route('/'), 'create' => Pages\CreateSaasPlan::route('/create'), 'edit' => Pages\EditSaasPlan::route('/{record}/edit')];
    }
}
