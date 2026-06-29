<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class StorageController extends Controller
{
    public function serve(string $path)
    {
        if (str_contains($path, '..')) {
            abort(403);
        }

        $filePath = storage_path('app/public/' . $path);

        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak ditemukan',
                'data' => null,
            ], 404);
        }

        return response()->file($filePath, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ]);
    }
}
