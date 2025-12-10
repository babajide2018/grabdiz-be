<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderNotificationMail;
use App\Mail\OrderStatusUpdateMail;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class OrderController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create order and Stripe payment intent
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'billing_first_name' => 'required|string|max:100',
                'billing_last_name' => 'required|string|max:100',
                'billing_email' => 'required|email|max:255',
                'billing_phone' => 'nullable|string|max:20',
                'billing_address' => 'required|string',
                'billing_city' => 'required|string|max:100',
                'billing_postcode' => 'required|string|max:20',
                'billing_country' => 'required|string|max:100',
                'shipping_first_name' => 'nullable|string|max:100',
                'shipping_last_name' => 'nullable|string|max:100',
                'shipping_address' => 'nullable|string',
                'shipping_city' => 'nullable|string|max:100',
                'shipping_postcode' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                'shipping_method' => 'nullable|string|max:50',
                'payment_method' => 'required|string|in:card',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
                'message' => 'Please check your form data and try again.',
            ], 422);
        }

        // Validate Bedfordshire postcode
        $postcode = strtoupper(str_replace(' ', '', $request->billing_postcode));
        $bedfordshirePostcodes = [
            'MK40',
            'MK41',
            'MK42',
            'MK43',
            'MK44',
            'MK45', // Bedford area
            'LU1',
            'LU2',
            'LU3',
            'LU4',
            'LU5',
            'LU6',
            'LU7', // Luton area
            'SG15',
            'SG16',
            'SG17',
            'SG18',
            'SG19', // Sandy/Biggleswade area
        ];

        $isValidBedfordshirePostcode = false;
        foreach ($bedfordshirePostcodes as $validPrefix) {
            if (str_starts_with($postcode, $validPrefix)) {
                $isValidBedfordshirePostcode = true;
                break;
            }
        }

        if (!$isValidBedfordshirePostcode) {
            return response()->json([
                'success' => false,
                'error' => 'We currently only deliver to Bedfordshire addresses. Please ensure your postcode is from Bedfordshire (MK40-MK45, LU1-LU7, or SG15-SG19).',
            ], 422);
        }

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Authentication required',
                'message' => 'Please log in to place an order',
            ], 401);
        }

        // Get cart items
        $cartItems = Cart::where('user_id', $user->id)
            ->with(['product', 'variant'])
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'Cart is empty',
                'message' => 'Your cart is empty. Please add items before checkout.',
            ], 400);
        }

        // Calculate totals
        $subtotal = 0;
        $items = [];

        foreach ($cartItems as $cartItem) {
            $product = $cartItem->product;
            $variant = $cartItem->variant;

            $price = (float) $product->price;
            if ($variant && $variant->price_modifier) {
                $price += (float) $variant->price_modifier;
            }

            $itemSubtotal = $price * $cartItem->quantity;
            $subtotal += $itemSubtotal;

            $items[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $cartItem->quantity,
                'price' => $price,
                'subtotal' => $itemSubtotal,
            ];
        }

        $shippingCost = (float) ($request->shipping_cost ?? 0);
        $totalAmount = $subtotal + $shippingCost;

        DB::beginTransaction();
        try {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'status' => 'pending',
                'total_amount' => $totalAmount,
                'shipping_cost' => $shippingCost,
                'shipping_method' => $request->shipping_method,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'billing_first_name' => $request->billing_first_name,
                'billing_last_name' => $request->billing_last_name,
                'billing_email' => $request->billing_email,
                'billing_phone' => $request->billing_phone,
                'billing_address' => $request->billing_address,
                'billing_city' => $request->billing_city,
                'billing_postcode' => $request->billing_postcode,
                'billing_country' => $request->billing_country,
                'shipping_first_name' => $request->shipping_first_name ?? $request->billing_first_name,
                'shipping_last_name' => $request->shipping_last_name ?? $request->billing_last_name,
                'shipping_address' => $request->shipping_address ?? $request->billing_address,
                'shipping_city' => $request->shipping_city ?? $request->billing_city,
                'shipping_postcode' => $request->shipping_postcode ?? $request->billing_postcode,
                'shipping_country' => $request->shipping_country ?? $request->billing_country,
            ]);

            // Create order items
            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'variant_id' => $item['variant']?->id,
                    'product_name' => $item['product']->name,
                    'product_price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            // Create Stripe Payment Intent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($totalAmount * 100), // Convert to cents
                'currency' => 'gbp',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $user->id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Update order with payment intent ID
            $order->stripe_payment_intent_id = $paymentIntent->id;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order->load('items'),
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order. Please try again.',
            ], 500);
        }
    }

    /**
     * Get order details
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with('items.product', 'items.variant')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Get user's orders
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = Order::with('items')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Confirm payment and update order status (called from frontend after payment succeeds)
     */
    public function confirmPayment(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Verify payment with Stripe
        if (!$order->stripe_payment_intent_id) {
            return response()->json([
                'success' => false,
                'error' => 'No payment intent found for this order',
            ], 400);
        }

        try {
            $paymentIntent = PaymentIntent::retrieve($order->stripe_payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                // Update order status
                $order->payment_status = 'succeeded';
                $order->status = 'processing';
                $order->save();

                // Clear user's cart
                Cart::where('user_id', $order->user_id)->delete();

                // Send order confirmation email
                try {
                    Mail::to($order->billing_email)->send(new OrderConfirmationMail($order));
                    Log::info("Order confirmation email sent to {$order->billing_email} for order {$order->order_number}");
                } catch (\Exception $e) {
                    Log::error("Failed to send order confirmation email: " . $e->getMessage());
                }

                // Send admin notification
                try {
                    $adminEmail = config('services.admin_email', env('ADMIN_EMAIL'));
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new OrderNotificationMail($order));
                        Log::info("Order notification email sent to admin for order {$order->order_number}");
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to send admin notification email: " . $e->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Payment confirmed and order updated',
                    'data' => $order->load('items'),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Payment not yet succeeded. Status: ' . $paymentIntent->status,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error("Failed to confirm payment: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to verify payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send order confirmation emails (called after payment confirmation)
     */
    public function sendOrderEmails(Request $request, $id)
    {
        $user = $request->user();
        $order = Order::with('items', 'user')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Check if payment has succeeded via Stripe Payment Intent
        // If payment_status is still pending but we have a payment intent, check with Stripe
        if ($order->payment_status !== 'succeeded' && $order->stripe_payment_intent_id) {
            try {
                $paymentIntent = PaymentIntent::retrieve($order->stripe_payment_intent_id);

                if ($paymentIntent->status === 'succeeded') {
                    // Update order status if payment has succeeded
                    $order->payment_status = 'succeeded';
                    $order->status = 'processing';
                    $order->save();
                    Log::info("Order {$order->order_number} payment status updated to succeeded via sendOrderEmails");
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment not yet confirmed. Status: ' . $paymentIntent->status,
                    ], 400);
                }
            } catch (\Exception $e) {
                Log::error("Failed to check payment intent status: " . $e->getMessage());
                // If we can't verify, still try to send email if payment_status allows
                if ($order->payment_status !== 'succeeded') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment not yet confirmed',
                    ], 400);
                }
            }
        } elseif ($order->payment_status !== 'succeeded') {
            return response()->json([
                'success' => false,
                'message' => 'Payment not yet confirmed',
            ], 400);
        }

        // Send order confirmation email to customer
        try {
            Mail::to($order->billing_email)->send(new OrderConfirmationMail($order));
            Log::info("Order confirmation email sent to {$order->billing_email} for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order confirmation email: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send confirmation email: ' . $e->getMessage(),
            ], 500);
        }

        // Send order notification email to admin
        try {
            $adminEmail = config('services.admin_email', env('ADMIN_EMAIL'));
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new OrderNotificationMail($order));
                Log::info("Order notification email sent to admin for order {$order->order_number}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send admin notification email: " . $e->getMessage());
            // Don't fail the request if admin email fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Emails sent successfully',
        ]);
    }

    /**
     * Handle Stripe webhooks
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // Webhook secret is optional - if not configured, log warning but still process
        // (useful for local development; production should have webhook secret)
        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured - webhook verification skipped');
            // For local development, you can still process webhooks without verification
            // In production, you should always have a webhook secret
            try {
                $event = json_decode($payload, true);
                if (!isset($event['type'])) {
                    return response()->json(['error' => 'Invalid webhook payload'], 400);
                }
            } catch (\Exception $e) {
                Log::error('Failed to parse webhook payload: ' . $e->getMessage());
                return response()->json(['error' => 'Invalid webhook payload'], 400);
            }
        } else {
            // Verify webhook signature if secret is configured
            try {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        // Handle the event
        $eventType = is_array($event) ? $event['type'] : $event->type;
        $eventData = is_array($event) ? $event['data'] : $event->data;

        switch ($eventType) {
            case 'payment_intent.succeeded':
                $paymentIntent = is_array($eventData) ? (object)$eventData['object'] : $eventData->object;
                $this->handlePaymentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = is_array($eventData) ? (object)$eventData['object'] : $eventData->object;
                $this->handlePaymentFailed($paymentIntent);
                break;

            default:
                Log::info('Unhandled Stripe event type: ' . $eventType);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Handle successful payment
     */
    private function handlePaymentSucceeded($paymentIntent)
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)
            ->with('items', 'user')
            ->first();

        if ($order) {
            $order->payment_status = 'succeeded';
            $order->status = 'processing';
            $order->save();

            // Clear user's cart
            Cart::where('user_id', $order->user_id)->delete();

            // Send order confirmation email to customer
            try {
                Mail::to($order->billing_email)->send(new OrderConfirmationMail($order));
                Log::info("Order confirmation email sent to {$order->billing_email} for order {$order->order_number}");
            } catch (\Exception $e) {
                Log::error("Failed to send order confirmation email: " . $e->getMessage());
            }

            // Send order notification email to admin
            try {
                $adminEmail = config('services.admin_email', env('ADMIN_EMAIL'));
                if ($adminEmail) {
                    Mail::to($adminEmail)->send(new OrderNotificationMail($order));
                    Log::info("Order notification email sent to admin for order {$order->order_number}");
                } else {
                    Log::warning("Admin email not configured. Skipping admin notification for order {$order->order_number}");
                }
            } catch (\Exception $e) {
                Log::error("Failed to send admin notification email: " . $e->getMessage());
            }

            Log::info("Order {$order->order_number} payment succeeded");
        }
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed($paymentIntent)
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if ($order) {
            $order->payment_status = 'failed';
            $order->save();

            Log::info("Order {$order->order_number} payment failed");
        }
    }
    /**
     * Get all orders for admin
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'items.variant']);

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order = Order::with('items')->findOrFail($id);
        $previousStatus = $order->status;
        $newStatus = $request->status;

        // Only proceed if status actually changed
        if ($previousStatus === $newStatus) {
            return response()->json([
                'success' => true,
                'message' => 'Order status is already ' . $newStatus,
                'data' => $order,
            ]);
        }

        $order->status = $newStatus;
        $order->save();

        // Log status change to history
        try {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'changed_by' => $request->user()->id,
                'changed_by_type' => 'admin',
                'notes' => "Status changed from {$previousStatus} to {$newStatus}",
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to log order status history: " . $e->getMessage());
        }

        // Send email notification to customer
        try {
            Mail::to($order->billing_email)->send(new OrderStatusUpdateMail($order, $previousStatus, $newStatus));
            Log::info("Order status update email sent to {$order->billing_email} for order {$order->order_number} (Status: {$previousStatus} -> {$newStatus})");
        } catch (\Exception $e) {
            Log::error("Failed to send order status update email: " . $e->getMessage());
            // Don't fail the request if email fails, but log it
        }

        // Log status change
        Log::info("Order {$order->order_number} status updated from {$previousStatus} to {$newStatus} by admin user {$request->user()->id}");

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully. Customer has been notified via email.',
            'data' => $order,
        ]);
    }

    /**
     * Get order details for admin
     */
    public function adminShow($id)
    {
        $order = Order::with(['user', 'items.product', 'items.variant'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Get dashboard statistics for admin
     */
    public function dashboardStats()
    {
        try {
            // Get total counts
            $totalProducts = Product::count();
            $totalOrders = Order::count();
            $totalUsers = User::count();
            $totalCategories = Category::count();

            // Calculate total revenue (sum of all orders with payment_status = 'succeeded')
            $totalRevenue = Order::where('payment_status', 'succeeded')
                ->sum('total_amount');

            // Get pending orders count
            $pendingOrders = Order::where('status', 'pending')->count();

            // Get recent orders (last 10)
            $recentOrders = Order::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'orderNumber' => $order->order_number,
                        'user' => $order->user ? [
                            'firstName' => $order->user->first_name,
                            'lastName' => $order->user->last_name,
                            'email' => $order->user->email,
                        ] : null,
                        'totalAmount' => $order->total_amount,
                        'status' => $order->status,
                        'createdAt' => $order->created_at->toISOString(),
                    ];
                });

            // Get low stock products (quantity <= 10 or in_stock = false)
            $lowStockProducts = Product::where(function ($query) {
                $query->where('quantity', '<=', 10)
                    ->orWhere('in_stock', false);
            })
                ->orderBy('quantity', 'asc')
                ->limit(20)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'quantity' => $product->quantity,
                        'inStock' => $product->in_stock,
                        'in_stock' => $product->in_stock, // Also include snake_case for compatibility
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'totalProducts' => $totalProducts,
                    'totalOrders' => $totalOrders,
                    'totalUsers' => $totalUsers,
                    'totalCategories' => $totalCategories,
                    'totalRevenue' => (float) $totalRevenue,
                    'pendingOrders' => $pendingOrders,
                    'recentOrders' => $recentOrders,
                    'lowStockProducts' => $lowStockProducts,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch dashboard statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
