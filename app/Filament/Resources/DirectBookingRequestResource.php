<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\DirectBookingRequestResource\Pages;
use App\Models\DirectBookingRequest;
use App\Support\TenantContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DirectBookingRequestResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = DirectBookingRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Reservas directas';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'Pedido direto';

    protected static ?string $pluralModelLabel = 'Pedidos diretos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Pedido de reserva direta')
                ->columns(3)
                ->schema([
                    Forms\Components\Hidden::make('property_id')
                        ->default(fn (): ?int => TenantContext::propertyId()),
                    Forms\Components\TextInput::make('guest_name')->label('Hóspede')->required()->maxLength(255),
                    Forms\Components\TextInput::make('guest_phone')->label('Telefone')->tel()->maxLength(255),
                    Forms\Components\TextInput::make('guest_email')->label('Email')->email()->maxLength(255),
                    Forms\Components\DatePicker::make('check_in')->label('Entrada')->required(),
                    Forms\Components\DatePicker::make('check_out')->label('Saída')->required()->after('check_in'),
                    Forms\Components\TextInput::make('adults')->label('Adultos')->numeric()->minValue(1)->required(),
                    Forms\Components\TextInput::make('children')->label('Crianças')->numeric()->minValue(0)->required(),
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'new' => 'Novo',
                            'contacted' => 'Contactado',
                            'converted' => 'Convertido em reserva',
                            'lost' => 'Perdido',
                        ])
                        ->required(),
                    Forms\Components\Textarea::make('message')->label('Mensagem')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guest_name')->label('Hóspede')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('guest_phone')->label('Telefone')->searchable(),
                Tables\Columns\TextColumn::make('check_in')->label('Entrada')->date()->sortable(),
                Tables\Columns\TextColumn::make('check_out')->label('Saída')->date()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado')->dateTime()->sortable(),
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
            'index' => Pages\ListDirectBookingRequests::route('/'),
            'create' => Pages\CreateDirectBookingRequest::route('/create'),
            'edit' => Pages\EditDirectBookingRequest::route('/{record}/edit'),
        ];
    }
}
