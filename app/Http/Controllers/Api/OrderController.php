<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\NotificationService;

#[OA\Tag(name: 'Orders', description: 'Order management endpoints')]
class OrderController extends Controller
{
    #[OA\Get(
        path: '/api/orders',
        tags: ['Orders'],
        summary: 'Get all orders',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'store_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Orders retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Order::with([
            'store:id,name,address',
            'user:id,name',
            'orderItems:id,order_id,menu_id,menu_name,quantity,price,subtotal,notes,created_at',
            'payment:id,order_id,amount,payment_method,payment_status,payment_proof,payment_date',
        ]);

        if ($request->has('user_id') && in_array($user->role, ['penjual', 'pemilik', 'admin'])) {
            $query->where('user_id', $request->user_id);
        } elseif ($user->role === 'pelanggan') {
            $query->where('user_id', $user->id);

            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }
        } elseif (in_array($user->role, ['penjual', 'pemilik'])) {
            $storeIds = Store::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('seller_id', $user->id);
            })->pluck('id');
            $query->whereIn('store_id', $storeIds);

            if ($request->has('store_id')) {
                $query->where('store_id', $request->store_id);
            }
        }

        $orders = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully',
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/orders',
        tags: ['Orders'],
        summary: 'Create a new order',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'table_id', type: 'integer'),
                    new OA\Property(property: 'store_id', type: 'integer'),
                    new OA\Property(property: 'service_type', type: 'string', enum: ['dine_in', 'take_away'], nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'menu_id', type: 'integer'),
                                new OA\Property(property: 'quantity', type: 'integer'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Order created successfully'),
            new OA\Response(response: 403, description: 'Unauthorized - only customers can create orders'),
            new OA\Response(response: 409, description: 'Active order already exists'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StoreOrderRequest $request)
    {
        $user = Auth::user();

        if (!$user || $user->role !== 'pelanggan') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only customers can create orders.',
                'data' => null,
            ], 403);
        }

        $validated = $request->validated();
        $store = Store::find($validated['store_id']);
        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Store not found',
                'data' => null,
            ], 404);
        }

        $activeOrder = Order::where('user_id', $user->id)
            ->where('store_id', $request->store_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->first();

        if ($activeOrder) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active order for this store.',
                'data' => $activeOrder,
            ], 409);
        }

        $totalPrice = 0;
        foreach ($validated['items'] as $item) {
            $menu = Menu::find($item['menu_id']);
            if ($menu && $menu->store_id === $store->id) {
                $totalPrice += $menu->price * $item['quantity'];
            }
        }

        $todayCount = Order::where('store_id', $validated['store_id'])
            ->whereDate('created_at', today())
            ->count();
        $uniqueNominal = ($todayCount % 999) + 1;

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'store_id' => $validated['store_id'],
                'table_id' => $validated['table_id'] ?? null,
                'total_amount' => $totalPrice + $uniqueNominal,
                'unique_nominal' => $uniqueNominal,
                'status' => 'pending',
                'service_type' => $validated['service_type'] ?? 'dine_in',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $menu = Menu::find($item['menu_id']);
                OrderItem::create([
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'menu_id' => $item['menu_id'],
                    'menu_name' => $menu->name,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                    'subtotal' => $menu->price * $item['quantity'],
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            $order->load('store', 'orderItems.menu', 'user');

            DB::commit();

            $total = number_format($order->total_amount ?? 0, 0, ',', '.');
            $storeName = $order->store->name ?? 'Toko';
            $customerName = $order->user->name ?? 'Pelanggan';

            NotificationService::notifyCustomer(
                $order,
                'Pesanan Dibuat',
                "Pesanan di {$storeName} berhasil dibuat (Rp {$total}). Silakan lakukan pembayaran.",
                'order_created'
            );

            NotificationService::notifySeller(
                $order,
                'Pesanan Baru',
                "Pesanan baru dari {$customerName} — Rp {$total}.",
                'new_order'
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to create order error: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    #[OA\Get(
        path: '/api/orders/{id}',
        tags: ['Orders'],
        summary: 'Get an order by ID',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::with(['store', 'orderItems.menu', 'user', 'payment'])->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        $storeIds = Store::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('seller_id', $user->id);
        })->pluck('id');
        $isOwner = $order->user_id === $user->id;
        $isSeller = $storeIds->contains($order->store_id);

        if (!$isOwner && !$isSeller) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully',
            'data' => $order,
        ]);
    }

    #[OA\Put(
        path: '/api/orders/{id}/status',
        tags: ['Orders'],
        summary: 'Update order status',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Order status updated successfully'),
            new OA\Response(response: 400, description: 'Cannot update completed/cancelled order'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function updateStatus(UpdateOrderStatusRequest $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        // Ownership verified by store.owner middleware

        $validated = $request->validated();

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update status of a completed or cancelled order.',
                'data' => null,
            ], 400);
        }

        $order->status = $validated['status'];
        $order->save();

        $order->load('store', 'orderItems.menu', 'user');

        $storeName = $order->store->name ?? 'Toko';

        match ($validated['status']) {
            'confirmed' => NotificationService::notifyCustomer(
                $order, 'Pesanan Dikonfirmasi',
                "Pesanan di {$storeName} telah dikonfirmasi.",
                'order_confirmed'
            ),
            'preparing' => NotificationService::notifyCustomer(
                $order, 'Pesanan Dimasak',
                "Pesanan di {$storeName} sedang dimasak.",
                'order_preparing'
            ),
            'ready' => NotificationService::notifyCustomer(
                $order, 'Pesanan Siap',
                "Pesanan di {$storeName} siap diantar atau diambil.",
                'order_ready'
            ),
            'completed' => NotificationService::notifyCustomer(
                $order, 'Pesanan Selesai',
                "Pesanan di {$storeName} telah selesai! Jangan lupa beri ulasan.",
                'order_completed'
            ),
            default => null,
        };

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'data' => $order,
        ]);
    }

    #[OA\Post(
        path: '/api/orders/{id}/items',
        tags: ['Orders'],
        summary: 'Add item to an order',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'menu_id', type: 'integer'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Item added successfully'),
            new OA\Response(response: 400, description: 'Order is not pending / menu not from this store'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function addItem(Request $request, $id)
    {
        $user = Auth::user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the order owner can add items.',
                'data' => null,
            ], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add items to an order that is not pending.',
                'data' => null,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|integer|exists:menus,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'data' => $validator->errors(),
            ], 422);
        }

        $menu = Menu::find($request->menu_id);
        if ($menu->store_id !== $order->store_id) {
            return response()->json([
                'success' => false,
                'message' => 'Menu does not belong to this store.',
                'data' => null,
            ], 400);
        }

        $existingItem = OrderItem::where('order_id', $order->id)
            ->where('menu_id', $request->menu_id)
            ->first();

        DB::beginTransaction();
        try {
            if ($existingItem) {
                $existingItem->quantity += $request->quantity;
                $existingItem->subtotal = $existingItem->quantity * $existingItem->price;
                $existingItem->save();
                $orderItem = $existingItem;
            } else {
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'store_id' => $order->store_id,
                    'menu_id' => $request->menu_id,
                    'menu_name' => $menu->name,
                    'quantity' => $request->quantity,
                    'price' => $menu->price,
                    'subtotal' => $menu->price * $request->quantity,
                ]);
            }

            $newTotal = OrderItem::where('order_id', $order->id)->sum('subtotal');
            $order->total_amount = $newTotal + ($order->unique_nominal ?? 0);
            $order->save();

            $order->load('store', 'orderItems.menu');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item added successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add item',
                'data' => null,
            ], 500);
        }
    }

    #[OA\Delete(
        path: '/api/orders/{id}/items/{itemId}',
        tags: ['Orders'],
        summary: 'Remove item from an order',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Item removed successfully'),
            new OA\Response(response: 400, description: 'Order is not pending'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order or item not found'),
        ]
    )]
    public function removeItem($id, $itemId)
    {
        $user = Auth::user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the order owner can remove items.',
                'data' => null,
            ], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove items from an order that is not pending.',
                'data' => null,
            ], 400);
        }

        $orderItem = OrderItem::where('order_id', $order->id)
            ->where('id', $itemId)
            ->first();

        if (!$orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found',
                'data' => null,
            ], 404);
        }

        DB::beginTransaction();
        try {
            $orderItem->delete();

            $newTotal = OrderItem::where('order_id', $order->id)->sum('subtotal');
            $order->total_amount = $newTotal + ($order->unique_nominal ?? 0);
            $order->save();

            $order->load('store', 'orderItems.menu');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item removed successfully',
                'data' => $order,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove item',
                'data' => null,
            ], 500);
        }
    }

    #[OA\Put(
        path: '/api/orders/{id}/cancel',
        tags: ['Orders'],
        summary: 'Cancel an order',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Order cancelled successfully'),
            new OA\Response(response: 400, description: 'Order cannot be cancelled (completed/preparing/ready)'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Order not found'),
        ]
    )]
    public function cancel($id)
    {
        $user = Auth::user();
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
                'data' => null,
            ], 404);
        }

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the order owner can cancel this order.',
                'data' => null,
            ], 403);
        }

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Order is already completed or cancelled.',
                'data' => null,
            ], 400);
        }

        if ($order->status === 'preparing' || $order->status === 'ready') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel order that is being prepared or ready.',
                'data' => null,
            ], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        $order->load('store', 'orderItems.menu', 'user');

        $storeName = $order->store->name ?? 'Toko';
        $customerName = $order->user->name ?? 'Pelanggan';

        NotificationService::notifyCustomer(
            $order, 'Pesanan Dibatalkan',
            "Pesanan di {$storeName} telah dibatalkan.",
            'order_cancelled'
        );

        NotificationService::notifySeller(
            $order, 'Pesanan Dibatalkan',
            "Pesanan #{$order->id} dibatalkan oleh {$customerName}.",
            'order_cancelled'
        );

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order,
        ]);
    }
}