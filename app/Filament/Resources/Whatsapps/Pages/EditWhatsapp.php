<?php

namespace App\Filament\Resources\Whatsapps\Pages;

use App\Filament\Resources\Whatsapps\WhatsappResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhatsapp extends EditRecord
{
    protected static string $resource = WhatsappResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
