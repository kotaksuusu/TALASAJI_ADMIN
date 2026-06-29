<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOwnerCategoryRequest;
use App\Http\Requests\UpdateOwnerCategoryRequest;
use App\Models\OwnerCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Owner Categories', description: 'Owner-level category management')]
class OwnerCategoryController extends Controller
{
    #[OA\Get(
        path: '/api/owner/categories',
        tags: ['Owner Categories'],
        summary: 'Get all owner categories',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Categories retrieved successfully'),
        ]
    )]
    public function index(): JsonResponse
    {
        $categories = OwnerCategory::where('user_id', Auth::id())
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ]);
    }

    #[OA\Post(
        path: '/api/owner/categories',
        tags: ['Owner Categories'],
        summary: 'Create a new owner category',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 201, description: 'Category created successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreOwnerCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = Auth::id();
        $category = OwnerCategory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $category = OwnerCategory::where('user_id', Auth::id())->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Category retrieved successfully',
            'data' => $category,
        ]);
    }

    #[OA\Put(
        path: '/api/owner/categories/{id}',
        tags: ['Owner Categories'],
        summary: 'Update an owner category',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Category updated successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function update(UpdateOwnerCategoryRequest $request, $id): JsonResponse
    {
        $category = OwnerCategory::where('user_id', Auth::id())->findOrFail($id);
        $validated = $request->validated();
        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    #[OA\Delete(
        path: '/api/owner/categories/{id}',
        tags: ['Owner Categories'],
        summary: 'Delete an owner category',
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted successfully'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $category = OwnerCategory::where('user_id', Auth::id())->findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
            'data' => null,
        ]);
    }
}
