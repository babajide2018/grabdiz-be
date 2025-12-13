<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'original_price',
        'image',
        'category_id',
        'in_stock',
        'quantity',
        'rating',
        'reviews_count',
        'featured',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'in_stock' => 'boolean',
        'quantity' => 'integer',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the variants for the product.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the cart items for the product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the reviews for the product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the images for the product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get the image URL attribute, ensuring it points to the backend domain.
     */
    public function getImageAttribute($value)
    {
        if (empty($value)) {
            return $value;
        }

        // If already a full URL pointing to frontend domain, convert to backend domain
        if (str_starts_with($value, 'https://demo.grabdiz.co.uk/') || str_starts_with($value, 'http://demo.grabdiz.co.uk/')) {
            $path = str_replace(['https://demo.grabdiz.co.uk/', 'http://demo.grabdiz.co.uk/'], '', $value);
            $appUrl = config('app.url', env('APP_URL', 'https://api.grabdiz.co.uk'));
            return rtrim($appUrl, '/') . '/' . ltrim($path, '/');
        }

        // If already a full URL pointing to backend domain, return as is
        if (str_starts_with($value, 'https://api.grabdiz.co.uk/') || str_starts_with($value, 'http://api.grabdiz.co.uk/')) {
            return $value;
        }

        // If relative path, make it absolute pointing to backend
        if (str_starts_with($value, '/') || str_starts_with($value, 'storage/')) {
            $appUrl = config('app.url', env('APP_URL', 'https://api.grabdiz.co.uk'));
            return rtrim($appUrl, '/') . '/' . ltrim($value, '/');
        }

        // If already a full URL (other domain), return as is
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        // Default: treat as relative path
        $appUrl = config('app.url', env('APP_URL', 'https://api.grabdiz.co.uk'));
        return rtrim($appUrl, '/') . '/storage/' . ltrim($value, '/');
    }
}
