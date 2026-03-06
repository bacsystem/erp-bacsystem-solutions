<?php

namespace Database\Factories;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InvitacionUsuarioFactory extends Factory
{
    protected $model = InvitacionUsuario::class;

    public function definition(): array
    {
        // Create a minimal owner user for the invitado_por FK
        $plan    = Plan::factory()->create();
        $empresa = Empresa::factory()->create();
        Suscripcion::factory()->create(['empresa_id' => $empresa->id, 'plan_id' => $plan->id]);
        $owner = Usuario::factory()->create(['empresa_id' => $empresa->id, 'rol' => 'owner']);

        return [
            'id'           => (string) Str::uuid(),
            'empresa_id'   => $empresa->id,
            'email'        => $this->faker->unique()->safeEmail(),
            'rol'          => 'empleado',
            'token'        => Str::random(64),
            'invitado_por' => $owner->id,
            'expires_at'   => now()->addHours(48),
            'used_at'      => null,
            'created_at'   => now(),
        ];
    }

    public function expirada(): static
    {
        return $this->state(['expires_at' => now()->subHour()]);
    }

    public function usada(): static
    {
        return $this->state(['used_at' => now()->subHour()]);
    }
}
