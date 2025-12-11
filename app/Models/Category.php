<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the products for the category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the icon URL attribute, ensuring it points to the backend domain.
     */
    public function getIconAttribute($value)
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
