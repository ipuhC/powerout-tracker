<?php

namespace Tests\Feature;

use App\Enums\OutageStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOutageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_active_outages_within_bounds(): void
    {
        // Outage inside bounds
        OutageReport::factory()->create([
            'status' => OutageStatus::ACTIVE->value,
            'expires_at' => now()->addHours(2),
        ])->setCoordinates(10.5, -68.5)->save();

        // Outage outside bounds
        OutageReport::factory()->create([
            'status' => OutageStatus::ACTIVE->value,
            'expires_at' => now()->addHours(2),
        ])->setCoordinates(20.0, -80.0)->save();

        // Expired outage inside bounds
        OutageReport::factory()->create([
            'status' => OutageStatus::EXPIRED->value,
        ])->setCoordinates(10.5, -68.5)->save();

        // Bounding box around (10.5, -68.5)
        $response = $this->getJson('/api/v1/public/outages?min_lat=9.0&min_lng=-69.0&max_lat=11.0&max_lng=-67.0');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.outages')
            ->assertJsonPath('data.outages.0.latitude', 10.5)
            ->assertJsonPath('data.outages.0.longitude', -68.5);
    }

    public function test_public_endpoint_does_not_leak_personal_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Secret',
            'email' => 'john.secret@example.com'
        ]);

        OutageReport::factory()->create([
            'user_id' => $user->id,
            'status' => OutageStatus::ACTIVE->value,
            'description' => 'A public description'
        ])->setCoordinates(10.5, -68.5)->save();

        $response = $this->getJson('/api/v1/public/outages?min_lat=9.0&min_lng=-69.0&max_lat=11.0&max_lng=-67.0');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.outages');
            
        $data = $response->json('data.outages.0');

        // Check required public fields are present
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('latitude', $data);
        $this->assertArrayHasKey('longitude', $data);
        $this->assertArrayHasKey('radius_km', $data);
        $this->assertArrayHasKey('description', $data);
        
        // Assert PII is NOT leaked
        $this->assertArrayNotHasKey('user_id', $data);
        $this->assertArrayNotHasKey('user', $data);
        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayNotHasKey('email', $data);
        
        // Assert deep missing
        $jsonString = $response->getContent();
        $this->assertStringNotContainsString('John Secret', $jsonString);
        $this->assertStringNotContainsString('john.secret@example.com', $jsonString);
    }
}
