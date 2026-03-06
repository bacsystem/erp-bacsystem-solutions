<?php

namespace Tests\Feature\Core;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuscripcionActivaMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUsuario(array $suscripcionData): array
    {
        $plan    = Plan::factory()->create(['modulos' => ['productos']]);
        $empresa = Empresa::factory()->create();

        Suscripcion::factory()->create(array_merge([
            'empresa_id' => $empresa->id,
            'plan_id'    => $plan->id,
        ], $suscripcionData));

        $usuario = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        $token = $usuario->createToken('test')->plainTextToken;

        return [$usuario, $token, $empresa->id];
    }

    private function payload(string $empresaId): array
    {
        $cat = \App\Modules\Core\Producto\Models\Categoria::create([
            'id'         => Str::uuid(),
            'empresa_id' => $empresaId,
            'nombre'     => 'Cat Test',
        ]);

        return [
            'nombre'                  => 'Prod Test',
            'sku'                     => 'SKU-' . Str::random(4),
            'tipo'                    => 'simple',
            'categoria_id'            => $cat->id,
            'unidad_medida_principal' => 'NIU',
            'precio_venta'            => 99.90,
            'igv_tipo'                => 'gravado',
        ];
    }

    public function test_activa_permite_crear_producto(): void
    {
        [, $token, $empresaId] = $this->makeUsuario([
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->addMonth(),
        ]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload($empresaId));

        $res->assertStatus(201);
    }

    public function test_vencida_por_estado_bloquea_escritura(): void
    {
        [, $token, $empresaId] = $this->makeUsuario([
            'estado'            => 'vencida',
            'fecha_vencimiento' => today()->subDay(),
        ]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload($empresaId));

        $res->assertStatus(402);
    }

    public function test_activa_con_fecha_vencida_ayer_bloquea_escritura(): void
    {
        // Simula cuenta con estado=activa pero el cobro no procesó aún
        [, $token, $empresaId] = $this->makeUsuario([
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->subDay(),
        ]);

        $res = $this->withToken($token)->postJson('/api/productos', $this->payload($empresaId));

        $res->assertStatus(402);
    }

    public function test_activa_con_fecha_vencida_ayer_permite_lectura(): void
    {
        [, $token] = $this->makeUsuario([
            'estado'            => 'activa',
            'fecha_vencimiento' => today()->subDay(),
        ]);

        $res = $this->withToken($token)->getJson('/api/productos');

        $res->assertStatus(200);
    }

    public function test_cancelada_bloquea_todo(): void
    {
        [, $token] = $this->makeUsuario([
            'estado'            => 'cancelada',
            'fecha_vencimiento' => today()->subDays(10),
            'fecha_cancelacion' => today()->subDays(3),
        ]);

        $res = $this->withToken($token)->getJson('/api/productos');

        $res->assertStatus(402);
    }

    public function test_cancelada_permite_get_empresa(): void
    {
        [, $token] = $this->makeUsuario([
            'estado'            => 'cancelada',
            'fecha_vencimiento' => today()->subDays(10),
            'fecha_cancelacion' => today()->subDays(3),
        ]);

        $res = $this->withToken($token)->getJson('/api/empresa');

        $res->assertStatus(200);
    }

    public function test_vencida_permite_upgrade(): void
    {
        [, $token] = $this->makeUsuario([
            'estado'            => 'vencida',
            'fecha_vencimiento' => today()->subDay(),
        ]);

        // Solo verificamos que no retorne 402 (puede retornar otro error por falta de datos Culqi)
        $res = $this->withToken($token)->postJson('/api/suscripcion/upgrade', []);

        $this->assertNotEquals(402, $res->status());
    }
}
