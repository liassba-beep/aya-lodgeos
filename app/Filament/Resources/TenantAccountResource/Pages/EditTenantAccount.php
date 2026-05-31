<?php

namespace App\Filament\Resources\TenantAccountResource\Pages;

use App\Filament\Resources\TenantAccountResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTenantAccount extends EditRecord
{
    protected static string $resource = TenantAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createInitialAccess')
                ->label('Criar acesso')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->fillForm(fn (): array => [
                    'property_name' => $this->record->properties()->orderBy('id')->value('name') ?: $this->record->name,
                    'owner_email' => $this->record->billing_email,
                    'owner_phone' => $this->record->billing_phone,
                ])
                ->form(TenantAccountResource::initialAccessForm())
                ->action(function (array $data): void {
                    TenantAccountResource::createInitialAccess($this->record, $data);

                    Notification::make()
                        ->title('Acesso inicial criado')
                        ->body('O proprietário já pode entrar no painel web do tenant.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
