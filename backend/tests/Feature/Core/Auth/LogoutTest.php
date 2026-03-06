<?php

namespace Tests\Feature\Core\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_logout_deletes_all_tokens_and_clears_cookies(): void
    {
        [, , , , $token] = $this->actingAsOwner();

        $response = $this->withToken($token)->postJson('/api/auth/logout');

        $response->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada');

        // Verificar cookies borradas
        $this->assertEquals(0, $response->headers->getCookies()[0]->getMaxAge());

        $this->assertDatabaseHas('audit_logs', ['accion' => 'logout_all']);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $this->postJson('/api/auth/logout')->assertStatus(401);
    }
}
