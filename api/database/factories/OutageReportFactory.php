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
     * Generates realistic outages centered in Estado Carabobo, Venezuela (Valencia, Naguanagua, San Diego).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Carabobo (Valencia Metropolitan Area): Lat 10.1500 - 10.2800, Lng -68.0400 - -67.9200
        $latitude = $this->faker->latitude(10.1600, 10.2700);
        $longitude = $this->faker->longitude(-68.0400, -67.9300);
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
                'Explosión de transformador en la Av. Bolívar Norte cerca de El Viñedo.',
                'Falla en la subestación La Quizanda afectando varias urbanizaciones.',
                'Sector El Trigal Norte sin servicio eléctrico tras fuertes lluvias.',
                'Avería en circuito de distribución en Naguanagua cerca de La Granja.',
                'Apagón general en San Diego por caída de línea de media tensión.',
                'Fluctuaciones severas (bajones) y corte total en Mañongo y Tazajal.',
                'Corte imprevisto afectando comercios y residencias en Prebo.',
                'Sector Las Chimeneas y Los Mangos a oscuras desde horas de la tarde.',
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
