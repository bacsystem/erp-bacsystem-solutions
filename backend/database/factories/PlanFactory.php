<?php

namespace Database\Factories;

use App\Modules\Core\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'id'             => (string) Str::uuid(),
            'nombre'         => $this->faker->unique()->randomElement(['starter', 'pyme', 'enterprise']),
            'nombre_display' => $this->faker->word(),
            'precio_mensual' => $this->faker->randomFloat(2, 29, 299),
            'max_usuarios'   => $this->faker->randomElement([3, 15, null]),
            'modulos'        => ['facturacion', 'clientes', 'productos'],
            'activo'         => true,
        ];
    }

    public function pyme(): static
    {
        return $this->state([
            'nombre'         => 'pyme',
            'nombre_display' => 'PYME',
            'precio_mensual' => 129.00,
            'max_usuarios'   => 15,
            'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia'],
        ]);
    }

    public function starter(): static
    {
        return $this->state([
            'nombre'         => 'starter',
            'nombre_display' => 'Starter',
            'precio_mensual' => 59.00,
            'max_usuarios'   => 3,
            'modulos'        => ['facturacion', 'clientes', 'productos'],
        ]);
    }

    public function enterprise(): static
    {
        return $this->state([
            'nombre'         => 'enterprise',
            'nombre_display' => 'Enterprise',
            'precio_mensual' => 299.00,
            'max_usuarios'   => null,
            'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia', 'rrhh'],
        ]);
    }
}
