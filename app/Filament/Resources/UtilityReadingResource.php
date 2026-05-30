<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\UtilityReadingResource\Pages;
use App\Models\UtilityReading;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UtilityReadingResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = UtilityReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Leitura Credelec';

    protected static ?string $pluralModelLabel = 'Controlo Credelec';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Leitura')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('reading_date')->label('Data')->required(),
                    Forms\Components\TextInput::make('meter_number')->label('Contador'),
                    Forms\Components\TextInput::make('balance_kwh')->label('kWh')->numeric(),
                    Forms\Components\TextInput::make('balance_amount')->label('Saldo MZN')->numeric(),
                    Forms\Components\TextInput::make('qr_code')->label('QR'),
                    Forms\Components\FileUpload::make('photo_path')->label('Foto')->image()->disk('public')->directory('utility-readings')->downloadable()->openable()->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('reading_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reading_date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('meter_number')->label('Contador')->searchable(),
                Tables\Columns\TextColumn::make('balance_kwh')->label('kWh'),
                Tables\Columns\TextColumn::make('balance_amount')->label('MZN'),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Registado por')->toggleable(),
                Tables\Columns\ImageColumn::make('photo_path')->label('Foto')->disk('public')->toggleable(),
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
            'index' => Pages\ListUtilityReadings::route('/'),
            'edit' => Pages\EditUtilityReading::route('/{record}/edit'),
        ];
    }
}
