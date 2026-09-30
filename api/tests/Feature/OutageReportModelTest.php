<?php

namespace Tests\Feature;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OutageReportModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_create_outage_report_with_coordinates_and_retrieve(): void
    {
        $user = User::factory()->create();

        $report = OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 40.712776,
            'longitude' => -74.005974,
            'radius_km' => 2.50,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
            'description' => 'Substation explosion on 5th Ave',
        ]);

        $this->assertDatabaseHas('outage_reports', [
            'id' => $report->id,
            'radius_km' => 2.50,
            'status' => 'active',
        ]);

        $fresh = OutageReport::find($report->id);

        $this->assertEqualsWithDelta(40.712776, $fresh->latitude, 0.0001);
        $this->assertEqualsWithDelta(-74.005974, $fresh->longitude, 0.0001);
        $this->assertEquals(OutageStatus::ACTIVE, $fresh->status);
        $this->assertEquals(DurationType::FIXED, $fresh->duration_type);
        $this->assertGreaterThan(0, $fresh->remaining_seconds);
    }

    public function test_scope_active_guarantees_zero_stale_data_when_expired(): void
    {
        $user = User::factory()->create();

        // 1. Valid Active report
        $activeReport = OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 40.758000,
            'longitude' => -73.985500,
            'radius_km' => 1.5,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
        ]);

        // 2. Report whose expires_at is past, but status column is still 'active' (scheduler delayed scenario)
        $staleReport = OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 40.760000,
            'longitude' => -73.982000,
            'radius_km' => 1.0,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 2,
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHours(3),
            'expires_at' => now()->subHour(),
        ]);

        $activeIds = OutageReport::active()->pluck('id')->all();

        $this->assertContains($activeReport->id, $activeIds);
        $this->assertNotContains($staleReport->id, $activeIds, 'Expired report MUST NOT be returned by scopeActive (Tier-1 Zero-Stale-Data Guarantee)');
    }

    public function test_scope_within_bounding_box_filters_geospatially(): void
    {
        $user = User::factory()->create();

        // Inside NYC Manhattan envelope (Lat 40.70 to 40.80, Lng -74.02 to -73.95)
        $manhattanReport = OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 40.750000,
            'longitude' => -73.990000,
            'radius_km' => 1.0,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 2,
        ]);

        // Far outside in Los Angeles (Lat ~34.05, Lng ~-118.24)
        $laReport = OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 34.052200,
            'longitude' => -118.243700,
            'radius_km' => 1.0,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 2,
        ]);

        $results = OutageReport::withinBoundingBox(40.70, -74.05, 40.80, -73.90)->pluck('id')->all();

        $this->assertContains($manhattanReport->id, $results);
        $this->assertNotContains($laReport->id, $results);
    }

    public function test_expiration_calculation_respects_72_hour_hard_cap(): void
    {
        $fixedExpires = OutageReport::calculateExpiresAt(DurationType::FIXED, 10, now());
        $this->assertEquals(10, round(now()->diffInHours($fixedExpires)));

        // Fixed capped at 72 hours even if 100 is requested
        $fixedCapped = OutageReport::calculateExpiresAt(DurationType::FIXED, 100, now());
        $this->assertEquals(72, round(now()->diffInHours($fixedCapped)));

        // Until Closed automatically receives 72 hours safety cap
        $untilClosedExpires = OutageReport::calculateExpiresAt(DurationType::UNTIL_CLOSED, null, now());
        $this->assertEquals(72, round(now()->diffInHours($untilClosedExpires)));
    }

    public function test_user_active_reports_count_relationship(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(0, $user->activeReportsCount());

        OutageReport::create([
            'user_id' => $user->id,
            'latitude' => 40.71,
            'longitude' => -74.01,
            'radius_km' => 1.0,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 3,
        ]);

        $this->assertEquals(1, $user->fresh()->activeReportsCount());
    }
}
