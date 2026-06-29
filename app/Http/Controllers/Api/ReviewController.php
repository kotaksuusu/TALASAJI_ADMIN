<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Review;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

#[OA\Tag(name: 'Reviews', description: 'Review management endpoints')]
class ReviewController extends Controller
{
    #[OA\Get(
        path: '/api/reviews',
        tags: ['Reviews'],
        summary: 'Get all reviews',
        parameters: [
            new OA\Parameter(name: 'menu_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'store_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reviews retrieved successfully'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user:id,name,avatar_url', 'menu:id,name,image', 'store:id,name']);

        if ($request->has('menu_id')) {
            $query->where('menu_id', $request->menu_id);
        }

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $reviews = $query->latest()->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => $reviews
        ]);
    }

    #[OA\Post(
        path: '/api/reviews',
        tags: ['Reviews'],
        summary: 'Submit a review',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'menu_id', type: 'integer'),
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5),
                    new OA\Property(property: 'comment', type: 'string', nullable: true),
                    new OA\Property(property: 'photo', type: 'string', nullable: true),
                    new OA\Property(property: 'recommend', type: 'boolean', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Review submitted successfully'),
            new OA\Response(response: 403, description: 'Unauthorized - only customers can review'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreReviewRequest $request): JsonResponse
    {
        $user = Auth::user();
        $validated = $request->validated();
        $validated['user_id'] = $user->id;

        $review = Review::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully',
            'data' => $review->load(['user', 'menu'])
        ], 201);
    }

    #[OA\Get(
        path: '/api/reviews/{id}',
        tags: ['Reviews'],
        summary: 'Get a review by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review retrieved successfully'),
            new OA\Response(response: 404, description: 'Review not found'),
        ]
    )]
    public function show($id): JsonResponse
    {
        $review = Review::with(['user', 'menu'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Review retrieved successfully',
            'data' => $review
        ]);
    }

    #[OA\Delete(
        path: '/api/reviews/{id}',
        tags: ['Reviews'],
        summary: 'Delete a review',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Review deleted successfully'),
            new OA\Response(response: 403, description: 'Unauthorized - only the review owner can delete'),
            new OA\Response(response: 404, description: 'Review not found'),
        ]
    )]
    public function destroy($id): JsonResponse
    {
        $user = Auth::user();
        $review = Review::findOrFail($id);

        if ($review->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only delete your own reviews.',
                'data' => null
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review deleted successfully',
            'data' => null
        ]);
    }
}
