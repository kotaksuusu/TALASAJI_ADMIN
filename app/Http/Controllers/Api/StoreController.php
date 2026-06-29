<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStoreRequest;
use App\Http\Requests\UpdateStoreRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\OwnerCategory;
use App\Models\OwnerMenu;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stores')]
class StoreController extends Controller
{
    #[OA\Get(
        path: '/api/stores',
        summary: 'Get all stores',
        security: [['sanctum' => []]],
        tags: ['Store'],
        responses: [
            new OA\Response(response: 200, description: 'Stores retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $cacheKey = 'stores_all_' . ($request->get('user_id') ?? 'all');

        $stores = \Illuminate\Support\Facades\Cache::remember($cacheKey, 120, function () use ($request) {
            $query = Store::select(['id', 'name', 'address', 'phone', 'latitude', 'longitude', 'radius_meter', 'operational_status', 'logo', 'user_id', 'seller_id', 'description', 'category', 'registration_status', 'created_at', 'updated_at', 'payment_qr'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews');

            if ($request->has('user_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('user_id', $request->user_id)
                      ->orWhere('seller_id', $request->user_id);
                });
            } else {
                // Pelanggan (tidak mengirim user_id) — hanya tampilkan toko yang AKTIF
                $query->where('registration_status', 'active');
            }

            $result = $query->orderBy('name')->get();

            // Logo URL fix — lakukan DI DALAM cache agar tidak perlu transform setelah unserialize
            $base = $request->getSchemeAndHttpHost();
            foreach ($result as $store) {
                if ($store->logo && str_contains($store->logo, 'localhost')) {
                    $store->logo = preg_replace('/http:\/\/localhost(:\d+)?\/api\/storage\//', $base . '/api/storage/', $store->logo);
                }
            }

            return $result;
        });

        return response()->json([
            'success' => true,
            'message' => 'Stores retrieved successfully',
            'data' => $stores,
        ]);
    }

    #[OA\Post(
        path: '/api/stores',
        summary: 'Create a new store',
        security: [['sanctum' => []]],
        tags: ['Store'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'latitude', type: 'number', nullable: true),
                    new OA\Property(property: 'longitude', type: 'number', nullable: true),
                    new OA\Property(property: 'radius_meter', type: 'integer', minimum: 10, maximum: 1000, nullable: true),
                    new OA\Property(property: 'operational_status', type: 'string', enum: ['buka', 'tutup'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Store created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only sellers can create stores'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreStoreRequest $request)
    {
        $user = Auth::user();

        if ($user->role !== 'pemilik' && $user->store()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a store. Each seller can only own one store.',
                'data' => null,
            ], 403);
        }

        $sellerId = null;

        if ($request->filled('branch_email')) {
            $sellerUser = User::create([
                'name' => $request->branch_name ?? 'Pengelola ' . $request->name,
                'email' => $request->branch_email,
                'password' => $request->branch_password,
                'role' => 'penjual',
            ]);
            $sellerId = $sellerUser->id;
        }

        $store = Store::create([
            'name' => $request->name,
            'description' => $request->description,
            'address' => $request->address,
            'phone' => $request->phone,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meter' => $request->radius_meter ?? 50,
            'operational_status' => $request->operational_status ?? 'buka',
            'registration_status' => 'pending',
            'logo' => $request->logo,
            'payment_qr' => $request->payment_qr,
            'user_id' => $user->id,
            'seller_id' => $sellerId,
        ]);

        // Sync existing Owner Master Menu ke toko baru
        if ($user->role === 'pemilik') {
            $this->syncMasterToStore($store, $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Store created successfully',
            'data' => $store,
        ], 201);
    }

    private function syncMasterToStore(Store $store, string $userId): void
    {
        $ownerCategories = OwnerCategory::where('user_id', $userId)->get();
        $ownerMenus = OwnerMenu::where('user_id', $userId)->with('ownerCategory')->get();

        foreach ($ownerMenus as $ownerMenu) {
            // Cari atau buat category di store ini
            $cat = Category::firstOrCreate(
                ['store_id' => $store->id, 'name' => $ownerMenu->ownerCategory->name],
                [
                    'description' => $ownerMenu->ownerCategory->description,
                    'display_order' => $ownerMenu->ownerCategory->display_order,
                    'icon' => $ownerMenu->ownerCategory->icon,
                    'is_active' => $ownerMenu->ownerCategory->is_active,
                ]
            );

            Menu::create([
                'store_id' => $store->id,
                'category_id' => $cat->id,
                'owner_menu_id' => $ownerMenu->id,
                'name' => $ownerMenu->name,
                'description' => $ownerMenu->description,
                'price' => $ownerMenu->price,
                'image' => $ownerMenu->image,
                'stock_status' => 'tersedia',
                'is_recommended' => $ownerMenu->is_recommended,
                'display_order' => $ownerMenu->display_order,
            ]);
        }
    }

    #[OA\Get(
        path: '/api/stores/{id}',
        summary: 'Get a store by ID',
        security: [['sanctum' => []]],
        tags: ['Store'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Store retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Store not found'),
        ]
    )]
    public function show(Request $request, $id)
    {
        $cacheKey = 'store_detail_' . $id;
        $store = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($id) {
            return Store::withAvg('reviews', 'rating')->withCount('reviews')->with('user:id,name,email,avatar_url', 'seller:id,name,email')->find($id);
        });

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'data' => null,
            ], 404);
        }

        $base = $request->getSchemeAndHttpHost();
        if ($store->logo && str_contains($store->logo, 'localhost')) {
            $store->logo = preg_replace('/http:\/\/localhost(:\d+)?\/api\/storage\//', $base . '/api/storage/', $store->logo);
        }

        return response()->json([
            'success' => true,
            'message' => 'Store retrieved successfully',
            'data' => $store,
        ]);
    }

    #[OA\Put(
        path: '/api/stores/{id}',
        summary: 'Update a store',
        security: [['sanctum' => []]],
        tags: ['Store'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'description', type: 'string', maxLength: 1000, nullable: true),
                    new OA\Property(property: 'address', type: 'string', maxLength: 500, nullable: true),
                    new OA\Property(property: 'phone', type: 'string', maxLength: 20, nullable: true),
                    new OA\Property(property: 'latitude', type: 'number', nullable: true),
                    new OA\Property(property: 'longitude', type: 'number', nullable: true),
                    new OA\Property(property: 'radius_meter', type: 'integer', minimum: 10, maximum: 1000, nullable: true),
                    new OA\Property(property: 'operational_status', type: 'string', enum: ['buka', 'tutup'], nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Store updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only the owner can update'),
            new OA\Response(response: 404, description: 'Store not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateStoreRequest $request, $id)
    {
        $store = Store::find($id);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'data' => null,
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $updateData = $request->only(['name', 'description', 'address', 'phone', 'latitude', 'longitude', 'radius_meter', 'operational_status', 'logo', 'payment_qr']);

        // Handle Akun Toko (seller account)
        if ($request->filled('seller_email')) {
            $sellerValidator = Validator::make($request->all(), [
                'seller_email' => 'required|string|email|max:255',
                'seller_password' => 'nullable|string|min:6',
            ]);

            if ($sellerValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'data' => $sellerValidator->errors(),
                ], 422);
            }

            $sellerUser = User::where('email', $request->seller_email)->first();

            if ($sellerUser) {
                if ($sellerUser->id === $store->seller_id) {
                    if ($request->filled('seller_password')) {
                        $sellerUser->update([
                            'password' => $request->seller_password,
                        ]);
                    }
                    $updateData['seller_id'] = $sellerUser->id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email sudah digunakan oleh akun lain.',
                        'data' => null,
                    ], 422);
                }
            } else {
                $sellerUser = User::create([
                    'name' => $request->input('seller_name', 'Penjual ' . $store->name),
                    'email' => $request->seller_email,
                    'password' => $request->seller_password ?? 'password',
                    'role' => 'penjual',
                ]);
                $updateData['seller_id'] = $sellerUser->id;
            }
        }

        $store->update($updateData);

        $store->load('seller:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Store updated successfully',
            'data' => $store,
        ]);
    }

    #[OA\Delete(
        path: '/api/stores/{id}',
        summary: 'Delete a store',
        security: [['sanctum' => []]],
        tags: ['Store'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Store deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only the owner can delete'),
            new OA\Response(response: 404, description: 'Store not found'),
        ]
    )]
    public function myStore()
    {
        $user = Auth::user();
        $store = Store::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->with('user:id,name,email,avatar_url', 'seller:id,name,email')
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            })
            ->first();

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Store retrieved successfully',
            'data' => $store,
        ]);
    }

    public function destroy($id)
    {
        $store = Store::find($id);

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'data' => null,
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $store->delete();

        return response()->json([
            'success' => true,
            'message' => 'Store deleted successfully',
            'data' => null,
        ]);
    }

    public function clone(Request $request, $id)
    {
        $original = Store::with('categories', 'categories.menus')->findOrFail($id);

        // Ownership verified by store.owner middleware

        $user = Auth::user();

        $clone = Store::create([
            'name' => $original->name . ' (Copy)',
            'description' => $original->description,
            'address' => $original->address,
            'phone' => $original->phone,
            'latitude' => $original->latitude,
            'longitude' => $original->longitude,
            'radius_meter' => $original->radius_meter,
            'operational_status' => 'buka',
            'registration_status' => 'pending',
            'logo' => $original->logo,
            'payment_qr' => $original->payment_qr,
            'user_id' => $original->user_id,
            'seller_id' => null, // cloned store doesn't inherit seller
        ]);

        // Clone categories & menus
        foreach ($original->categories as $cat) {
            $newCat = $clone->categories()->create([
                'name' => $cat->name,
                'description' => $cat->description,
                'display_order' => $cat->display_order,
                'icon' => $cat->icon,
                'is_active' => $cat->is_active,
            ]);

            foreach ($cat->menus as $menu) {
                $clone->menus()->create([
                    'category_id' => $newCat->id,
                    'owner_menu_id' => $menu->owner_menu_id,
                    'name' => $menu->name,
                    'description' => $menu->description,
                    'price' => $menu->price,
                    'image' => $menu->image,
                    'stock_status' => 'tersedia',
                    'is_recommended' => $menu->is_recommended,
                    'display_order' => $menu->display_order,
                ]);
            }
        }

        $clone->load('categories.menus');

        return response()->json([
            'success' => true,
            'message' => 'Store cloned successfully',
            'data' => $clone,
        ], 201);
    }
}