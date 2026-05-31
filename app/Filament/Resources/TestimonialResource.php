<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\TenantAccount;
use App\Models\Testimonial;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Testimonial::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $modelLabel = 'Testemunho';
    protected static ?string $pluralModelLabel = 'Testemunhos';
    protected static ?string $permissionModule = 'testimonial';

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
            Forms\Components\Section::make('Testemunho')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('author')->label('Autor')->required()->maxLength(255),
                    Forms\Components\TextInput::make('source')->label('Origem')->maxLength(255),
                    Forms\Components\TextInput::make('rating')->label('Avaliação')->numeric()->minValue(1)->maxValue(5),
                    Forms\Components\TextInput::make('sort_order')->label('Ordem')->numeric()->default(0),
                    Forms\Components\Textarea::make('text')->label('Texto')->rows(5)->required()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('tenantAccount.name')->label('Tenant')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('author')->label('Autor')->searchable(),
                Tables\Columns\TextColumn::make('rating')->label('Avaliação')->badge(),
                Tables\Columns\TextColumn::make('source')->label('Origem')->toggleable(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
