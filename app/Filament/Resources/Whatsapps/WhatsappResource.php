<?php

namespace App\Filament\Resources\Whatsapps;

use App\Filament\Resources\Whatsapps\Pages\CreateWhatsapp;
use App\Filament\Resources\Whatsapps\Pages\EditWhatsapp;
use App\Filament\Resources\Whatsapps\Pages\ListWhatsapps;
use App\Filament\Resources\Whatsapps\Pages\Whatsapp as WhatsappPage;
use App\Filament\Resources\Whatsapps\Schemas\WhatsappForm;
use App\Filament\Resources\Whatsapps\Tables\WhatsappsTable;
use App\Models\Whatsapp;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhatsappResource extends Resource
{
    protected static ?string $model = Whatsapp::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Whatsapp';

    public static function form(Schema $schema): Schema
    {
        return WhatsappForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhatsappsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => WhatsappPage::route('/'),
        ];
    }
}
