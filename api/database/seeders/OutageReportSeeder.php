<?php

namespace Database\Seeders;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class OutageReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Demo Users exist
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@powerout.test'],
            [
                'name' => 'Demo User',
                'password' => bcrypt('password'),
            ]
        );

        $jane = User::firstOrCreate(
            ['email' => 'jane@powerout.test'],
            [
                'name' => 'Jane Citizen',
                'password' => bcrypt('password'),
            ]
        );

        $alex = User::firstOrCreate(
            ['email' => 'alex@powerout.test'],
            [
                'name' => 'Alex Rivera',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Demo User Outages (1 active fixed, 1 active until_closed, 1 closed, 1 expired)
        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 40.754932,
            'longitude' => -73.984016,
            'radius_km' => 2.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
            'description' => 'Blackout across Times Square commercial corridor. Traffic signals disabled.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHour(),
            'expires_at' => now()->addHours(3),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 40.712776,
            'longitude' => -74.005974,
            'radius_km' => 1.50,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'Substation explosion on 5th Ave. Entire neighborhood dark since storm started.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHours(2)->addHours(72),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 40.692800,
            'longitude' => -73.990300,
            'radius_km' => 1.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 2,
            'description' => 'Downtown Brooklyn local feeder line repair. Power successfully restored.',
            'status' => OutageStatus::CLOSED,
            'closed_at' => now()->subMinutes(45),
            'created_at' => now()->subHours(3),
            'expires_at' => now()->subHours(1),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 40.807500,
            'longitude' => -73.946500,
            'radius_km' => 3.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 1,
            'description' => 'Brief storm surge outage in Harlem.',
            'status' => OutageStatus::EXPIRED,
            'closed_at' => null,
            'created_at' => now()->subHours(4),
            'expires_at' => now()->subHours(3),
        ]);

        // 3. Overlapping Outages in Midtown Manhattan (Demonstrating ADR-004 Visual Alpha Blending)
        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 40.758000,
            'longitude' => -73.985500,
            'radius_km' => 2.50,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 6,
            'description' => 'Severe electrical failure near Rockefeller Center.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(330),
        ]);

        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 40.760000,
            'longitude' => -73.982000,
            'radius_km' => 1.80,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'High-voltage cable cut on 50th Street.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(20)->addHours(72),
        ]);

        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 40.752000,
            'longitude' => -73.978000,
            'radius_km' => 2.20,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 5,
            'description' => 'Grand Central vicinity power surge cut off multiple blocks.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(15),
            'expires_at' => now()->addMinutes(285),
        ]);

        // 4. Outer Borough Outages (Brooklyn & Queens)
        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 40.708100,
            'longitude' => -73.957100,
            'radius_km' => 3.50,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 8,
            'description' => 'Williamsburg waterfront substation trip. Multiple residential complexes affected.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(50),
            'expires_at' => now()->addMinutes(430),
        ]);

        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 40.764400,
            'longitude' => -73.923500,
            'radius_km' => 2.80,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
            'description' => 'Astoria power failure impacting subway ventilation and residential streetlights.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(40),
            'expires_at' => now()->addMinutes(200),
        ]);

        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 40.787000,
            'longitude' => -73.975400,
            'radius_km' => 1.60,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'Upper West Side brownout affecting elevators and street illumination.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHours(1),
            'expires_at' => now()->subHours(1)->addHours(72),
        ]);
    }
}
