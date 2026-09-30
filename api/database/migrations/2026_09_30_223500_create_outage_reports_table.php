<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('outage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // PostGIS WGS84 Geodetic Point Geography
            $table->geography('location', subtype: 'point', srid: 4326);

            // Outage radius in kilometers (0.50 km to 25.00 km)
            $table->decimal('radius_km', 4, 2);

            // Duration configuration
            $table->string('duration_type', 20);
            $table->unsignedSmallInteger('duration_hours')->nullable();

            // Optional incident notes (max 500 characters)
            $table->string('description', 500)->nullable();

            // Status and lifecycle timestamps
            $table->string('status', 20)->default('active');
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('expires_at');

            $table->timestamps();

            // High-throughput query indexes (Laravel Blueprint native syntax)
            $table->spatialIndex('location', 'idx_outage_reports_location_gist');
            $table->index(['status', 'expires_at'], 'idx_outage_reports_status_expires_at');
            $table->index(['user_id', 'status'], 'idx_outage_reports_user_id_status');
        });

        // Partial Index for Scheduler background daemon (requires raw SQL in PostgreSQL due to WHERE clause)
        DB::statement("CREATE INDEX idx_outage_reports_pending_expiration ON outage_reports (expires_at) WHERE status = 'active';");

        // 3. Database-level CHECK Constraints for strict data integrity
        DB::statement('
            ALTER TABLE outage_reports
            ADD CONSTRAINT chk_outage_reports_radius
            CHECK (radius_km >= 0.50 AND radius_km <= 25.00);
        ');

        DB::statement("
            ALTER TABLE outage_reports
            ADD CONSTRAINT chk_outage_reports_duration_type
            CHECK (duration_type IN ('fixed', 'until_closed'));
        ");

        DB::statement("
            ALTER TABLE outage_reports
            ADD CONSTRAINT chk_outage_reports_status
            CHECK (status IN ('active', 'closed', 'expired'));
        ");

        DB::statement("
            ALTER TABLE outage_reports
            ADD CONSTRAINT chk_outage_reports_duration_hours
            CHECK (
                (duration_type = 'fixed' AND duration_hours >= 1 AND duration_hours <= 72)
                OR (duration_type = 'until_closed' AND duration_hours IS NULL)
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outage_reports');
    }
};
