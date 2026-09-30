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
     * Generates realistic outages located in Estado Carabobo, Venezuela (Valencia, Naguanagua, San Diego).
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

        // 2. Demo User Outages in Valencia, Carabobo (1 active fixed, 1 active until_closed, 1 closed, 1 expired)
        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.213500,
            'longitude' => -68.005200,
            'radius_km' => 2.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
            'description' => 'Apagón general en El Viñedo y Av. Carlos Sanda. Comercios y semáforos sin energía eléctrica.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHour(),
            'expires_at' => now()->addHours(3),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.182000,
            'longitude' => -68.001000,
            'radius_km' => 1.50,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'Explosión de transformador en el casco central de Valencia cerca de la Plaza Bolívar. Varias cuadras a oscuras.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHours(2)->addHours(72),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.165000,
            'longitude' => -67.978000,
            'radius_km' => 1.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 2,
            'description' => 'Avería en circuito industrial de La Isabelica. Servicio restablecido por cuadrilla de Corpoelec.',
            'status' => OutageStatus::CLOSED,
            'closed_at' => now()->subMinutes(45),
            'created_at' => now()->subHours(3),
            'expires_at' => now()->subHours(1),
        ]);

        OutageReport::create([
            'user_id' => $demoUser->id,
            'latitude' => 10.220000,
            'longitude' => -68.016000,
            'radius_km' => 2.50,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 1,
            'description' => 'Corte breve por maniobras de mantenimiento en la subestación Prebo.',
            'status' => OutageStatus::EXPIRED,
            'closed_at' => null,
            'created_at' => now()->subHours(4),
            'expires_at' => now()->subHours(3),
        ]);

        // 3. Overlapping Outages in Valencia Norte (Demonstrating ADR-004 Visual Alpha Blending in El Trigal & Las Chimeneas)
        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 10.218000,
            'longitude' => -67.986000,
            'radius_km' => 2.20,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 6,
            'description' => 'Fuerte falla en El Trigal Centro tras caída de ramas sobre las líneas del tendido eléctrico.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(330),
        ]);

        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 10.225000,
            'longitude' => -67.982000,
            'radius_km' => 1.80,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'Sin servicio eléctrico en Las Chimeneas y sector Trigal Norte. Línea reventada en la vía principal.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(20),
            'expires_at' => now()->subMinutes(20)->addHours(72),
        ]);

        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 10.222000,
            'longitude' => -67.995000,
            'radius_km' => 2.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 5,
            'description' => 'Fluctuación severa (bajón) y corte total en Guaparo, Los Mangos y zonas aledañas.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(15),
            'expires_at' => now()->addMinutes(285),
        ]);

        // 4. Municipalities of Naguanagua & San Diego (Carabobo)
        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 10.248000,
            'longitude' => -68.004000,
            'radius_km' => 3.00,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 8,
            'description' => 'Corte general en Naguanagua: La Granja, Mañongo y Tazajal sin electricidad.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(50),
            'expires_at' => now()->addMinutes(430),
        ]);

        OutageReport::create([
            'user_id' => $jane->id,
            'latitude' => 10.252000,
            'longitude' => -67.953000,
            'radius_km' => 2.80,
            'duration_type' => DurationType::FIXED,
            'duration_hours' => 4,
            'description' => 'Falla de circuito en el municipio San Diego afectando El Remanso y urbanizaciones cercanas.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subMinutes(40),
            'expires_at' => now()->addMinutes(200),
        ]);

        OutageReport::create([
            'user_id' => $alex->id,
            'latitude' => 10.205000,
            'longitude' => -67.959000,
            'radius_km' => 1.80,
            'duration_type' => DurationType::UNTIL_CLOSED,
            'duration_hours' => null,
            'description' => 'Corte imprevisto en Morro II y zona comercial de Castillito en San Diego.',
            'status' => OutageStatus::ACTIVE,
            'created_at' => now()->subHours(1),
            'expires_at' => now()->subHours(1)->addHours(72),
        ]);
    }
}
