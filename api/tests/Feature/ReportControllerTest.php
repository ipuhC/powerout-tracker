<?php

namespace Tests\Feature;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_valid_report(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/reports', [
            'latitude' => 10.1234,
            'longitude' => -68.1234,
            'radius_km' => 5.5,
            'duration_type' => 'fixed',
            'duration_hours' => 4,
            'description' => 'Transformer blew up.'
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.report.radius_km', 5.5)
            ->assertJsonPath('data.report.duration_type', 'fixed')
            ->assertJsonPath('data.report.duration_hours', 4)
            ->assertJsonPath('data.report.description', 'Transformer blew up.');

        $this->assertDatabaseHas('outage_reports', [
            'user_id' => $user->id,
            'radius_km' => 5.5,
            'duration_type' => 'fixed',
            'status' => OutageStatus::ACTIVE->value,
        ]);
    }

    public function test_report_creation_fails_with_invalid_data(): void
    {
        $user = User::factory()->create();

        // Invalid radius (>25), missing coordinates, invalid duration type
        $response = $this->actingAs($user)->postJson('/api/v1/reports', [
            'radius_km' => 50,
            'duration_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
            
        $details = $response->json('error.details');
        $this->assertArrayHasKey('latitude', $details);
        $this->assertArrayHasKey('longitude', $details);
        $this->assertArrayHasKey('radius_km', $details);
        $this->assertArrayHasKey('duration_type', $details);
    }

    public function test_user_cannot_exceed_active_reports_quota(): void
    {
        $user = User::factory()->create();

        // Create 3 active reports (the quota is 3)
        OutageReport::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => OutageStatus::ACTIVE->value,
            'expires_at' => now()->addHours(2)
        ]);

        // 4th report should fail with 422 QUOTA_EXCEEDED
        $response = $this->actingAs($user)->postJson('/api/v1/reports', [
            'latitude' => 10.1234,
            'longitude' => -68.1234,
            'radius_km' => 5,
            'duration_type' => 'fixed',
            'duration_hours' => 2
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'QUOTA_EXCEEDED');
    }

    public function test_user_can_edit_own_report(): void
    {
        $user = User::factory()->create();
        $report = OutageReport::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::ACTIVE->value,
            'radius_km' => 5,
        ]);

        $response = $this->actingAs($user)->patchJson("/api/v1/reports/{$report->id}", [
            'radius_km' => 10,
            'description' => 'Updated description'
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.report.radius_km', 10)
            ->assertJsonPath('data.report.description', 'Updated description');

        $this->assertDatabaseHas('outage_reports', [
            'id' => $report->id,
            'radius_km' => 10,
            'description' => 'Updated description'
        ]);
    }

    public function test_user_cannot_edit_others_report(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $report = OutageReport::factory()->create([
            'user_id' => $user1->id,
            'status' => OutageStatus::ACTIVE->value,
        ]);

        // User 2 tries to edit User 1's report
        $response = $this->actingAs($user2)->patchJson("/api/v1/reports/{$report->id}", [
            'radius_km' => 10,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_close_own_report(): void
    {
        $user = User::factory()->create();
        $report = OutageReport::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::ACTIVE->value,
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/reports/{$report->id}/close");

        $response->assertStatus(200)
            ->assertJsonPath('data.report.status', 'closed');

        $this->assertDatabaseHas('outage_reports', [
            'id' => $report->id,
            'status' => OutageStatus::CLOSED->value,
        ]);
    }

    public function test_user_cannot_close_others_report(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $report = OutageReport::factory()->create([
            'user_id' => $user1->id,
            'status' => OutageStatus::ACTIVE->value,
        ]);

        // User 2 tries to close User 1's report
        $response = $this->actingAs($user2)->postJson("/api/v1/reports/{$report->id}/close");

        $response->assertStatus(403);
    }

    public function test_rate_limiting_on_report_creation(): void
    {
        $user = User::factory()->create();
        
        // The rate limit is 5 per hour. We make 5 valid requests.
        // But since active quota is 3, to not fail with QUOTA_EXCEEDED, 
        // we must make the quota check pass, or we just submit invalid data that hits throttle before quota.
        // Actually, throttle happens BEFORE controller logic. So we can just send missing data.

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson('/api/v1/reports', []);
        }

        // 6th request should hit 429 Too Many Requests
        $response = $this->actingAs($user)->postJson('/api/v1/reports', []);
        
        $response->assertStatus(429);
    }
}
