<?php

namespace Tests\Feature\Core\Auth;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    private function createSessionWithRefreshCookie(): array
    {
        $plan        = Plan::factory()->pyme()->create();
        $empresa     = Empresa::factory()->create();
        Suscripcion::factory()->trial()->create([
            'empresa_id' => $empresa->id,
            'plan_id'    => $plan->id,
        ]);
        $owner = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'password'   => Hash::make('password123'),
            'rol'        => 'owner',
        ]);

        $refresh = $owner->createToken('refresh', ['refresh'], now()->addDays(30));

        return [$owner, $refresh->plainTextToken];
    }

    public function test_returns_new_access_token_and_rotates_refresh(): void
    {
        [$owner, $refreshToken] = $this->createSessionWithRefreshCookie();

        $response = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'token_type', 'expires_in']])
            ->assertCookie('refresh_token')
            ->assertCookie('has_session', '1', false);
    }

    public function test_returns_401_without_cookie(): void
    {
        $this->postJson('/api/auth/refresh')->assertStatus(401);
    }

    public function test_returns_401_with_expired_refresh_token(): void
    {
        [$owner] = $this->createSessionWithRefreshCookie();
        $expired = $owner->createToken('refresh', ['refresh'], now()->subMinute());

        $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $expired->plainTextToken)
            ->postJson('/api/auth/refresh')
            ->assertStatus(401);
    }
}
