<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use App\Models\TenantAccount;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PropertyResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Alojamento';

    protected static ?string $pluralModelLabel = 'Alojamentos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados do alojamento')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('tenant_account_id')
                            ->label('Tenant SaaS')
                            ->options(fn (): array => TenantAccount::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'guest_house' => 'Guest house',
                                'hotel' => 'Hotel',
                                'lodge' => 'Lodge',
                                'apartment' => 'Apartamento',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('invoice_logo_path')
                            ->label('Logotipo para facturas')
                            ->image()
                            ->directory('invoice-logos')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('legal_name')
                            ->label('Nome fiscal da instituição')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nuit')
                            ->label('NUIT')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->label('País')
                            ->default('Mozambique')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city')
                            ->label('Cidade')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('address')
                            ->label('Endereço')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Telefone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_phone')
                            ->label('Contacto na factura')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('invoice_email')
                            ->label('Email na factura')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('invoice_footer')
                            ->label('Rodapé da factura')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('cancellation_policy')
                            ->label('Política de cancelamento')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('deposit_percent')
                            ->label('Depósito de reserva')
                            ->suffix('%')
                            ->numeric()
                            ->default(50),
                        Forms\Components\Textarea::make('house_rules')
                            ->label('Regras da casa')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cleaning_interval_days')
                            ->label('Limpeza a cada X dias')
                            ->numeric()
                            ->default(3),
                        Forms\Components\KeyValue::make('room_inventory_template')
                            ->label('Inventário modelo do quarto')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('meals_and_services')
                            ->label('Refeições e serviços úteis')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Website público')
                    ->description('Dados usados no site público, SEO, WhatsApp e mapa deste tenant.')
                    ->relationship('tenantAccount')
                    ->visible(fn (string $operation): bool => $operation === 'edit')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp')
                            ->helperText('Use o formato internacional sem +, por exemplo 258842990406.')
                            ->tel()
                            ->maxLength(32),
                        Forms\Components\TextInput::make('address_label')
                            ->label('Morada pública')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude')
                            ->numeric(),
                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude')
                            ->numeric(),
                        Forms\Components\Textarea::make('directions_note')
                            ->label('Nota de direcção')
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('nearby_json')
                            ->label('Pontos próximos')
                            ->keyLabel('Ponto')
                            ->valueLabel('Distância')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('seo_title')
                            ->label('Título SEO')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('seo_description')
                            ->label('Descrição SEO')
                            ->rows(3),
                        Forms\Components\FileUpload::make('og_image')
                            ->label('Imagem de partilha')
                            ->image()
                            ->directory('website-og')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->image()
                            ->directory('website-favicons')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nuit')
                    ->label('NUIT')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Quartos')
                    ->counts('rooms')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'active' => 'Activo',
                        'inactive' => 'Inactivo',
                    ]),
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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(TenantContext::propertyId(), fn ($query, int $propertyId) => $query->whereKey($propertyId));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
