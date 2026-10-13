<?php

namespace App\Filament\Resources\Whatsapps\Pages;

use App\Filament\Resources\Whatsapps\WhatsappResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhatsapps extends ListRecords
{
    protected static string $resource = WhatsappResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
