<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// API root - show welcome message
Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Grabdiz API',
        'version' => '1.0.0',
        'endpoints' => [
            'categories' => '/api/categories',
            'products' => '/api/products',
            'auth' => '/api/auth/login',
        ],
    ]);
});

// Public routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'publicIndex']);
Route::get('/products/{id}', [ProductController::class, 'publicShow']);

// Review routes (public read, authenticated write)
Route::get('/products/{productId}/reviews', [ReviewController::class, 'index']);
Route::post('/products/{productId}/reviews', [ReviewController::class, 'store']);

// Auth routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Get current authenticated user
Route::middleware(['auth:sanctum'])->get('/auth/me', [AuthController::class, 'me']);

// Stripe webhook (must be public, no CSRF protection)
Route::post('/webhooks/stripe', [OrderController::class, 'webhook']);

// Test email route (for testing email configuration)
Route::get('/test-email', function () {
    try {
        $testEmail = 'ojobabajide2018@gmail.com';

        // Log current mail configuration (without password)
        \Log::info('Mail Config Test', [
            'mailer' => config('mail.default'),
            'host' => config('mail.mailers.smtp.host'),
            'port' => config('mail.mailers.smtp.port'),
            'encryption' => config('mail.mailers.smtp.encryption'),
            'username' => config('mail.mailers.smtp.username'),
            'from_address' => config('mail.from.address'),
        ]);

        Mail::raw('This is a test email from Grabdiz. If you receive this, your email configuration is working correctly!', function ($message) use ($testEmail) {
            $message->to($testEmail)
                ->subject('Test Email from Grabdiz');
        });

        return response()->json([
            'success' => true,
            'message' => 'Test email sent successfully to ' . $testEmail,
        ]);
    } catch (\Exception $e) {
        \Log::error('Email send failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to send test email: ' . $e->getMessage(),
            'config' => [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
            ],
        ], 500);
    }
});

// User routes (protected)
Route::middleware(['auth:sanctum'])->group(function () {

    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Order routes
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/confirm-payment', [OrderController::class, 'confirmPayment']);
    Route::post('/orders/{id}/send-emails', [OrderController::class, 'sendOrderEmails']);

    // User Profile routes
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/profile-picture', [UserController::class, 'uploadProfilePicture']);

    // Wishlist routes
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{productId}', [WishlistController::class, 'destroy']);
});

// Admin routes (protected)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('products', ProductController::class);
    Route::post('upload', [UploadController::class, 'store']);

    // Review management routes
    Route::get('reviews', [ReviewController::class, 'adminIndex']);
    Route::get('reviews/pending-count', [ReviewController::class, 'pendingCount']);
    Route::put('reviews/{reviewId}/status', [ReviewController::class, 'updateStatus']);
    Route::delete('reviews/{reviewId}', [ReviewController::class, 'destroy']);

    // Order management routes
    Route::get('orders', [OrderController::class, 'adminIndex']);
    Route::get('orders/{id}', [OrderController::class, 'adminShow']);
    Route::put('orders/{id}/status', [OrderController::class, 'updateStatus']);

    // Dashboard stats route
    Route::get('dashboard/stats', [OrderController::class, 'dashboardStats']);

    // User management routes
    Route::get('users', [UserController::class, 'adminIndex']);
});
