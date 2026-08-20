<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class SanctumSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_supports_api_tokens(): void
    {
        $this->assertContains(HasApiTokens::class, class_uses_recursive(User::class));
    }

    public function test_personal_access_tokens_table_exists(): void
    {
        $this->assertTrue(\Schema::hasTable('personal_access_tokens'));
    }

    public function test_user_can_create_and_authenticate_with_a_token(): void
    {
        $user = User::factory()->create();

        $newToken = $user->createToken('mobile');

        $this->assertNotEmpty($newToken->plainTextToken);
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'mobile',
        ]);

        $model = PersonalAccessToken::findToken($newToken->plainTextToken);
        $this->assertNotNull($model);
        $this->assertTrue($model->tokenable->is($user));
    }

    public function test_sanctum_token_expiration_is_configured(): void
    {
        $this->assertSame(60 * 24 * 30, config('sanctum.expiration'));
    }

    public function test_sanctum_guard_is_registered(): void
    {
        $this->assertSame('sanctum', config('auth.guards.sanctum.driver'));
    }

    public function test_cors_allows_capacitor_origin(): void
    {
        $this->assertContains('capacitor://localhost', config('cors.allowed_origins'));
        $this->assertContains('https://datenight.app', config('cors.allowed_origins'));
    }
}
