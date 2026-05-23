<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $all     = Store::with(['owner', 'menus'])->latest()->get();
        $active  = $all->where('registration_status', 'active')->values();
        $pending = $all->where('registration_status', 'pending')->values();

        $totalAll     = $all->count();
        $totalActive  = $active->count();
        $totalPending = $pending->count();

        $avgDaysInQueue = $pending->avg(function ($s) {
            return now()->diffInDays($s->created_at);
        }) ?? 0;

        $requiresAction = $pending->filter(function ($s) {
            return now()->diffInDays($s->created_at) <= 3;
        })->count();

        $allPartnersJson = $all->map(function ($s) {
            return [
                'id'          => $s->id,
                'umkId'       => '#UMK-' . str_pad($s->id, 3, '0', STR_PAD_LEFT),
                'name'        => $s->name,
                'description' => $s->description ?? '-',
                'category'    => $s->category ?? 'Kuliner Lokal',
                'location'    => $s->address ?? '-',
                'latitude'    => $s->latitude,
                'longitude'   => $s->longitude,
                'status'      => $s->registration_status === 'active'
                                    ? 'Active'
                                    : ($s->registration_status === 'pending' ? 'Pending' : 'Inactive'),
                'regStatus'   => $s->registration_status,
                'image'       => $s->logo ? asset('storage/' . $s->logo) : null,
                'telepon'     => $s->phone ?? '-',
                'jamBuka'     => $s->open_time ?? 'Tidak tersedia',
                'jamTutup'    => $s->close_time ?? 'Tidak tersedia',
                'owner'       => optional($s->owner)->name ?? '-',
                'layanan'     => $s->service_type ?? '-',
                'appliedAt'   => optional($s->created_at)->toISOString(),
                'isNew'       => now()->diffInDays($s->created_at) <= 3,
                'menuCount'   => $s->menus ? $s->menus->count() : 0,
            ];
        })->values();

        return view('admin.stores.index', compact(
            'all', 'active', 'pending',
            'totalAll', 'totalActive', 'totalPending',
            'avgDaysInQueue', 'requiresAction',
            'allPartnersJson'
        ));
    }

    public function show($id)
    {
        $store = Store::with('owner')->findOrFail($id);
        return response()->json(['success' => true, 'data' => $store]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate(['status' => 'required|in:open,closed']);
        $store = Store::findOrFail($id);
        $store->update(['operational_status' => $request->status]);
        return response()->json(['success' => true, 'data' => $store]);
    }

    public function approve($id)
    {
        $store = Store::findOrFail($id);
        $store->update([
            'registration_status' => 'active',
            'operational_status'  => 'open',
        ]);
        return response()->json(['success' => true, 'message' => 'Store approved successfully.']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason'   => 'required|string|min:20',
            'rejection_category' => 'required|string',
        ]);
        $store = Store::findOrFail($id);
        $store->update([
            'registration_status' => 'rejected',
            'operational_status'  => 'closed',
            'rejection_reason'    => $request->rejection_reason,
            'rejection_category'  => $request->rejection_category,
        ]);
        return response()->json(['success' => true, 'message' => 'Store rejected.']);
    }
}
