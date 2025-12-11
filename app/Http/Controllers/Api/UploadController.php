<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Handle single image upload (CategoryForm)
            if ($request->hasFile('image')) {
                $request->validate([
                    'image' => 'required|image|max:5120', // Max 5MB
                ]);

                $path = $request->file('image')->store('uploads', 'public');
                return response()->json([
                    'success' => true,
                    'path' => $path,
                    'url' => Storage::url($path), // Use Laravel's storage URL helper
                ]);
            }

            // Handle multiple files upload (ProductForm)
            if ($request->hasFile('files')) {
                $request->validate([
                    'files.*' => 'required|image|max:5120', // Max 5MB per file
                ]);

                $uploadedFiles = [];
                $files = $request->file('files');

                // Ensure $files is an array (it might be a single file if only one uploaded)
                if (!is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    $path = $file->store('uploads', 'public');
                    $uploadedFiles[] = [
                        'path' => $path,
                        'url' => Storage::url($path), // Use Laravel's storage URL helper
                        'originalName' => $file->getClientOriginalName(),
                    ];
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'files' => $uploadedFiles
                    ]
                ]);
            }

            return response()->json(['error' => 'No image uploaded'], 400);
        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Upload failed',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
