<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Models\Menu;
use App\Models\Category;
use App\Models\OwnerCategory;
use App\Models\OwnerMenu;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

#[OA\Tag(name: 'Menus', description: 'Menu management endpoints')]
class MenuController extends Controller
{
    #[OA\Get(
        path: '/api/menus',
        tags: ['Menus'],
        summary: 'Get all menus',
        parameters: [
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'store_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Menus retrieved successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Menu::with('category:id,name')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $menus = $query->orderBy('display_order')->get();

        \Illuminate\Support\Facades\Log::debug('[MenuController] GET /api/menus', [
            'store_id' => $request->get('store_id'),
            'count' => $menus->count(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menus retrieved successfully',
            'data' => $menus
        ]);
    }

    public function store(StoreMenuRequest $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'pemilik') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only owners can create menus.',
                'data' => null
            ], 403);
        }

        $validated = $request->validated();
        $category = Category::with('store')->findOrFail($validated['category_id']);

        // Ownership verified by store.owner middleware

        // Find or create owner category with same name
        $ownerCategory = OwnerCategory::firstOrCreate(
            ['user_id' => $user->id, 'name' => $category->name],
            [
                'description' => $category->description,
                'display_order' => $category->display_order,
                'icon' => $category->icon,
                'is_active' => $category->is_active ?? true,
            ]
        );

        // Create owner menu
        $ownerMenu = OwnerMenu::create([
            'user_id' => $user->id,
            'owner_category_id' => $ownerCategory->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'image' => $validated['image'],
            'is_recommended' => $validated['is_recommended'] ?? false,
            'display_order' => $validated['display_order'] ?? 0,
        ]);

        // Sync to all stores owned by this user
        $stores = Store::where('user_id', $user->id)->get();
        foreach ($stores as $store) {
            $storeCategory = Category::firstOrCreate(
                ['store_id' => $store->id, 'name' => $ownerCategory->name],
                [
                    'description' => $ownerCategory->description,
                    'display_order' => $ownerCategory->display_order,
                    'icon' => $ownerCategory->icon,
                    'is_active' => true,
                ]
            );

            Menu::create([
                'store_id' => $store->id,
                'category_id' => $storeCategory->id,
                'owner_menu_id' => $ownerMenu->id,
                'name' => $ownerMenu->name,
                'description' => $ownerMenu->description,
                'price' => $ownerMenu->price,
                'image' => $ownerMenu->image,
                'stock_status' => $validated['stock_status'] ?? 'tersedia',
                'is_recommended' => $ownerMenu->is_recommended,
                'display_order' => $ownerMenu->display_order,
            ]);
        }

        // Return the menu created for the requested store
        $menu = Menu::where('store_id', $category->store_id)
            ->where('owner_menu_id', $ownerMenu->id)
            ->first();

        $this->clearMenuCache($category->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully and synced to all stores.',
            'data' => $menu
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $menu = Menu::with(['category', 'store'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Menu retrieved successfully',
            'data' => $menu
        ]);
    }

    public function update(UpdateMenuRequest $request, $id): JsonResponse
    {
        $user = Auth::user();

        $menu = Menu::with('category.store')->findOrFail($id);

        // Ownership verified by store.owner middleware

        $validated = $request->validated();

        // ── Handle stock_status FIRST — allowed for seller & pemilik ────────
        // Pisahkan dari update detail biar seller bisa toggle tanpa 403
        $onlyStockStatus = count($validated) === 1 && isset($validated['stock_status']);
        $onlyDisplayOrder = count($validated) === 1 && isset($validated['display_order']);

        if ($onlyStockStatus) {
            $menu->stock_status = $validated['stock_status'];
            $menu->save();
            $this->clearMenuCache($menu->store_id);
            return response()->json([
                'success' => true,
                'message' => 'Stock status updated successfully',
                'data' => $menu->fresh()
            ]);
        }

        if ($onlyDisplayOrder) {
            $menu->display_order = $validated['display_order'];
            $menu->save();
            $this->clearMenuCache($menu->store_id);
            return response()->json([
                'success' => true,
                'message' => 'Display order updated successfully',
                'data' => $menu->fresh()
            ]);
        }

        // Only pemilik can update item details (cascading)
        if ($user->role !== 'pemilik') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only owners can update menu details.',
                'data' => null
            ], 403);
        }

        // Update owner_menu and cascade to all linked stores
        $ownerMenu = OwnerMenu::find($menu->owner_menu_id);
        if ($ownerMenu && $ownerMenu->user_id === $user->id) {
            $ownerMenu->update($validated);

            Menu::where('owner_menu_id', $ownerMenu->id)->update([
                'name' => $ownerMenu->name,
                'description' => $ownerMenu->description,
                'price' => $ownerMenu->price,
                'image' => $ownerMenu->image,
                'is_recommended' => $ownerMenu->is_recommended,
            ]);
        }

        if (isset($validated['stock_status'])) {
            $menu->stock_status = $validated['stock_status'];
        }
        if (isset($validated['display_order'])) {
            $menu->display_order = $validated['display_order'];
        }

        $menu->save();

        $this->clearMenuCache($menu->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu->fresh()
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $user = Auth::user();

        $menu = Menu::with('category.store')->findOrFail($id);

        // Ownership verified by store.owner middleware

        if ($user->role !== 'pemilik') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only owners can delete menus.',
                'data' => null
            ], 403);
        }

        // Delete owner_menu which cascades to all linked store menus
        OwnerMenu::where('id', $menu->owner_menu_id)
            ->where('user_id', $user->id)
            ->delete();

        $this->clearMenuCache($menu->store_id);

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully from all stores.',
            'data' => null
        ]);
    }

    private function getStoreIdFromCategory($categoryId): int
    {
        return Category::findOrFail($categoryId)->store_id;
    }

    /**
     * Clear all cached menu queries for a given store.
     */
    private function clearMenuCache(int $storeId): void
    {
        $prefix = 'menus_index_';
        Cache::forget($prefix . $storeId . '_all');

        // Also flush any category-filtered cache variants for this store
        // by iterating common patterns — safest is to forget the store's cache group.
        // Simple approach: also forget the "all stores" cache key.
        Cache::forget($prefix . 'all_all');

        // Forget per-store keys for all category IDs (wildcard: clear menu cache for this store)
        $categoryIds = Category::where('store_id', $storeId)->pluck('id');
        foreach ($categoryIds as $catId) {
            Cache::forget($prefix . $storeId . '_' . $catId);
        }
    }
}
