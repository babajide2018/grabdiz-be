<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'product_images';

    protected $fillable = [
        'product_id',
        'image_url',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'sort_order' => 'integer',
        'is_primary' => 'boolean',
    ];

    /**
     * Get the product that owns the image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the image URL attribute, ensuring it points to the backend domain.
     */
    public function getImageUrlAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        // If already a full URL, return as is
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // If relative path, make it absolute pointing to backend
        $appUrl = config('app.url', env('APP_URL', 'https://api.grabdiz.co.uk'));
        return rtrim($appUrl, '/') . '/' . ltrim($value, '/');
    }
}
