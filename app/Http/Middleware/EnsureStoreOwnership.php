<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Table;
use App\Models\Order;
use App\Models\Payment;

class EnsureStoreOwnership
{
    /**
     * Mapping URI prefix → model class untuk route param {id}.
     * Urutan penting: spesifik dulu baru generik.
     */
    private const URI_MODEL_MAP = [
        'stores'     => Store::class,
        'categories' => Category::class,
        'menus'      => Menu::class,
        'tables'     => Table::class,
        'orders'     => Order::class,
        'payments'   => Payment::class,
    ];

    /**
     * Mapping model → field yang berisi store_id.
     */
    private const STORE_ID_FIELD = [
        Store::class    => 'id',
        Category::class => 'store_id',
        Menu::class     => 'store_id',
        Table::class    => 'store_id',
        Order::class    => 'store_id',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        $storeId = $this->resolveStoreId($request);

        if (!$storeId) {
            return response()->json([
                'success' => false,
                'message' => 'Store context not found.',
                'data' => null,
            ], 403);
        }

        $owned = Store::where('id', $storeId)
            ->where(fn($q) => $q->where('user_id', $user->id)->orWhere('seller_id', $user->id))
            ->exists();

        if (!$owned) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage this store.',
                'data' => null,
            ], 403);
        }

        return $next($request);
    }

    private function resolveStoreId(Request $request): ?string
    {
        // 1. Route model binding — cek named params: store, category, menu, table, order, payment
        $namedParams = ['store', 'category', 'menu', 'table', 'order', 'payment'];
        foreach ($namedParams as $param) {
            $model = $request->route($param);
            if (!$model) {
                continue;
            }

            if ($model instanceof Payment) {
                $order = $model->order;
                return $order?->store_id;
            }

            $class = get_class($model);
            if (isset(self::STORE_ID_FIELD[$class])) {
                return (string) $model->{self::STORE_ID_FIELD[$class]};
            }
        }

        // 2. Generic {id} — cari tahu dari URI prefix
        $id = $request->route('id');
        if ($id) {
            $uri = $request->path();
            foreach (self::URI_MODEL_MAP as $prefix => $class) {
                if (str_contains($uri, $prefix)) {
                    $model = $class::find($id);
                    if (!$model) {
                        continue; // maybe different resource with same id prefix
                    }

                    if ($class === Payment::class) {
                        $order = $model->order;
                        return $order?->store_id;
                    }

                    if (isset(self::STORE_ID_FIELD[$class])) {
                        return (string) $model->{self::STORE_ID_FIELD[$class]};
                    }
                }
            }
        }

        // 3. Request body / query: store_id
        $storeId = $request->input('store_id') ?? $request->query('store_id');
        if ($storeId) {
            return $storeId;
        }

        // 4. Indirect via category_id
        $categoryId = $request->input('category_id');
        if ($categoryId) {
            return Category::where('id', $categoryId)->value('store_id');
        }

        // 5. orderId route param (payment routes: /payments/{orderId}/proof)
        $orderId = $request->route('orderId');
        if ($orderId) {
            $order = Order::find($orderId);
            return $order?->store_id;
        }

        return null;
    }
}
