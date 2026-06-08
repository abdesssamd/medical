<?php

namespace Modules\Logistics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockItem extends Model
{
    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'category',
        'is_high_value',
        'unit',
        'current_quantity',
        'minimum_quantity',
        'reorder_quantity',
        'unit_cost',
        'is_active',
    ];

    protected $casts = [
        'is_high_value' => 'boolean',
        'current_quantity' => 'decimal:2',
        'minimum_quantity' => 'decimal:2',
        'reorder_quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_item_id');
    }
}

