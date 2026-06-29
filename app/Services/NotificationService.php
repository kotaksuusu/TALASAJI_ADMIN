<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function create(
        string $userId,
        ?string $orderId,
        string $title,
        string $body,
        string $type = 'order_status'
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    public static function notifyCustomer(
        object $order,
        string $title,
        string $body,
        string $type = 'order_status'
    ): Notification {
        return self::create($order->user_id, $order->id, $title, $body, $type);
    }

    public static function notifySeller(
        object $order,
        string $title,
        string $body,
        string $type = 'order_status'
    ): ?Notification {
        $sellerId = $order->store?->user_id;
        if (!$sellerId) return null;
        return self::create($sellerId, $order->id, $title, $body, $type);
    }
}
