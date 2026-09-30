<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_register_successfully(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Maria Gonzalez',
            'email' => 'maria@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'token',
                    'token_type',
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'name' => 'Maria Gonzalez',
                        'email' => 'maria@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'maria@example.com']);
    }

    public function test_user_registration_fails_with_validation_errors(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                    'details' => ['name', 'email', 'password'],
                ],
            ])
            ->assertJson([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                ],
            ]);
    }

    public function test_user_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Duplicate User',
            'email' => 'duplicate@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonPath('error.details.email.0', 'This email address is already registered.');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('SecurePass123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'SecurePass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'token',
                    'token_type',
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'login@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);
    }

    public function test_user_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => bcrypt('CorrectPassword123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'valid@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 'INVALID_CREDENTIALS',
                    'message' => 'These credentials do not match our records.',
                ],
            ]);
    }

    public function test_user_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['email', 'password']]]);
    }

    public function test_authenticated_user_can_access_me_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'profile@example.com',
        ]);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at'],
                    'active_reports_count',
                    'active_reports_quota',
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'profile@example.com',
                    ],
                    'active_reports_count' => 0,
                    'active_reports_quota' => 3,
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_me_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required. Please provide a valid Bearer token.',
                ],
            ]);
    }

    public function test_authenticated_user_can_logout_and_revoke_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'message' => 'Successfully logged out.',
                ],
            ]);

        // Clear in-memory guard cache so Sanctum re-evaluates database token
        $this->app['auth']->forgetGuards();

        // Revoked token should now be rejected
        $retryResponse = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/auth/me');

        $retryResponse->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}
