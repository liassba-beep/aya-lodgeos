<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationalTaskResource\Pages;
use App\Models\OperationalTask;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OperationalTaskResource extends Resource
{
    protected static ?string $model = OperationalTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Tarefa operacional';

    protected static ?string $pluralModelLabel = 'Tarefas operacionais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tarefa')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('room_id')
                        ->label('Quarto')
                        ->relationship('room', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('staff_member_id')
                        ->label('Responsavel')
                        ->relationship('staffMember', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options([
                            'housekeeping' => 'Limpeza',
                            'maintenance' => 'Manutencao',
                            'reception' => 'Recepcao',
                            'security' => 'Seguranca',
                            'kitchen' => 'Cozinha',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Titulo')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\DatePicker::make('due_date')
                        ->label('Data prevista'),
                    Forms\Components\TimePicker::make('due_time')
                        ->label('Hora prevista')
                        ->seconds(false),
                    Forms\Components\Select::make('priority')
                        ->label('Prioridade')
                        ->options([
                            'low' => 'Baixa',
                            'normal' => 'Normal',
                            'high' => 'Alta',
                            'urgent' => 'Urgente',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'pending' => 'Pendente',
                            'in_progress' => 'Em execucao',
                            'done' => 'Concluida',
                            'cancelled' => 'Cancelada',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Tarefa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('room.name')->label('Quarto')->toggleable(),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Responsavel')->toggleable(),
                Tables\Columns\TextColumn::make('due_date')->label('Data')->date()->sortable(),
                Tables\Columns\TextColumn::make('priority')->label('Prioridade')->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'done' => 'success',
                        'in_progress' => 'warning',
                        'cancelled' => 'gray',
                        default => 'danger',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendente',
                        'in_progress' => 'Em execucao',
                        'done' => 'Concluida',
                        'cancelled' => 'Cancelada',
                    ]),
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
            'index' => Pages\ListOperationalTasks::route('/'),
            'create' => Pages\CreateOperationalTask::route('/create'),
            'edit' => Pages\EditOperationalTask::route('/{record}/edit'),
        ];
    }
}
