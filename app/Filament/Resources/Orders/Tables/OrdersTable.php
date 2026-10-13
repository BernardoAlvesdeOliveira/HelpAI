<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_phone')
                    ->label('Telefone')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'pendente',
                        'preparing' => 'preparando',
                        'ready' => 'pronto',
                        'completed' => 'concluido',
                        'cancelled' => 'cancelado',
                        default => $state,
                    }),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('delivery_type')
                    ->label('Entrega')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pickup' => 'Pegar',
                        'delivery' => 'Entregar',
                        default => $state,
                    }),

                TextColumn::make('payment_timing')
                    ->label('Pagamento')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'local' => 'local',
                        'now' => 'agora',
                        default => $state,
                    }),

                IconColumn::make('paid')
                    ->label('Pago')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])

            // Mais antigo → mais recente
            ->defaultSort('created_at', 'desc')

            ->filters([
                //
            ])
            
            // websocket
            ->socket(
                channel: 'orders',
                event: 'OrderCreated',
            );
    }
}