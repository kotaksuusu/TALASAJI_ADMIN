<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('registration_status')->default('pending')->after('operational_status');
            $table->string('rejection_reason')->nullable()->after('registration_status');
            $table->string('rejection_category')->nullable()->after('rejection_reason');
            $table->time('open_time')->nullable()->after('rejection_category');
            $table->time('close_time')->nullable()->after('open_time');
            $table->string('category')->nullable()->after('close_time');
            $table->string('service_type')->nullable()->after('category');
        });
    }

    public function down(): void {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'registration_status','rejection_reason',
                'rejection_category','open_time','close_time','category','service_type'
            ]);
        });
    }
};
