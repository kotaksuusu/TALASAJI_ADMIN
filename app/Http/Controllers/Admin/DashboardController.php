<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStores = Store::where('registration_status', 'active')->count();
        $totalOrders = Order::count();

        $activeRegions = Store::where('registration_status', 'active')
            ->get()
            ->map(function ($store) {
                $address = $store->address ?? '';
                $parts   = explode(',', $address);
                return trim(end($parts));
            })
            ->filter()
            ->unique()
            ->count();

        $totalPending = Store::where('registration_status', 'pending')->count();

        $storesThisMonth = Store::where('registration_status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $storesLastMonth = Store::where('registration_status', 'active')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $storeGrowth = $storesLastMonth > 0
            ? round((($storesThisMonth - $storesLastMonth) / $storesLastMonth) * 100, 1)
            : ($storesThisMonth > 0 ? 100 : 0);

        $ordersThisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $ordersLastMonth = Order::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $orderGrowth = $ordersLastMonth > 0
            ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1)
            : ($ordersThisMonth > 0 ? 100 : 0);

        $regionsThisMonth = Store::where('registration_status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get()
            ->map(fn($s) => trim(last(explode(',', $s->address ?? ''))))
            ->filter()->unique()->count();
        $regionsLastMonth = Store::where('registration_status', 'active')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->get()
            ->map(fn($s) => trim(last(explode(',', $s->address ?? ''))))
            ->filter()->unique()->count();
        $regionDiff = $regionsThisMonth - $regionsLastMonth;

        $revenueMtd = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $cacheKey = 'dashboard_chart_' . now()->format('Y_m');

        $chartData = Cache::remember($cacheKey, now()->addHour(), function () {
            $labels   = [];
            $orders   = [];
            $revenue  = [];

            $sixMonthsAgo = now()->subMonths(5)->startOfMonth();

            $allChartOrders = Order::where('created_at', '>=', $sixMonthsAgo)
                ->get(['created_at', 'total_amount']);

            for ($i = 5; $i >= 0; $i--) {
                $date  = now()->subMonths($i);
                $year  = $date->year;
                $month = $date->month;

                $monthOrders = $allChartOrders->filter(
                    fn($o) => $o->created_at->year === $year &&
                              $o->created_at->month === $month
                );

                $labels[]  = $date->locale('id')->isoFormat('MMM YYYY');
                $orders[]  = $monthOrders->count();
                $revenue[] = round($monthOrders->sum('total_amount') / 1000000, 2);
            }

            return compact('labels', 'orders', 'revenue');
        });

        $chartLabels  = $chartData['labels'];
        $chartOrders  = $chartData['orders'];
        $chartRevenue = $chartData['revenue'];

        $recentOrders = Order::with('store')->latest()->take(4)->get();

        return view('admin.dashboard', compact(
            'totalStores', 'totalPending', 'totalOrders',
            'activeRegions', 'revenueMtd',
            'chartLabels', 'chartOrders', 'chartRevenue',
            'recentOrders',
            'storeGrowth', 'orderGrowth', 'regionDiff'
        ));
    }
}
