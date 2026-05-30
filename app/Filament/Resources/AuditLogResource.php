<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasResourcePermissions;
use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use App\Support\TenantContext;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = AuditLog::class;

    protected static ?string $permissionModule = 'audit-log';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'SaaS';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Auditoria';

    protected static ?string $pluralModelLabel = 'Auditoria';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Data')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Utilizador')->placeholder('Sistema')->searchable(),
                Tables\Columns\TextColumn::make('event')->label('Evento')->badge()->sortable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Modulo')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('auditable_id')->label('Registo')->sortable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
