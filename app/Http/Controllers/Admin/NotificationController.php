<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function getNotifications(): array
    {
        $notifications = [];

        // Kondisi 1: Toko dengan rata-rata rating < 1
        $lowRatingStores = Store::where('registration_status', 'active')
            ->whereHas('reviews')
            ->withAvg('reviews', 'rating')
            ->get()
            ->filter(fn($s) => $s->reviews_avg_rating < 1);

        foreach ($lowRatingStores as $store) {
            $avg = round($store->reviews_avg_rating, 1);
            $notifications[] = [
                'type'    => 'low_rating',
                'icon'    => 'star',
                'color'   => '#e53935',
                'title'   => 'Rating Toko Sangat Rendah',
                'message' => "{$store->name} memiliki rata-rata rating {$avg} dari 5.",
                'action_url' => route('admin.umkm.show', $store->id),
                'action_label' => 'Lihat Toko',
                'time'    => now()->toIso8601String(),
            ];
        }

        // Kondisi 2: Toko pending lebih dari 3 hari
        $pendingStores = Store::where('registration_status', 'pending')
            ->where('created_at', '<=', now()->subDays(3))
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($pendingStores as $store) {
            $days = (int) now()->diffInDays($store->created_at, true);
            $notifications[] = [
                'type'    => 'pending_long',
                'icon'    => 'clock',
                'color'   => '#FF7901',
                'title'   => 'Persetujuan Toko Tertunda',
                'message' => "{$store->name} sudah menunggu persetujuan selama {$days} hari.",
                'action_url' => route('admin.umkm.index'),
                'action_label' => 'Review Sekarang',
                'time'    => $store->created_at->toIso8601String(),
            ];
        }

        return $notifications;
    }

    public function index()
    {
        $notifications = $this->getNotifications();
        return view('admin.notifications', compact('notifications'));
    }

    public function count()
    {
        return response()->json(['count' => count($this->getNotifications())]);
    }
}
