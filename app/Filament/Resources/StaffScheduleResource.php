<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\StaffScheduleResource\Pages;
use App\Models\StaffMember;
use App\Models\StaffSchedule;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StaffScheduleResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = StaffSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Colaboradores';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Escala mensal';

    protected static ?string $pluralModelLabel = 'Escalas mensais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Escala')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('staff_member_id')
                        ->label('Colaborador')
                        ->relationship('staffMember', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $staffMember = $state ? StaffMember::find($state) : null;
                            $set('property_id', $staffMember?->property_id);
                        })
                        ->required(),
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\DatePicker::make('schedule_month')
                        ->label('Mês da escala')
                        ->default(now()->startOfMonth())
                        ->required(),
                    Forms\Components\DatePicker::make('shift_date')
                        ->label('Dia')
                        ->default(now())
                        ->required(),
                    Forms\Components\TimePicker::make('starts_at')
                        ->label('Entrada')
                        ->seconds(false),
                    Forms\Components\TimePicker::make('ends_at')
                        ->label('Saída')
                        ->seconds(false),
                    Forms\Components\Select::make('shift_type')
                        ->label('Turno')
                        ->options([
                            'morning' => 'Manhã',
                            'afternoon' => 'Tarde',
                            'night' => 'Noite',
                            'normal' => 'Normal',
                            'off' => 'Folga',
                        ])
                        ->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'planned' => 'Planeada',
                            'confirmed' => 'Confirmada',
                            'changed' => 'Alterada',
                            'missed' => 'Faltou',
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
                Tables\Columns\TextColumn::make('schedule_month')->label('Mês')->date('m/Y')->sortable(),
                Tables\Columns\TextColumn::make('shift_date')->label('Dia')->date()->sortable(),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Colaborador')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('shift_type')->label('Turno')->badge(),
                Tables\Columns\TextColumn::make('starts_at')->label('Entrada'),
                Tables\Columns\TextColumn::make('ends_at')->label('Saída'),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('staff_member_id')
                    ->label('Colaborador')
                    ->relationship('staffMember', 'name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'planned' => 'Planeada',
                        'confirmed' => 'Confirmada',
                        'changed' => 'Alterada',
                        'missed' => 'Faltou',
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
            'index' => Pages\ListStaffSchedules::route('/'),
            'create' => Pages\CreateStaffSchedule::route('/create'),
            'edit' => Pages\EditStaffSchedule::route('/{record}/edit'),
        ];
    }
}
