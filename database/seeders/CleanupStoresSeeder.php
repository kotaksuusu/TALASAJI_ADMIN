<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupStoresSeeder extends Seeder
{
    /**
     * Run the database seeds to delete stores other than the specified 5 stores.
     */
    public function run(): void
    {
        // Daftar nama toko yang harus DIPERTAHANKAN
        $allowedStoreNames = [
            'Warung Makan Sari Rasa',
            'Kopiku Coffee & Eatery',
            'Gudeg Mbah Sastro',
            'Camilan Kita',
            'Warung Sate Pak Budi'
        ];

        // Dapatkan semua ID toko yang tidak diperbolehkan
        $storesToDelete = Store::whereNotIn('name', $allowedStoreNames)->get();

        if ($storesToDelete->isEmpty()) {
            $this->command?->info("Tidak ada toko lain yang perlu dihapus. Database sudah bersih.");
            return;
        }

        $count = 0;
        DB::transaction(function () use ($storesToDelete, &$count) {
            foreach ($storesToDelete as $store) {
                $this->command?->comment("Menghapus toko: {$store->name} (ID: {$store->id})");

                // Hapus data berelasi secara manual untuk menghindari constraint error

                // 1. Hapus Order Items via Orders
                $orderIds = DB::table('orders')->where('store_id', $store->id)->pluck('id');
                DB::table('order_items')->whereIn('order_id', $orderIds)->delete();

                // 2. Hapus Payments via Orders
                DB::table('payments')->whereIn('order_id', $orderIds)->delete();

                // 3. Hapus Notifications via Orders
                DB::table('notifications')->whereIn('order_id', $orderIds)->delete();

                // 4. Hapus Orders
                DB::table('orders')->where('store_id', $store->id)->delete();

                // 5. Hapus Reviews
                DB::table('reviews')->where('store_id', $store->id)->delete();

                // 6. Hapus Menus
                DB::table('menus')->where('store_id', $store->id)->delete();

                // 7. Hapus Categories
                DB::table('categories')->where('store_id', $store->id)->delete();

                // 8. Hapus Tables
                DB::table('tables')->where('store_id', $store->id)->delete();

                // 9. Hapus Toko itu sendiri
                $store->delete();
                $count++;
            }
        });

        $this->command?->info("Berhasil menghapus {$count} toko lain beserta data terkaitnya.");
    }
}
