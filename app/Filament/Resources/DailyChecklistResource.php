<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyChecklistResource\Pages;
use App\Models\DailyChecklist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyChecklistResource extends Resource
{
    protected static ?string $model = DailyChecklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';

    protected static ?string $navigationGroup = 'Supervisao';

    protected static ?string $modelLabel = 'Checklist diaria';

    protected static ?string $pluralModelLabel = 'Checklists diarias';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Supervisao diaria')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('property_id')->label('Alojamento')->relationship('property', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('staff_member_id')->label('Responsavel')->relationship('staffMember', 'name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('checklist_date')->label('Data')->default(now())->required(),
                    Forms\Components\Select::make('area')
                        ->label('Area')
                        ->options([
                            'recepcao' => 'Recepcao',
                            'limpeza' => 'Limpeza',
                            'manutencao' => 'Manutencao',
                            'cozinha' => 'Cozinha',
                            'seguranca' => 'Seguranca',
                            'caixa' => 'Caixa',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('title')->label('Item')->required()->maxLength(255),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendente',
                            'done' => 'Concluido',
                            'failed' => 'Falhou',
                        ])
                        ->required(),
                    Forms\Components\DateTimePicker::make('completed_at')->label('Concluido em')->seconds(false),
                    Forms\Components\Textarea::make('evidence_note')->label('Evidencia')->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Notas')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('checklist_date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('area')->label('Area')->badge(),
                Tables\Columns\TextColumn::make('title')->label('Item')->searchable(),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Responsavel')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('completed_at')->label('Concluido')->dateTime()->toggleable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyChecklists::route('/'),
            'create' => Pages\CreateDailyChecklist::route('/create'),
            'edit' => Pages\EditDailyChecklist::route('/{record}/edit'),
        ];
    }
}
