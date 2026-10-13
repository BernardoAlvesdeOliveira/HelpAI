<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'original_price',
        'category',
        'size',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function getDiscountPercentageAttribute(): ?int
    {
        if (
            $this->original_price === null ||
            (float) $this->original_price <= 0 ||
            (float) $this->original_price <= (float) $this->price
        ) {
            return null;
        }

        return (int) round(
            (
                ((float) $this->original_price - (float) $this->price)
                / (float) $this->original_price
            ) * 100
        );
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->discount_percentage !== null;
    }
}
