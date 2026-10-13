<?php

namespace App\Filament\Resources\Menus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('size')
                    ->label('Tamanho')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('original_price')
                    ->label('Preço original')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('discount_percentage')
                    ->label('Desconto')
                    ->suffix('%')
                    ->placeholder('-')
                    ->sortable(),

                IconColumn::make('is_available')
                    ->label('Disponível')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_on_sale')
                    ->label('Promoção')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}