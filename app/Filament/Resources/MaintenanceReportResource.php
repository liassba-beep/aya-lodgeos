<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\MaintenanceReportResource\Pages;
use App\Models\MaintenanceReport;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaintenanceReportResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = MaintenanceReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Avaria';

    protected static ?string $pluralModelLabel = 'Avarias reportadas';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Avaria')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Titulo')->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'reported' => 'Reportada',
                            'in_progress' => 'Em resolucao',
                            'resolved' => 'Resolvida',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('priority')->label('Prioridade')->required(),
                    Forms\Components\TextInput::make('qr_code')->label('QR'),
                    Forms\Components\FileUpload::make('photo_path')->label('Foto')->image()->disk('public')->directory('maintenance-reports')->downloadable()->openable()->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Avaria')->searchable(),
                Tables\Columns\TextColumn::make('room.name')->label('Quarto')->toggleable(),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Reportado por')->toggleable(),
                Tables\Columns\TextColumn::make('priority')->label('Prioridade')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
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
            'index' => Pages\ListMaintenanceReports::route('/'),
            'edit' => Pages\EditMaintenanceReport::route('/{record}/edit'),
        ];
    }
}
