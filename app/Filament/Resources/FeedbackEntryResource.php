<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\FeedbackEntryResource\Pages;
use App\Models\FeedbackEntry;
use App\Models\Property;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeedbackEntryResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = FeedbackEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Suporte';

    protected static ?string $modelLabel = 'Mensagem';

    protected static ?string $pluralModelLabel = 'Bugs e opiniões';

    protected static ?string $navigationLabel = 'Bugs e opiniões';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Origem')
                ->columns(2)
                ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                ->schema([
                    Forms\Components\Select::make('property_id')
                        ->label('Alojamento')
                        ->options(fn (): array => Property::query()
                            ->with('tenantAccount')
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (Property $property): array => [
                                $property->id => ($property->tenantAccount?->name ? $property->tenantAccount->name.' · ' : '').$property->name,
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                ]),
            Forms\Components\Hidden::make('property_id')
                ->default(fn (): ?int => TenantContext::propertyId())
                ->visible(fn (): bool => auth()->user()?->role !== 'super_admin'),
            Forms\Components\Section::make('Mensagem')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->options(FeedbackEntry::typeLabels())
                        ->default('bug')
                        ->required(),
                    Forms\Components\Select::make('priority')
                        ->label('Prioridade')
                        ->options(FeedbackEntry::priorityLabels())
                        ->default('normal')
                        ->required(),
                    Forms\Components\TextInput::make('title')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->label('Descrição')
                        ->rows(6)
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('screenshot_path')
                        ->label('Captura de ecrã ou anexo')
                        ->image()
                        ->directory('feedback')
                        ->visibility('public')
                        ->columnSpanFull(),
                ]),
            Forms\Components\Section::make('Triagem do master')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options(FeedbackEntry::statusLabels())
                        ->default('open')
                        ->disabled(fn (): bool => auth()->user()?->role !== 'super_admin')
                        ->dehydrated(fn (): bool => auth()->user()?->role === 'super_admin')
                        ->required(),
                    Forms\Components\DateTimePicker::make('resolved_at')
                        ->label('Resolvido em')
                        ->disabled(fn (): bool => auth()->user()?->role !== 'super_admin')
                        ->dehydrated(fn (): bool => auth()->user()?->role === 'super_admin'),
                    Forms\Components\Textarea::make('master_response')
                        ->label('Resposta do master')
                        ->helperText('Esta resposta fica visível para o tenant.')
                        ->rows(4)
                        ->disabled(fn (): bool => auth()->user()?->role !== 'super_admin')
                        ->dehydrated(fn (): bool => auth()->user()?->role === 'super_admin')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('tenantAccount.name')->label('Tenant')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('property.name')->label('Alojamento')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('user.name')->label('Enviado por')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (?string $state): string => FeedbackEntry::typeLabels()[$state] ?? (string) $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('title')->label('Título')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridade')
                    ->formatStateUsing(fn (?string $state): string => FeedbackEntry::priorityLabels()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'critical' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (?string $state): string => FeedbackEntry::statusLabels()[$state] ?? (string) $state)
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'done' => 'success',
                        'rejected' => 'danger',
                        'in_progress' => 'warning',
                        'planned' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tipo')->options(FeedbackEntry::typeLabels()),
                Tables\Filters\SelectFilter::make('priority')->label('Prioridade')->options(FeedbackEntry::priorityLabels()),
                Tables\Filters\SelectFilter::make('status')->label('Estado')->options(FeedbackEntry::statusLabels()),
                Tables\Filters\SelectFilter::make('tenant_account_id')
                    ->label('Tenant')
                    ->relationship('tenantAccount', 'name')
                    ->visible(fn (): bool => auth()->user()?->role === 'super_admin'),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Abrir')])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['tenantAccount', 'property', 'user'])
            ->when(TenantContext::propertyId(), fn (Builder $query, int $propertyId) => $query->where('property_id', $propertyId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeedbackEntries::route('/'),
            'create' => Pages\CreateFeedbackEntry::route('/create'),
            'edit' => Pages\EditFeedbackEntry::route('/{record}/edit'),
        ];
    }
}
