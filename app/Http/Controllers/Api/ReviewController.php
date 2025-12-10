<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class ReviewController extends Controller
{
    /**
     * Get all approved reviews for a product (public)
     */
    public function index($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        // Only show approved reviews for public
        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->with('user:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate average rating and rating distribution from approved reviews only
        $avgRating = $reviews->avg('rating') ?? 0;
        $ratingDistribution = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];
        $totalReviews = $reviews->count();
        $ratingPercentages = [];
        foreach ($ratingDistribution as $rating => $count) {
            $ratingPercentages[$rating] = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'average_rating' => round($avgRating, 1),
                'total_reviews' => $totalReviews,
                'rating_distribution' => $ratingDistribution,
                'rating_percentages' => $ratingPercentages,
            ],
        ]);
    }

    /**
     * Create a new review for a product (status: pending)
     */
    public function store(Request $request, $productId)
    {
        // Get authenticated user
        $user = $this->getAuthenticatedUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Please login to add a review.',
            ], 401);
        }

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        // Check if user already has an approved or pending review for this product
        // Allow resubmission if previous review was rejected
        $existingReview = Review::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->first();

        if ($existingReview) {
            $statusMessage = $existingReview->status === 'pending'
                ? 'You have a review pending approval for this product'
                : 'You have already reviewed this product';

            return response()->json([
                'success' => false,
                'error' => $statusMessage,
            ], 400);
        }

        // If user has a rejected review, delete it so they can submit a new one
        $rejectedReview = Review::where('product_id', $productId)
            ->where('user_id', $user->id)
            ->where('status', 'rejected')
            ->first();

        if ($rejectedReview) {
            $rejectedReview->delete();
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending', // New reviews are pending approval
        ]);

        // Don't update product rating yet - wait for approval
        // $this->updateProductRating($productId);

        $review->load('user:id,first_name,last_name,email');

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully. It will be visible after admin approval.',
            'data' => $review,
        ], 201);
    }

    /**
     * Get all reviews for admin (with filters)
     */
    public function adminIndex(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $status = $request->query('status', 'all');
        $productId = $request->query('product_id');

        $query = Review::with(['user:id,first_name,last_name,email', 'product:id,name'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $reviews = $query->paginate(20);

        // Get pending count for notification
        $pendingCount = Review::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'data' => $reviews,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * Update review status (approve/reject)
     */
    public function updateStatus(Request $request, $reviewId)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $review = Review::find($reviewId);

        if (!$review) {
            return response()->json([
                'success' => false,
                'error' => 'Review not found',
            ], 404);
        }

        $review->status = $request->status;
        $review->save();

        // Update product rating and reviews count (only count approved reviews)
        $this->updateProductRating($review->product_id);

        $review->load('user:id,first_name,last_name,email', 'product:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Review status updated successfully',
            'data' => $review,
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(Request $request, $reviewId)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $review = Review::find($reviewId);

        if (!$review) {
            return response()->json([
                'success' => false,
                'error' => 'Review not found',
            ], 404);
        }

        $productId = $review->product_id;
        $review->delete();

        // Update product rating and reviews count
        $this->updateProductRating($productId);

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
        ]);
    }

    /**
     * Get pending reviews count for notifications
     */
    public function pendingCount(Request $request)
    {
        $user = $this->getAuthenticatedUser($request);

        if (!$user || $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized. Admin access required.',
            ], 403);
        }

        $count = Review::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Get authenticated user from request
     */
    private function getAuthenticatedUser(Request $request)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null;
        }

        return $accessToken->tokenable;
    }

    /**
     * Update product rating and reviews count (only approved reviews)
     */
    private function updateProductRating($productId)
    {
        // Only count approved reviews
        $reviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;
        $reviewsCount = $reviews->count();

        Product::where('id', $productId)->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount,
        ]);
    }
}
