<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupabaseRealtimeService
{
    protected string $projectUrl;
    protected string $anonKey;

    public function __construct()
    {
        $this->projectUrl = config('supabase.url');
        $this->anonKey = config('supabase.anon_key');
    }

    public function broadcast(string $channel, string $event, array $payload): bool
    {
        $response = Http::withHeaders([
            'apikey' => $this->anonKey,
            'Authorization' => 'Bearer ' . $this->anonKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->projectUrl}/realtime/v1/broadcast", [
            'channel' => $channel,
            'event' => $event,
            'payload' => $payload,
        ]);

        return $response->successful();
    }

    public function orderStatusChanged(int $orderId, string $status, array $data = []): bool
    {
        return $this->broadcast(
            channel: "orders:{$orderId}",
            event: 'order_status',
            payload: array_merge(['order_id' => $orderId, 'status' => $status], $data)
        );
    }

    public function newOrder(int $storeId, array $orderData): bool
    {
        return $this->broadcast(
            channel: "orders:store:{$storeId}",
            event: 'new_order',
            payload: $orderData
        );
    }

    public function paymentConfirmed(int $orderId, string $paymentStatus): bool
    {
        return $this->broadcast(
            channel: "orders:{$orderId}",
            event: 'payment_status',
            payload: ['order_id' => $orderId, 'payment_status' => $paymentStatus]
        );
    }

    public function notifyUser(int $userId, string $title, string $body, array $data = []): bool
    {
        return $this->broadcast(
            channel: "notifications:{$userId}",
            event: 'notification',
            payload: array_merge(['title' => $title, 'body' => $body], $data)
        );
    }
}
