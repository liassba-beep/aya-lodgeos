<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\TenantAccountResource\Pages;
use App\Models\TenantAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantAccountResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = TenantAccount::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'SaaS';
    protected static ?string $modelLabel = 'Tenant';
    protected static ?string $pluralModelLabel = 'Tenants';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tenant')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->live(onBlur: true)->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                Forms\Components\TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('billing_email')->label('Email de cobranca')->email(),
                Forms\Components\TextInput::make('billing_phone')->label('Telefone de cobranca'),
                Forms\Components\Select::make('status')->label('Estado')->options(['active' => 'Activo', 'suspended' => 'Suspenso'])->required(),
                Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->label('Slug'),
            Tables\Columns\TextColumn::make('properties_count')->label('Propriedades')->counts('properties'),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTenantAccounts::route('/'), 'create' => Pages\CreateTenantAccount::route('/create'), 'edit' => Pages\EditTenantAccount::route('/{record}/edit')];
    }
}
