<?php

namespace Database\Factories;

use App\Enums\DurationType;
use App\Enums\OutageStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OutageReport>
 */
class OutageReportFactory extends Factory
{
    protected $model = OutageReport::class;

    /**
     * Define the model's default state.
     * Generates realistic outages centered in the New York City metropolitan area.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // NYC Bounding Range: Lat 40.65 - 40.85, Lng -74.05 - -73.85
        $latitude = $this->faker->latitude(40.6800, 40.8200);
        $longitude = $this->faker->longitude(-74.0300, -73.9000);
        $durationType = $this->faker->randomElement([DurationType::FIXED, DurationType::UNTIL_CLOSED]);
        $durationHours = $durationType === DurationType::FIXED ? $this->faker->numberBetween(1, 24) : null;
        $createdAt = now()->subMinutes($this->faker->numberBetween(5, 180));

        $expiresAt = OutageReport::calculateExpiresAt(
            $durationType,
            $durationHours,
            $createdAt
        );

        return [
            'user_id' => User::factory(),
            'latitude' => round($latitude, 6),
            'longitude' => round($longitude, 6),
            'radius_km' => $this->faker->randomElement([0.8, 1.2, 1.5, 2.0, 2.5, 3.5, 5.0]),
            'duration_type' => $durationType,
            'duration_hours' => $durationHours,
            'description' => $this->faker->randomElement([
                'Transformer blew up near 8th Ave and 42nd St.',
                'Entire block without electricity since heavy winds started.',
                'Traffic lights and brownstones pitch dark.',
                'Substation maintenance failure impacting multiple residential towers.',
                'Underground cable issue reported by utility crew.',
                'Power flickering and now completely cut off.',
            ]),
            'status' => OutageStatus::ACTIVE,
            'closed_at' => null,
            'expires_at' => $expiresAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /**
     * Indicate that the outage report is currently active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OutageStatus::ACTIVE,
            'closed_at' => null,
            'expires_at' => now()->addHours(rand(1, 12)),
        ]);
    }

    /**
     * Indicate that the outage report has been manually closed.
     */
    public function closed(): static
    {
        return $this->state(function (array $attributes) {
            $closedAt = now()->subMinutes(rand(15, 120));

            return [
                'status' => OutageStatus::CLOSED,
                'closed_at' => $closedAt,
                'expires_at' => $closedAt->copy()->addHours(4),
            ];
        });
    }

    /**
     * Indicate that the outage report has reached expiration.
     */
    public function expired(): static
    {
        return $this->state(function (array $attributes) {
            $expiredAt = now()->subMinutes(rand(10, 300));
            $createdAt = $expiredAt->copy()->subHours(2);

            return [
                'status' => OutageStatus::EXPIRED,
                'closed_at' => null,
                'created_at' => $createdAt,
                'expires_at' => $expiredAt,
            ];
        });
    }

    /**
     * Set a fixed duration in hours.
     */
    public function fixed(int $hours = 4): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => DurationType::FIXED,
            'duration_hours' => $hours,
        ]);
    }

    /**
     * Set an until-closed duration type.
     */
    public function untilClosed(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
        ]);
    }
}
