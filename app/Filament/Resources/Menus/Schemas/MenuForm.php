<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Descrição')
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Preço')
                    ->numeric()
                    ->prefix('R$')
                    ->required()
                    ->minValue(0),

                TextInput::make('original_price')
                    ->label('Preço original')
                    ->numeric()
                    ->prefix('R$')
                    ->minValue(0),

                TextInput::make('category')
                    ->label('Categoria')
                    ->required()
                    ->maxLength(255),

                TextInput::make('size')
                    ->label('Tamanho')
                    ->maxLength(255),

                Toggle::make('is_available')
                    ->label('Disponível')
                    ->default(true),
            ]);
    }
}
