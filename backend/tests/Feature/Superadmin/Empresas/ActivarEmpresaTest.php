<?php

namespace Tests\Feature\Superadmin\Empresas;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Superadmin\Helpers\SuperadminHelper;
use Tests\TestCase;

class ActivarEmpresaTest extends TestCase
{
    use RefreshDatabase;
    use SuperadminHelper;

    private function createEmpresaSuspendida(): array
    {
        $plan = Plan::factory()->create(['nombre' => 'pyme', 'nombre_display' => 'PYME', 'precio_mensual' => 100]);
        $empresa = Empresa::factory()->create();
        $suscripcion = Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'cancelada',
            'fecha_vencimiento' => today()->subDays(1),
        ]);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner', 'activo' => true]);
        return [$empresa, $suscripcion, $owner];
    }

    public function test_activates_suspended_empresa(): void
    {
        [$superadmin, $token] = $this->actingAsSuperadmin();
        [$empresa, $suscripcion, $owner] = $this->createEmpresaSuspendida();

        Mail::fake();

        $response = $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/activar");

        $response->assertOk();

        $suscripcion->refresh();
        $this->assertEquals('activa', $suscripcion->estado);

        $this->assertDatabaseHas('audit_logs', [
            'empresa_id'    => $empresa->id,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_activate',
        ]);
    }

    public function test_sends_email_to_owner_on_activation(): void
    {
        [, $token] = $this->actingAsSuperadmin();
        [$empresa, $suscripcion, $owner] = $this->createEmpresaSuspendida();

        Mail::fake();

        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/activar")
            ->assertOk();

        Mail::assertSent(\App\Shared\Mail\ReactivacionMail::class, function ($mail) use ($owner) {
            return $mail->hasTo($owner->email);
        });
    }

    public function test_returns_422_if_empresa_already_active(): void
    {
        [, $token] = $this->actingAsSuperadmin();

        $plan = Plan::factory()->create(['nombre' => 'basic', 'nombre_display' => 'Basic', 'precio_mensual' => 50]);
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        $this->withToken($token)
            ->postJson("/superadmin/api/empresas/{$empresa->id}/activar")
            ->assertStatus(422);
    }
}
