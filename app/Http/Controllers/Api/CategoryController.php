<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Categories')]
class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/categories',
        summary: 'Get all categories',
        security: [['sanctum' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'store_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filter by store ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categories retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $categories = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    #[OA\Post(
        path: '/api/categories',
        summary: 'Create a new category',
        security: [['sanctum' => []]],
        tags: ['Category'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true),
                    new OA\Property(property: 'icon', type: 'string', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only sellers can create categories'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();
        // Ownership verified by store.owner middleware

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    #[OA\Get(
        path: '/api/categories/{id}',
        summary: 'Get a category by ID',
        security: [['sanctum' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show($id)
    {
        $category = Category::with('menus')->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => $category,
        ]);
    }

    #[OA\Put(
        path: '/api/categories/{id}',
        summary: 'Update a category',
        security: [['sanctum' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'display_order', type: 'integer', nullable: true),
                    new OA\Property(property: 'icon', type: 'string', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - you do not own this category'),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data' => null,
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $validated = $request->validated();

        if (isset($validated['store_id']) && $validated['store_id'] !== $category->store_id) {
            $newStore = Store::find($validated['store_id']);
            if (!$newStore || ($newStore->user_id !== Auth::id() && $newStore->seller_id !== Auth::id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own the target store',
                    'data' => null,
                ], 403);
            }
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    #[OA\Delete(
        path: '/api/categories/{id}',
        summary: 'Delete a category',
        security: [['sanctum' => []]],
        tags: ['Category'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - you do not own this category'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'data' => null,
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'data' => null,
        ]);
    }
}