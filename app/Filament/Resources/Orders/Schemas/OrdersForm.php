<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrdersForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('customer_name')
                    ->label('Nome do cliente')
                    ->required()
                    ->maxLength(255),

                TextInput::make('customer_phone')
                    ->label('Telefone')
                    ->tel()
                    ->required()
                    ->maxLength(20),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pendente',
                        'preparing' => 'Preparando',
                        'ready' => 'Pronto',
                        'completed' => 'Concluído',
                        'cancelled' => 'Cancelado',
                    ])
                    ->required(),

                TextInput::make('total')
                    ->label('Total')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),

                Textarea::make('order')
                    ->label('Pedido')
                    ->required()
                    ->rows(5),

                Select::make('delivery_type')
                    ->label('Tipo de entrega')
                    ->options([
                        'delivery' => 'Entrega',
                        'pickup' => 'Retirada',
                    ])
                    ->required(),

                Textarea::make('delivery_address')
                    ->label('Endereço de entrega')
                    ->rows(3),

                Select::make('payment_timing')
                    ->label('Momento do pagamento')
                    ->options([
                        'local' => 'No local',
                        'now' => 'Agora',
                    ])
                    ->required(),

                Toggle::make('paid')
                    ->label('Pago'),
            ]);
    }
}