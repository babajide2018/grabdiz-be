<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'value',
        'price_modifier',
        'stock',
        'sort_order',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'stock' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns this variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
