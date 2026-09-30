<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We do not drop postgis extension in production/dev automatically to avoid breaking other schemas
        // DB::statement('DROP EXTENSION IF EXISTS postgis CASCADE;');
    }
};
