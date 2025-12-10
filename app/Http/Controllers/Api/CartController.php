<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Get the authenticated user's cart with product details.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $cartItems = Cart::where('user_id', $user->id)
            ->with(['product.category', 'variant'])
            ->get();

        $items = $cartItems->map(function ($item) {
            $product = $item->product;
            $variant = $item->variant;

            // Calculate price (product price + variant modifier if exists)
            $price = (float) $product->price;
            if ($variant && $variant->price_modifier) {
                $price += (float) $variant->price_modifier;
            }

            return [
                'id' => (string) $item->id, // Cart item ID
                'productId' => (string) $product->id, // Product ID for frontend checks
                'name' => $product->name,
                'price' => $price,
                'image' => $product->image ?? '',
                'quantity' => $item->quantity,
                'category' => $product->category->name ?? '',
                'variant' => $variant ? [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'value' => $variant->value,
                ] : null,
            ];
        });

        $totalItems = $items->sum('quantity');
        $totalPrice = $items->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'totalItems' => $totalItems,
                'totalPrice' => $totalPrice,
            ],
        ]);
    }

    /**
     * Add item to cart or update quantity if exists.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Check if item already exists in cart
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            // Create new cart item
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
            ]);
        }

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $cartItem = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $cartItem = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cartItem->delete();

        // Return updated cart
        return $this->index($request);
    }

    /**
     * Clear all items from cart.
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        Cart::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
            'data' => [
                'items' => [],
                'totalItems' => 0,
                'totalPrice' => 0,
            ],
        ]);
    }
}

