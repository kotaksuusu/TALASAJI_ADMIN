<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function store(Request $request, SupabaseStorageService $supabase)
    {
        $request->validate([
            'file' => 'required|image|max:5120',
            'folder' => 'nullable|string|max:50',
        ]);

        $folder = $request->input('folder', 'uploads');
        $url = $supabase->upload($folder, $request->file('file'));

        if (!$url) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file to storage',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => ['url' => $url],
        ]);
    }
}
