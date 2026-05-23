<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::first();

        $totalStores   = Store::where('registration_status', 'active')->count();
        $totalOrders   = Order::count();
        $totalPending  = Store::where('registration_status', 'pending')->count();
        $activeRegions = Store::where('registration_status', 'active')
            ->get()
            ->map(fn($s) => trim(last(explode(',', $s->address ?? ''))))
            ->filter()->unique()->count();
        $revenueMtd = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        $ordersThisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $storesNewThisMonth = Store::where('registration_status', 'active')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return view('admin.settings', compact(
            'settings',
            'totalStores', 'totalOrders', 'totalPending',
            'activeRegions', 'revenueMtd',
            'ordersThisMonth', 'storesNewThisMonth'
        ));
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'nullable|string|max:255',
            'logo'     => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settingsData = [];

        if ($request->filled('app_name')) {
            $settingsData['app_name'] = $request->app_name;
        }

        if ($request->hasFile('logo')) {
            $existing = Setting::first();
            if ($existing && $existing->logo && File::exists(public_path('uploads/' . $existing->logo))) {
                File::delete(public_path('uploads/' . $existing->logo));
            }
            $logo     = $request->file('logo');
            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->move(public_path('uploads'), $logoName);
            $settingsData['logo'] = $logoName;
        }

        if (!empty($settingsData)) {
            Setting::updateOrCreate(['id' => 1], $settingsData);
        }

        return back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required'],
            'new_password'     => ['required', 'min:8', 'confirmed'],
        ], [
            'new_password.min'          => 'Password baru minimal 8 karakter.',
            'new_password.confirmed'    => 'Konfirmasi password tidak cocok.',
            'current_password.required' => 'Password saat ini wajib diisi.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $admin = Auth::user();

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $admin->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password berhasil diperbarui.');
    }
}
