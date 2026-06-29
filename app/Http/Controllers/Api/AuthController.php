<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/register',
        summary: 'Register a new user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'role', type: 'string', enum: ['pelanggan', 'penjual', 'pemilik'], default: 'pelanggan'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 201, description: 'User registered successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    private function mapRole(string $role): string
    {
        return match ($role) {
            'customer' => 'pelanggan',
            'seller'   => 'penjual',
            'owner'    => 'pemilik',
            'admin'    => 'admin',
            default    => $role,
        };
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|max:255',
        ]);

        $role = $this->mapRole($validated['role'] ?? 'customer');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $role,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    #[OA\Post(
        path: '/api/login',
        summary: 'Login user',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials'),
        ]
    )]
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null,
            ], 401);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
                'data' => null,
            ], 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/logout',
        summary: 'Logout user',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
            'data' => null,
        ]);
    }

    #[OA\Get(
        path: '/api/me',
        summary: 'Get authenticated user profile',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'User profile data'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Data user berhasil diambil',
            'data' => $request->user(),
        ]);
    }

    #[OA\Put(
        path: '/api/profile',
        summary: 'Update user profile',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone_number', type: 'string', maxLength: 20),
                    new OA\Property(property: 'avatar_url', type: 'string', maxLength: 255),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $request->user()->id,
            'phone_number' => 'nullable|string|max:20',
            'avatar_url' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data' => $user->fresh(),
        ]);
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $path = $request->file('avatar')->store('avatars', 'public');
        $filename = basename($path);
        $url = url('api/avatar/' . $filename);

        $user = $request->user();
        $user->update(['avatar_url' => $url]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar berhasil diupload',
            'data' => [
                'avatar_url' => $url,
            ],
        ]);
    }

    public function serveAvatar(string $path)
    {
        $filePath = storage_path('app/public/avatars/' . $path);

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

    #[OA\Post(
        path: '/api/change-password',
        summary: 'Change user password',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                    new OA\Property(property: 'password_confirmation', type: 'string'),
                ]
            )
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Password changed successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah',
            'data' => null,
        ]);
    }
}