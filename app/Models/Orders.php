<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'status',
        'total',
        'order',
        'delivery_type',
        'delivery_address',
        'payment_timing',
        'paid',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'paid' => 'boolean',
    ];
}