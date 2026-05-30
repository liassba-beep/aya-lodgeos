<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    use HasResourcePermissions;
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'SaaS';
    protected static ?string $modelLabel = 'Subscricao';
    protected static ?string $pluralModelLabel = 'Subscricoes';

    public static function form(Form $form): Form
    {
        return $form->schema([Forms\Components\Section::make('Subscricao')->columns(3)->schema([
            Forms\Components\Select::make('tenant_account_id')->label('Tenant')->relationship('tenantAccount', 'name')->required(),
            Forms\Components\Select::make('saas_plan_id')->label('Plano')->relationship('saasPlan', 'name'),
            Forms\Components\Select::make('status')->label('Estado')->options(['trial' => 'Trial', 'active' => 'Ativa', 'past_due' => 'Em atraso', 'cancelled' => 'Cancelada'])->required(),
            Forms\Components\DatePicker::make('starts_at')->label('Inicio'),
            Forms\Components\DatePicker::make('renews_at')->label('Renova em'),
            Forms\Components\DatePicker::make('ends_at')->label('Fim'),
            Forms\Components\TextInput::make('monthly_amount')->label('Mensalidade')->prefix('MZN')->numeric()->required(),
            Forms\Components\TextInput::make('billing_reference')->label('Referencia'),
            Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('tenantAccount.name')->label('Tenant')->searchable(),
            Tables\Columns\TextColumn::make('saasPlan.name')->label('Plano'),
            Tables\Columns\TextColumn::make('monthly_amount')->label('Mensalidade')->formatStateUsing(fn ($state) => number_format((float) $state, 2).' MZN'),
            Tables\Columns\TextColumn::make('renews_at')->label('Renova')->date(),
            Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        ])->actions([Tables\Actions\EditAction::make()->label('Editar')]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptions::route('/'), 'create' => Pages\CreateSubscription::route('/create'), 'edit' => Pages\EditSubscription::route('/{record}/edit')];
    }
}
