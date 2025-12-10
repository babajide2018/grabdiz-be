# Laravel API Setup Guide

## Why Laravel?

✅ **Better ORM** - Eloquent is excellent and can replace Prisma
✅ **Built-in Auth** - Laravel Sanctum for API authentication
✅ **Better File Handling** - Excellent upload/storage system
✅ **More Maintainable** - Industry standard, great documentation
✅ **Works on Shared Hosting** - No persistent processes needed
✅ **Better for Complex Apps** - Perfect for e-commerce APIs

## Setup Steps

### 1. Install Laravel

```bash
cd api
composer create-project laravel/laravel .
```

Or if you want to install in the api directory:

```bash
composer create-project laravel/laravel api
```

### 2. Configure Database

Update `.env` in the `api` directory:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Or use `DATABASE_URL`:
```env
DATABASE_URL=mysql://user:pass@host:port/dbname
```

### 3. Configure Authentication

Laravel Sanctum is already included. Configure it:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4. API Structure

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── Admin/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── UploadController.php
│   │   │   └── CategoryController.php (public)
│   │   └── Middleware/
│   │       └── EnsureAdmin.php
│   └── Models/
│       ├── User.php
│       ├── Category.php
│       ├── Product.php
│       ├── ProductImage.php
│       └── ProductVariant.php
├── routes/
│   └── api.php
└── database/
    └── migrations/
```

### 5. Routes Structure

```php
// routes/api.php

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Admin routes (protected)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', Admin\CategoryController::class);
    Route::apiResource('products', Admin\ProductController::class);
    Route::post('upload', [Admin\UploadController::class, 'store']);
});
```

### 6. Models (Eloquent)

Laravel Eloquent will replace Prisma:

```php
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', ...];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
```

### 7. Deployment

**No Node.js server needed!** Just:
1. Upload Laravel files
2. Run `composer install --optimize-autoloader --no-dev`
3. Set up `.env`
4. Run migrations: `php artisan migrate`
5. Done!

### 8. Benefits Over Slim

- ✅ **Eloquent ORM** - Much better than raw PDO
- ✅ **Migrations** - Built-in database migrations
- ✅ **Validation** - Form request validation
- ✅ **File Storage** - Excellent file handling
- ✅ **Testing** - Built-in testing framework
- ✅ **Documentation** - Extensive Laravel docs
- ✅ **Ecosystem** - Tons of packages

### 9. Migration from Prisma

You can keep Prisma for migrations OR:
- Use Laravel migrations (recommended)
- Convert Prisma schema to Laravel migrations
- Use both (Prisma for schema, Laravel for queries)

### 10. Next Steps

1. Install Laravel in `api/` directory
2. Set up database connection
3. Create Eloquent models
4. Create API controllers
5. Set up routes
6. Configure Sanctum for auth
7. Test endpoints
8. Update Next.js frontend to call Laravel APIs

## Comparison: Slim vs Laravel

| Feature | Slim | Laravel |
|---------|------|---------|
| ORM | None (PDO) | Eloquent ✅ |
| Auth | Manual | Sanctum ✅ |
| Migrations | Manual | Built-in ✅ |
| Validation | Manual | Form Requests ✅ |
| File Upload | Manual | Storage System ✅ |
| Testing | Manual | Built-in ✅ |
| Learning Curve | Easy | Moderate |
| Ecosystem | Small | Large ✅ |
| Maintenance | More code | Less code ✅ |

**Verdict: Laravel is better for this project!**




## Why Laravel?

✅ **Better ORM** - Eloquent is excellent and can replace Prisma
✅ **Built-in Auth** - Laravel Sanctum for API authentication
✅ **Better File Handling** - Excellent upload/storage system
✅ **More Maintainable** - Industry standard, great documentation
✅ **Works on Shared Hosting** - No persistent processes needed
✅ **Better for Complex Apps** - Perfect for e-commerce APIs

## Setup Steps

### 1. Install Laravel

```bash
cd api
composer create-project laravel/laravel .
```

Or if you want to install in the api directory:

```bash
composer create-project laravel/laravel api
```

### 2. Configure Database

Update `.env` in the `api` directory:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Or use `DATABASE_URL`:
```env
DATABASE_URL=mysql://user:pass@host:port/dbname
```

### 3. Configure Authentication

Laravel Sanctum is already included. Configure it:

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 4. API Structure

```
api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── Admin/
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   └── UploadController.php
│   │   │   └── CategoryController.php (public)
│   │   └── Middleware/
│   │       └── EnsureAdmin.php
│   └── Models/
│       ├── User.php
│       ├── Category.php
│       ├── Product.php
│       ├── ProductImage.php
│       └── ProductVariant.php
├── routes/
│   └── api.php
└── database/
    └── migrations/
```

### 5. Routes Structure

```php
// routes/api.php

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Admin routes (protected)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', Admin\CategoryController::class);
    Route::apiResource('products', Admin\ProductController::class);
    Route::post('upload', [Admin\UploadController::class, 'store']);
});
```

### 6. Models (Eloquent)

Laravel Eloquent will replace Prisma:

```php
// app/Models/Product.php
class Product extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'price', ...];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
```

### 7. Deployment

**No Node.js server needed!** Just:
1. Upload Laravel files
2. Run `composer install --optimize-autoloader --no-dev`
3. Set up `.env`
4. Run migrations: `php artisan migrate`
5. Done!

### 8. Benefits Over Slim

- ✅ **Eloquent ORM** - Much better than raw PDO
- ✅ **Migrations** - Built-in database migrations
- ✅ **Validation** - Form request validation
- ✅ **File Storage** - Excellent file handling
- ✅ **Testing** - Built-in testing framework
- ✅ **Documentation** - Extensive Laravel docs
- ✅ **Ecosystem** - Tons of packages

### 9. Migration from Prisma

You can keep Prisma for migrations OR:
- Use Laravel migrations (recommended)
- Convert Prisma schema to Laravel migrations
- Use both (Prisma for schema, Laravel for queries)

### 10. Next Steps

1. Install Laravel in `api/` directory
2. Set up database connection
3. Create Eloquent models
4. Create API controllers
5. Set up routes
6. Configure Sanctum for auth
7. Test endpoints
8. Update Next.js frontend to call Laravel APIs

## Comparison: Slim vs Laravel

| Feature | Slim | Laravel |
|---------|------|---------|
| ORM | None (PDO) | Eloquent ✅ |
| Auth | Manual | Sanctum ✅ |
| Migrations | Manual | Built-in ✅ |
| Validation | Manual | Form Requests ✅ |
| File Upload | Manual | Storage System ✅ |
| Testing | Manual | Built-in ✅ |
| Learning Curve | Easy | Moderate |
| Ecosystem | Small | Large ✅ |
| Maintenance | More code | Less code ✅ |

**Verdict: Laravel is better for this project!**


