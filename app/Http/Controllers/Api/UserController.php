<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Map frontend camelCase to backend snake_case if needed, or just validate snake_case
        // The frontend sends: firstName, lastName, dateOfBirth
        // We need to map them.

        // Let's handle the mapping manually
        $data = $request->all();
        $updateData = [];

        if (isset($data['firstName']))
            $updateData['first_name'] = $data['firstName'];
        if (isset($data['lastName']))
            $updateData['last_name'] = $data['lastName'];
        if (isset($data['dateOfBirth']))
            $updateData['date_of_birth'] = $data['dateOfBirth'];
        if (isset($data['phone']))
            $updateData['phone'] = $data['phone'];
        if (isset($data['address']))
            $updateData['address'] = $data['address'];
        if (isset($data['city']))
            $updateData['city'] = $data['city'];
        if (isset($data['postcode']))
            $updateData['postcode'] = $data['postcode'];
        if (isset($data['country']))
            $updateData['country'] = $data['country'];
        if (isset($data['email']))
            $updateData['email'] = $data['email'];

        // Validate
        // We can use Validator facade or request->validate on the mapped data? No, validate request data.
        // But request data has camelCase.
        // Let's just validate the values we extracted.

        // Simple validation
        if (isset($updateData['email']) && $updateData['email'] !== $user->email) {
            $request->validate(['email' => 'email|unique:users,email,' . $user->id]);
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user
            ],
            'message' => 'Profile updated successfully'
        ]);
    }

    public function uploadProfilePicture(Request $request)
    {
        $request->validate([
            'profile_picture' => 'required|image|max:5120', // 5MB max
        ]);

        $user = $request->user();

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Store in public/uploads/profiles
            $file->move(public_path('uploads/profiles'), $filename);

            $path = '/uploads/profiles/' . $filename;

            $user->profile_picture = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Profile picture uploaded successfully'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
    }

    /**
     * Get all users for admin
     */
    public function adminIndex(Request $request)
    {
        $users = User::withCount('orders')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'phone' => $user->phone,
                    'role' => $user->role ?? 'customer',
                    'createdAt' => $user->created_at->toISOString(),
                    '_count' => [
                        'orders' => $user->orders_count ?? 0,
                    ],
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }
}
