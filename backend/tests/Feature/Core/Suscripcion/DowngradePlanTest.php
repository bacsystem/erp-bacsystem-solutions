<?php

namespace Tests\Feature\Core\Suscripcion;

use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class DowngradePlanTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_owner_puede_programar_downgrade(): void
    {
        // actingAsOwner crea suscripción con plan pyme (15 usuarios, modulos completos)
        [$owner, $empresa, $suscripcion, $planPyme, $token] = $this->actingAsOwner();

        $planStarter = Plan::factory()->starter()->create(['nombre' => 'starter_dg']);

        $response = $this->withToken($token)->postJson('/api/suscripcion/downgrade', [
            'plan_id' => $planStarter->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.plan_nuevo', 'starter_dg')
            ->assertJsonStructure([
                'data' => [
                    'plan_actual',
                    'plan_nuevo',
                    'efectivo_desde',
                    'modulos_que_perdera',
                    'nuevo_max_usuarios',
                ],
            ]);

        $this->assertDatabaseHas('suscripciones', [
            'id'               => $suscripcion->id,
            'downgrade_plan_id' => $planStarter->id,
        ]);

        $this->assertDatabaseHas('audit_logs', ['accion' => 'plan_downgrade']);
    }

    public function test_rechaza_mismo_plan(): void
    {
        [$owner, , $suscripcion, $planActual, $token] = $this->actingAsOwner();

        $this->withToken($token)->postJson('/api/suscripcion/downgrade', [
            'plan_id' => $planActual->id,
        ])->assertStatus(422)
          ->assertJsonValidationErrors('plan_id');
    }

    public function test_rechaza_si_no_es_downgrade(): void
    {
        // actingAsOwner tiene plan pyme — enterprise es upgrade, no downgrade
        [$owner, , , , $token] = $this->actingAsOwner();

        $planEnterprise = Plan::factory()->enterprise()->create(['nombre' => 'enterprise_nd']);

        $this->withToken($token)->postJson('/api/suscripcion/downgrade', [
            'plan_id' => $planEnterprise->id,
        ])->assertStatus(422)
          ->assertJsonValidationErrors('plan_id');
    }

    public function test_rol_no_owner_retorna_403(): void
    {
        [$owner, $empresa, , , ] = $this->actingAsOwner();

        $admin = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'admin',
            'activo'     => true,
        ]);
        $tokenAdmin = $this->loginAs($admin);

        $planStarter = Plan::factory()->starter()->create(['nombre' => 'starter_403']);

        $this->withToken($tokenAdmin)->postJson('/api/suscripcion/downgrade', [
            'plan_id' => $planStarter->id,
        ])->assertStatus(403);
    }

    public function test_sin_auth_retorna_401(): void
    {
        $plan = Plan::factory()->starter()->create();

        $this->postJson('/api/suscripcion/downgrade', [
            'plan_id' => $plan->id,
        ])->assertStatus(401);
    }
}
