<?php

namespace App\Services;

use App\Models\Orders;
use App\Services\ConversationService;
use App\Events\OrderCreated;

class OrderService
{
    public static function handle(array $datas): void
    {
        $remoteJid = $datas['remoteJid'];
        $customerOrder = $datas['order'];
        $name = $datas['name'];
        $phone = $datas['phone'];
        $deliveryType = $datas[' deliveryType'];
        $paymentTiming = $datas[' paymentTiming'];
        $deliveryAddress = $datas['deliveryAddress'];
        $total = $datas['total'];

        $order = Orders::create([
            'customer_name'    => $name,
            'customer_phone'   => $phone,
            'status'           => 'pending',
            'total'            => $total,
            'order'            => $customerOrder,
            'delivery_type'    => $deliveryType,
            'delivery_address' => empty($deliveryAddress)
                || $deliveryAddress === 'null' ? null : $deliveryAddress,
            'payment_timing'   => $paymentTiming,
            'paid'             => false,
        ]);

        OrderCreated::dispatch($order->id);

        ConversationService::set($remoteJid);
    }
}