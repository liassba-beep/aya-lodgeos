<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\RoomType;
use App\Models\TenantAccount;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoomTypeResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = RoomType::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $modelLabel = 'Tipo de quarto';
    protected static ?string $pluralModelLabel = 'Tipos de quarto';
    protected static ?string $navigationLabel = 'Tipos de quarto';
    protected static ?string $permissionModule = 'room-type';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Tenant')
                ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(fn (): array => TenantAccount::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                ]),
            Forms\Components\Hidden::make('tenant_id')
                ->default(fn (): ?int => TenantContext::tenantAccount()?->id)
                ->visible(fn (): bool => auth()->user()?->role !== 'super_admin'),
            Forms\Components\Section::make('Quarto público')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(255),
                    Forms\Components\TextInput::make('capacity')->label('Capacidade')->numeric()->minValue(1)->required(),
                    Forms\Components\TextInput::make('price_from')->label('Preço desde')->numeric()->prefix('MZN')->required(),
                    Forms\Components\TextInput::make('sort_order')->label('Ordem')->numeric()->default(0),
                    Forms\Components\FileUpload::make('photo')
                        ->label('Foto')
                        ->image()
                        ->directory('room-types')
                        ->visibility('public')
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->label('Descrição')->rows(4)->columnSpanFull(),
                    Forms\Components\TagsInput::make('amenities_json')
                        ->label('Inclui')
                        ->placeholder('WC privativo')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->label('Foto')->disk('public')->square(),
                Tables\Columns\TextColumn::make('tenantAccount.name')->label('Tenant')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('name')->label('Nome')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('capacity')->label('Cap.')->sortable(),
                Tables\Columns\TextColumn::make('price_from')->label('Desde')->money('MZN')->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordem')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit' => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}
