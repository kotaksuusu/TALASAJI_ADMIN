<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Truncate sessions first since the old BIGINT data is incompatible
        DB::table('sessions')->truncate();

        // Change user_id from BIGINT to VARCHAR(36) to support UUID
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE VARCHAR(36) USING NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sessions ALTER COLUMN user_id TYPE BIGINT USING NULL');
    }
};
