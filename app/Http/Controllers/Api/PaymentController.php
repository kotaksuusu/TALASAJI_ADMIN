<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;
use App\Services\SupabaseStorageService;

#[OA\Tag(name: 'Payments', description: 'Payment management endpoints')]
class PaymentController extends Controller
{
    #[OA\Post(
        path: '/api/payments/{orderId}',
        tags: ['Payments'],
        summary: 'Create a payment for an order',
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'amount', type: 'number'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'bank_transfer', 'qris']),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Payment processed successfully'),
            new OA\Response(response: 400, description: 'Order not confirmed yet'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(StorePaymentRequest $request, $orderId)
    {
        $validated = $request->validated();
        $order = Order::findOrFail($orderId);

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Pembayaran hanya bisa dilakukan untuk pesanan yang aktif.',
                'data' => null,
            ], 400);
        }

        $payment = Payment::create([
            'order_id' => $orderId,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_status' => 'pending',
        ]);

        $order->load('store', 'user');
        $total = number_format($order->total_amount ?? 0, 0, ',', '.');
        $storeName = $order->store->name ?? 'Toko';
        $customerName = $order->user->name ?? 'Pelanggan';

        NotificationService::create(
            $order->user_id, $order->id,
            'Pembayaran Dikirim',
            "Pembayaran Rp {$total} untuk {$storeName} menunggu konfirmasi penjual.",
            'payment_pending'
        );

        NotificationService::notifySeller(
            $order, 'Pembayaran Baru',
            "{$customerName} telah mengirim pembayaran Rp {$total}.",
            'payment_received'
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment processed successfully',
            'data' => $payment,
        ], 201);
    }

    #[OA\Get(
        path: '/api/payments/{id}',
        tags: ['Payments'],
        summary: 'Get payment details by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment details retrieved successfully'),
            new OA\Response(response: 404, description: 'Payment not found'),
        ]
    )]
    public function show($id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment details retrieved successfully',
            'data' => $payment,
        ]);
    }

    #[OA\Get(
        path: '/api/payments/by-order/{orderId}',
        tags: ['Payments'],
        summary: 'Get payment by order ID',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment retrieved successfully'),
            new OA\Response(response: 404, description: 'Payment not found for this order'),
        ]
    )]
    public function byOrder($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found for this order',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => $payment,
        ]);
    }

    #[OA\Post(
        path: '/api/payments/callback',
        tags: ['Payments'],
        summary: 'Payment callback webhook',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer'),
                    new OA\Property(property: 'payment_status', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Callback processed successfully'),
            new OA\Response(response: 404, description: 'Pending payment not found'),
        ]
    )]
    public function callback(Request $request)
    {
        $payment = Payment::where('payment_status', 'pending')->whereHas('order', function ($q) use ($request) {
            $q->where('id', $request->order_id);
        })->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        $payment->update([
            'payment_status' => $request->payment_status ?? 'confirmed',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Callback processed successfully',
            'data' => $payment,
        ]);
    }

    #[OA\Put(
        path: '/api/payments/{id}/confirm',
        tags: ['Payments'],
        summary: 'Confirm a payment',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment confirmed successfully'),
            new OA\Response(response: 400, description: 'Payment already processed'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Payment not found'),
        ]
    )]
    public function confirm($id)
    {
        // Role & ownership verified by middleware (role:penjual,pemilik,admin + store.owner)

        $payment = Payment::findOrFail($id);

        if ($payment->payment_status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be confirmed',
                'data' => null,
            ], 400);
        }

        $payment->update(['payment_status' => 'confirmed']);

        $order = $payment->order()->with('store')->first();
        if ($order) {
            $storeName = $order->store->name ?? 'Toko';
            $total = number_format($payment->amount ?? 0, 0, ',', '.');
            NotificationService::notifyCustomer(
                $order, 'Pembayaran Dikonfirmasi',
                "Pembayaran Rp {$total} untuk {$storeName} dikonfirmasi. Pesanan diproses.",
                'payment_confirmed'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully',
            'data' => $payment,
        ]);
    }

    #[OA\Put(
        path: '/api/payments/{id}/cancel',
        tags: ['Payments'],
        summary: 'Cancel a payment',
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment cancelled/refunded successfully'),
            new OA\Response(response: 400, description: 'Payment already cancelled'),
            new OA\Response(response: 403, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Payment not found'),
        ]
    )]
    public function cancel($id)
    {
        // Role & ownership verified by middleware (role:penjual,pemilik,admin + store.owner)

        $payment = Payment::findOrFail($id);

        if ($payment->payment_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Payment is already cancelled',
                'data' => null,
            ], 400);
        }

        $payment->update(['payment_status' => 'cancelled']);

        $order = $payment->order()->with('store')->first();
        if ($order) {
            $storeName = $order->store->name ?? 'Toko';
            $total = number_format($payment->amount ?? 0, 0, ',', '.');
            NotificationService::notifyCustomer(
                $order, 'Pembayaran Dibatalkan',
                "Pembayaran Rp {$total} untuk {$storeName} dibatalkan.",
                'payment_cancelled'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled/refunded successfully',
            'data' => $payment,
        ]);
    }

    public function uploadProof(UploadPaymentProofRequest $request, $orderId, SupabaseStorageService $supabase)
    {
        $user = Auth::user();
        $order = Order::findOrFail($orderId);

        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the order owner can upload payment proof.',
                'data' => null,
            ], 403);
        }

        $validated = $request->validated();
        $file = $request->file('payment_proof');
        $filename = 'payment_' . $orderId . '_' . now()->timestamp . '.' . $file->getClientOriginalExtension();
        $url = $supabase->upload('payments', $file, $filename);

        if (!$url) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file to storage.',
                'data' => null,
            ], 500);
        }

        $payment = Payment::where('order_id', $orderId)->firstOrFail();
        $payment->update([
            'payment_proof' => $url,
            'payment_status' => 'submitted',
        ]);

        $order->load('store', 'user');
        $customerName = $order->user->name ?? 'Pelanggan';
        $total = number_format($order->total_amount ?? 0, 0, ',', '.');

        NotificationService::notifySeller(
            $order, 'Bukti Pembayaran',
            "{$customerName} upload bukti bayar Rp {$total}.",
            'payment_proof_uploaded'
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment proof uploaded successfully',
            'data' => $payment,
        ]);
    }
}