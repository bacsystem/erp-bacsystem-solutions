<?php

namespace Tests\Feature\Core\Auth;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Shared\Mail\BienvenidaMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge_recursive([
            'plan_id' => Plan::factory()->pyme()->create()->id,
            'empresa' => [
                'ruc'                => '20123456789',
                'razon_social'       => 'Test SAC',
                'nombre_comercial'   => 'Test',
                'direccion'          => 'Av. Test 123',
                'regimen_tributario' => 'RMT',
            ],
            'owner' => [
                'nombre'                => 'Test Owner',
                'email'                 => 'owner@test.com',
                'password'              => 'password123',
                'password_confirmation' => 'password123',
            ],
        ], $overrides);
    }

    public function test_registers_empresa_owner_and_trial_suscripcion(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register', $this->payload());

        $response->assertStatus(201)
            ->assertJsonPath('data.user.rol', 'owner')
            ->assertJsonPath('data.user.suscripcion.estado', 'trial')
            ->assertJsonStructure(['data' => ['access_token', 'user']]);

        $this->assertDatabaseHas('empresas', ['ruc' => '20123456789']);
        $this->assertDatabaseHas('suscripciones', ['estado' => 'trial']);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'register']);
        $response->assertCookie('refresh_token');
        $response->assertCookie('has_session', '1', false);

        Mail::assertQueued(BienvenidaMail::class);
    }

    public function test_rejects_duplicate_ruc(): void
    {
        Empresa::factory()->create(['ruc' => '20123456789']);

        $payload = $this->payload();
        unset($payload['plan_id']);
        $payload['plan_id'] = Plan::factory()->pyme()->create(['nombre' => 'pyme2'])->id;

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['empresa.ruc' => 'Ya existe una empresa con este RUC.']);
    }

    public function test_rejects_duplicate_email(): void
    {
        $plan = Plan::factory()->pyme()->create(['nombre' => 'pyme3']);
        $empresa = Empresa::factory()->create();
        \App\Modules\Core\Models\Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'email'      => 'owner@test.com',
        ]);

        $payload = $this->payload(['plan_id' => $plan->id]);
        $payload['empresa']['ruc'] = '20987654321';

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['owner.email' => 'Este email ya tiene una cuenta.']);
    }

    public function test_rejects_ruc_with_wrong_length(): void
    {
        $payload = $this->payload();
        $payload['empresa']['ruc'] = '2012345';

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['empresa.ruc' => 'El RUC debe tener exactamente 11 dígitos numéricos.']);
    }

    public function test_rejects_mismatched_passwords(): void
    {
        $payload = $this->payload();
        $payload['owner']['password_confirmation'] = 'different_password';

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['owner.password' => 'Las contraseñas no coinciden.']);
    }

    public function test_rejects_invalid_plan(): void
    {
        $payload = $this->payload();
        $payload['plan_id'] = '00000000-0000-0000-0000-000000000000';

        $this->postJson('/api/auth/register', $payload)
            ->assertStatus(422);
    }
}
