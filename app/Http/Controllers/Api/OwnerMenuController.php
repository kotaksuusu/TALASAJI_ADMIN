<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerMenuRequest;
use App\Http\Requests\UpdateOwnerMenuRequest;
use App\Models\Menu;
use App\Models\OwnerCategory;
use App\Models\OwnerMenu;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Owner Menus', description: 'Owner-level master menu management')]
class OwnerMenuController extends Controller
{
    #[OA\Get(
        path: '/api/owner/menus',
        tags: ['Owner Menus'],
        summary: 'Get all owner master menu items',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'owner_category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Menus retrieved successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OwnerMenu::with('ownerCategory')
            ->where('user_id', Auth::id());

        if ($request->has('owner_category_id')) {
            $query->where('owner_category_id', $request->owner_category_id);
        }

        $menus = $query->orderBy('display_order')->get();

        return response()->json([
            'success' => true,
            'message' => 'Master menus retrieved successfully',
            'data' => $menus,
        ]);
    }

    #[OA\Post(
        path: '/api/owner/menus',
        tags: ['Owner Menus'],
        summary: 'Create a master menu item',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Menu created successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreOwnerMenuRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Verify the category belongs to this user
        OwnerCategory::where('user_id', Auth::id())
            ->findOrFail($validated['owner_category_id']);

        $validated['user_id'] = Auth::id();
        $ownerMenu = OwnerMenu::create($validated);

        // Sync to all stores owned by this user
        $this->syncToAllStores($ownerMenu);

        return response()->json([
            'success' => true,
            'message' => 'Master menu created successfully',
            'data' => $ownerMenu,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $menu = OwnerMenu::with('ownerCategory')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Master menu retrieved successfully',
            'data' => $menu,
        ]);
    }

    #[OA\Put(
        path: '/api/owner/menus/{id}',
        tags: ['Owner Menus'],
        summary: 'Update a master menu item (cascades to all stores)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Menu updated successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Menu not found'),
        ]
    )]
    public function update(UpdateOwnerMenuRequest $request, $id): JsonResponse
    {
        $ownerMenu = OwnerMenu::where('user_id', Auth::id())->findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['owner_category_id'])) {
            OwnerCategory::where('user_id', Auth::id())
                ->findOrFail($validated['owner_category_id']);
        }

        $ownerMenu->update($validated);

        // Cascade update to all linked store menus
        Menu::where('owner_menu_id', $ownerMenu->id)->update([
            'name' => $ownerMenu->name,
            'description' => $ownerMenu->description,
            'price' => $ownerMenu->price,
            'image' => $ownerMenu->image,
            'is_recommended' => $ownerMenu->is_recommended,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Master menu updated successfully. Changes cascaded to all stores.',
            'data' => $ownerMenu,
        ]);
    }

    #[OA\Delete(
        path: '/api/owner/menus/{id}',
        tags: ['Owner Menus'],
        summary: 'Delete a master menu item (cascades to all stores)',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Menu deleted successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Menu not found'),
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $ownerMenu = OwnerMenu::where('user_id', Auth::id())->findOrFail($id);
        $ownerMenu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Master menu deleted successfully. Removed from all stores.',
            'data' => null,
        ]);
    }

    private function syncToAllStores(OwnerMenu $ownerMenu): void
    {
        $stores = Store::where('user_id', $ownerMenu->user_id)->get();

        foreach ($stores as $store) {
            Menu::create([
                'store_id' => $store->id,
                'category_id' => $this->getOrCreateStoreCategory($store, $ownerMenu->ownerCategory),
                'owner_menu_id' => $ownerMenu->id,
                'name' => $ownerMenu->name,
                'description' => $ownerMenu->description,
                'price' => $ownerMenu->price,
                'image' => $ownerMenu->image,
                'stock_status' => 'tersedia',
                'is_recommended' => $ownerMenu->is_recommended ?? false,
                'display_order' => $ownerMenu->display_order ?? 0,
            ]);
        }
    }

    private function getOrCreateStoreCategory(Store $store, OwnerCategory $ownerCategory): int
    {
        $cat = \App\Models\Category::firstOrCreate(
            ['store_id' => $store->id, 'name' => $ownerCategory->name],
            [
                'description' => $ownerCategory->description,
                'display_order' => $ownerCategory->display_order,
                'icon' => $ownerCategory->icon,
                'is_active' => $ownerCategory->is_active,
            ]
        );
        return $cat->id;
    }
}
