<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\DailyChecklistResource\Pages;
use App\Models\DailyChecklist;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyChecklistResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = DailyChecklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Operacional';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Checklist diaria';

    protected static ?string $pluralModelLabel = 'Checklists diarias';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Supervisao diaria')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\Select::make('staff_member_id')->label('Responsavel')->relationship('staffMember', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))->searchable()->preload(),
                    Forms\Components\Select::make('room_id')->label('Quarto')->relationship('room', 'name', modifyQueryUsing: fn ($query) => $query->where('property_id', TenantContext::propertyId()))->searchable()->preload(),
                    Forms\Components\DatePicker::make('checklist_date')->label('Data')->default(now())->required(),
                    Forms\Components\Select::make('area')
                        ->label('Area')
                        ->options([
                            'recepcao' => 'Recepcao',
                            'limpeza' => 'Limpeza',
                            'limpeza_quarto' => 'Limpeza de quarto',
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
                    Forms\Components\FileUpload::make('evidence_photo_path')
                        ->label('Fotografia de prova')
                        ->image()
                        ->directory('checklist-evidence')
                        ->visibility('public')
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('evidence_latitude')->label('Latitude')->numeric(),
                    Forms\Components\TextInput::make('evidence_longitude')->label('Longitude')->numeric(),
                    Forms\Components\TextInput::make('evidence_qr_code')->label('Código QR')->maxLength(255),
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
                Tables\Columns\TextColumn::make('room.name')->label('Quarto')->toggleable(),
                Tables\Columns\TextColumn::make('staffMember.name')->label('Responsavel')->toggleable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('completed_at')->label('Concluido')->dateTime()->toggleable(),
                Tables\Columns\ImageColumn::make('evidence_photo_path')->label('Prova')->disk('public')->toggleable(),
                Tables\Columns\TextColumn::make('completedBy.name')->label('Concluido por')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('evidence_qr_code')->label('QR')->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListDailyChecklists::route('/'),
            'create' => Pages\CreateDailyChecklist::route('/create'),
            'edit' => Pages\EditDailyChecklist::route('/{record}/edit'),
        ];
    }
}
