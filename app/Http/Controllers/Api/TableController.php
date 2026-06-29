<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTableRequest;
use App\Http\Requests\UpdateTableRequest;
use App\Models\Store;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tables')]
class TableController extends Controller
{
    #[OA\Get(
        path: '/api/tables',
        summary: 'Get all tables',
        security: [['sanctum' => []]],
        tags: ['Table'],
        parameters: [
            new OA\Parameter(name: 'store_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filter by store ID'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tables retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Table::query();

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $tables = $query->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'message' => 'Tables retrieved successfully',
            'data' => $tables->items()
        ]);
    }

    #[OA\Post(
        path: '/api/tables',
        summary: 'Create a new table',
        security: [['sanctum' => []]],
        tags: ['Table'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'number', type: 'number'),
                    new OA\Property(property: 'capacity', type: 'number'),
                    new OA\Property(property: 'status', type: 'string', enum: ['available', 'occupied', 'reserved']),
                    new OA\Property(property: 'qr_code_content', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Table created successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only sellers can create tables'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreTableRequest $request)
    {
        $validated = $request->validated();
        $table = Table::create($validated);

        $table->qr_code_content = 'TALASAJI:TABLE:' . $table->store_id . ':' . $table->id;
        $table->save();

        $table = $table->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Table created successfully',
            'data' => $table
        ], 201);
    }

    #[OA\Get(
        path: '/api/tables/{id}',
        summary: 'Get a table by ID',
        security: [['sanctum' => []]],
        tags: ['Table'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Table retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function show($id)
    {
        $table = Table::with('store')->find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Table retrieved successfully',
            'data' => $table
        ]);
    }

    #[OA\Put(
        path: '/api/tables/{id}',
        summary: 'Update a table',
        security: [['sanctum' => []]],
        tags: ['Table'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'number', type: 'number'),
                    new OA\Property(property: 'capacity', type: 'number'),
                    new OA\Property(property: 'status', type: 'string', enum: ['available', 'occupied', 'reserved']),
                    new OA\Property(property: 'qr_code_content', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Table updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only sellers can update tables'),
            new OA\Response(response: 404, description: 'Table not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function update(UpdateTableRequest $request, $id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
                'data' => null
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $validated = $request->validated();
        $table->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Table updated successfully',
            'data' => $table
        ]);
    }

    #[OA\Delete(
        path: '/api/tables/{id}',
        summary: 'Delete a table',
        security: [['sanctum' => []]],
        tags: ['Table'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Table deleted successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized - only sellers can delete tables'),
            new OA\Response(response: 404, description: 'Table not found'),
        ]
    )]
    public function destroy($id)
    {
        $table = Table::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Table not found',
                'data' => null
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $table->delete();

        return response()->json([
            'success' => true,
            'message' => 'Table deleted successfully',
            'data' => null
        ]);
    }
}