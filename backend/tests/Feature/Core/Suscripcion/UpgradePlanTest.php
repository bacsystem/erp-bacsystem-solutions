<?php

namespace Tests\Feature\Core\Suscripcion;

use App\Modules\Core\Models\Plan;
use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;
use App\Shared\Mail\UpgradePlanMail;
use App\Modules\Core\Jobs\UpgradePlanJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Core\Helpers\AuthHelper;
use Tests\TestCase;

class UpgradePlanTest extends TestCase
{
    use RefreshDatabase, AuthHelper;

    public function test_upgrades_plan_and_returns_new_tokens_when_culqi_succeeds(): void
    {
        Mail::fake();
        $this->mock(PaymentGateway::class, function ($mock) {
            $mock->shouldReceive('charge')->once()->andReturn([
                'object' => 'charge', 'id' => 'chr_test', 'amount' => 17000,
                'source' => ['number_last4' => '1111', 'brand' => 'Visa'],
            ]);
        });

        [$owner, , , , $token] = $this->actingAsOwner();
        $enterprise = Plan::factory()->enterprise()->create(['nombre' => 'enterprise_t']);

        $res = $this->withToken($token)->postJson('/api/suscripcion/upgrade', [
            'plan_id'     => $enterprise->id,
            'culqi_token' => 'tkn_test_123',
        ]);

        $res->assertOk()
            ->assertJsonPath('data.suscripcion.plan', 'enterprise_t')
            ->assertJsonStructure(['data' => ['access_token']]);

        $this->assertDatabaseHas('suscripciones', ['plan_id' => $enterprise->id, 'estado' => 'activa']);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'plan_upgrade']);
        Mail::assertQueued(UpgradePlanMail::class);
    }

    public function test_queues_job_on_culqi_timeout(): void
    {
        Queue::fake();
        $this->mock(PaymentGateway::class, function ($mock) {
            $mock->shouldReceive('charge')->once()->andThrow(new \Exception('Connection timeout'));
        });

        [$owner, , , , $token] = $this->actingAsOwner();
        $enterprise = Plan::factory()->enterprise()->create(['nombre' => 'enterprise_q']);

        $this->withToken($token)->postJson('/api/suscripcion/upgrade', [
            'plan_id'     => $enterprise->id,
            'culqi_token' => 'tkn_test_123',
        ])->assertStatus(200)
          ->assertJsonPath('data.estado', 'procesando');

        Queue::assertPushed(UpgradePlanJob::class);
        $this->assertDatabaseHas('audit_logs', ['accion' => 'plan_upgrade_queued']);
    }

    public function test_returns_409_when_upgrade_already_in_progress(): void
    {
        [$owner, $empresa, , , $token] = $this->actingAsOwner();
        $enterprise = Plan::factory()->enterprise()->create(['nombre' => 'enterprise_409']);

        // Simular un upgrade_queued reciente
        \App\Modules\Core\Models\AuditLog::create([
            'empresa_id' => $empresa->id,
            'usuario_id' => $owner->id,
            'accion'     => 'plan_upgrade_queued',
            'ip'         => '127.0.0.1',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->withToken($token)->postJson('/api/suscripcion/upgrade', [
            'plan_id'     => $enterprise->id,
            'culqi_token' => 'tkn_test_123',
        ])->assertStatus(409);
    }

    public function test_rejects_downgrade_attempt(): void
    {
        [$owner, , , , $token] = $this->actingAsOwner();
        $starter = Plan::factory()->starter()->create(['nombre' => 'starter_d']);

        $this->withToken($token)->postJson('/api/suscripcion/upgrade', [
            'plan_id'     => $starter->id,
            'culqi_token' => 'tkn_test',
        ])->assertStatus(422);
    }
}
