<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\ProductImage;

class ProductController extends Controller
{
    /**
     * Public products endpoint (no auth required)
     */
    public function publicIndex(Request $request)
    {
        $query = Product::with('category')->where('in_stock', true);

        // Filter by category if provided
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by featured if provided
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('featured', true);
        }

        // Get highest sold products (by order items count)
        if ($request->has('highest_sold') && $request->highest_sold === 'true') {
            $products = $query->withCount('orderItems')
                ->orderBy('order_items_count', 'desc')
                ->limit($request->get('limit', 12))
                ->get();
        } else {
            $products = $query->orderBy('created_at', 'desc')
                ->limit($request->get('limit', 100))
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    /**
     * Public product show endpoint (no auth required)
     */
    public function publicShow($id)
    {
        try {
            $product = Product::with(['category', 'images'])->find($id);

            if (!$product) {
                \Log::info("Product not found with ID: {$id}");
                return response()->json([
                    'success' => false,
                    'error' => 'Product not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            \Log::error("Error fetching product {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error fetching product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin products endpoint (auth required)
     */
    public function index()
    {
        $products = Product::with(['category', 'images'])->get();
        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'in_stock' => 'boolean',
            'quantity' => 'integer|min:0',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*.imageUrl' => 'nullable|string',
            'images.*.altText' => 'nullable|string',
            'images.*.sortOrder' => 'nullable|integer',
            'images.*.isPrimary' => 'nullable|boolean',
        ]);

        // Ensure in_stock is set - default to true if quantity > 0, or use provided value
        if (!isset($validated['in_stock'])) {
            $validated['in_stock'] = ($validated['quantity'] ?? 0) > 0;
        }

        $product = Product::create($validated);

        // Handle images if provided
        if ($request->has('images') && is_array($request->images)) {
            foreach ($request->images as $index => $imageData) {
                if (!empty($imageData['imageUrl'])) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageData['imageUrl'],
                        'alt_text' => $imageData['altText'] ?? null,
                        'sort_order' => $imageData['sortOrder'] ?? $index,
                        'is_primary' => $imageData['isPrimary'] ?? false,
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $product->load(['category', 'images'])
        ], 201);
    }

    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product->load(['category', 'images'])
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'image' => 'nullable|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'in_stock' => 'boolean',
            'quantity' => 'integer|min:0',
            'featured' => 'boolean',
            'images' => 'nullable|array',
            'images.*.imageUrl' => 'nullable|string',
            'images.*.altText' => 'nullable|string',
            'images.*.sortOrder' => 'nullable|integer',
            'images.*.isPrimary' => 'nullable|boolean',
        ]);

        // Ensure in_stock is set - default to true if quantity > 0, or use provided value
        if (isset($validated['quantity']) && !isset($validated['in_stock'])) {
            $validated['in_stock'] = $validated['quantity'] > 0;
        } elseif (!isset($validated['in_stock'])) {
            // If quantity not updated, keep existing in_stock or default based on current quantity
            $validated['in_stock'] = $product->quantity > 0;
        }

        // Handle images if provided
        if ($request->has('images') && is_array($request->images)) {
            // Delete existing images
            $product->images()->delete();

            // Create new images
            foreach ($request->images as $index => $imageData) {
                if (!empty($imageData['imageUrl'])) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageData['imageUrl'],
                        'alt_text' => $imageData['altText'] ?? null,
                        'sort_order' => $imageData['sortOrder'] ?? $index,
                        'is_primary' => $imageData['isPrimary'] ?? false,
                    ]);
                }
            }
        }

        $product->update($validated);

        // Always reload with images, even if images weren't updated
        $product->load(['category', 'images']);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }

    public function destroy(Product $product)
    {
        try {
            // Check for associated order items
            if ($product->orderItems()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product because it has been ordered. Please disable the product instead.'
                ], 400);
            }

            // Check for associated cart items
            if ($product->cartItems()->exists()) {
                $product->cartItems()->delete();
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Product deletion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }
}
