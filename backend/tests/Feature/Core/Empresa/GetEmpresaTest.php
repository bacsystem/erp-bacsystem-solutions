<?php

namespace Tests\Feature\Core\Empresa;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class GetEmpresaTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_returns_empresa_data_for_tenant(): void
    {
        [, $empresa, , , $token] = $this->actingAsOwner();

        $response = $this->withToken($token)->getJson('/api/empresa');

        $response->assertOk()
            ->assertJsonPath('data.ruc', $empresa->getRawOriginal('ruc'))
            ->assertJsonPath('data.regimen_tributario', $empresa->regimen_tributario);
    }

    public function test_returns_401_without_auth(): void
    {
        $this->getJson('/api/empresa')->assertStatus(401);
    }
}
