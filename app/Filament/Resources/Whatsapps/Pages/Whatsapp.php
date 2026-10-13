<?php

namespace App\Filament\Resources\Whatsapps\Pages;

use App\Filament\Resources\Whatsapps\WhatsappResource;
use Filament\Resources\Pages\Page;

class Whatsapp extends Page
{
    protected static string $resource = WhatsappResource::class;

    protected string $view = 'filament.resources.whatsapps.pages.whatsapp';
}
