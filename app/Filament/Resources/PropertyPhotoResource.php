<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\PropertyPhotoResource\Pages;
use App\Models\PropertyPhoto;
use App\Models\TenantAccount;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyPhotoResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = PropertyPhoto::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $modelLabel = 'Foto do website';
    protected static ?string $pluralModelLabel = 'Galeria do website';
    protected static ?string $navigationLabel = 'Galeria';
    protected static ?string $permissionModule = 'property-photo';

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
            Forms\Components\Section::make('Foto')
                ->columns(2)
                ->schema([
                    Forms\Components\FileUpload::make('path')
                        ->label('Imagem')
                        ->image()
                        ->directory('property-photos')
                        ->visibility('public')
                        ->downloadable()
                        ->openable()
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('alt')
                        ->label('Texto alternativo')
                        ->helperText('Descreve a imagem para SEO e acessibilidade.')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('category')
                        ->label('Categoria')
                        ->options(PropertyPhoto::categoryOptions())
                        ->required(),
                    Forms\Components\TextInput::make('caption')
                        ->label('Legenda')
                        ->maxLength(255),
                    Forms\Components\TextInput::make('sort_order')
                        ->label('Ordem')
                        ->numeric()
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('path')->label('Foto')->disk('public')->square(),
                Tables\Columns\TextColumn::make('tenantAccount.name')->label('Tenant')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('alt')->label('Descrição')->searchable(),
                Tables\Columns\TextColumn::make('category')->label('Categoria')->badge(),
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
            'index' => Pages\ListPropertyPhotos::route('/'),
            'create' => Pages\CreatePropertyPhoto::route('/create'),
            'edit' => Pages\EditPropertyPhoto::route('/{record}/edit'),
        ];
    }
}
