<?php

namespace Database\Factories;

use App\Modules\Core\Models\Suscripcion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SuscripcionFactory extends Factory
{
    protected $model = Suscripcion::class;

    public function definition(): array
    {
        return [
            'id'               => (string) Str::uuid(),
            'estado'           => 'trial',
            'fecha_inicio'     => today(),
            'fecha_vencimiento'=> today()->addDays(30),
        ];
    }

    public function trial(): static
    {
        return $this->state([
            'estado'            => 'trial',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
    }

    public function activa(): static
    {
        return $this->state([
            'estado'              => 'activa',
            'fecha_vencimiento'   => today()->addMonth(),
            'fecha_proximo_cobro' => today()->addMonth(),
        ]);
    }

    public function vencida(): static
    {
        return $this->state([
            'estado'            => 'vencida',
            'fecha_vencimiento' => today()->subDay(),
        ]);
    }

    public function cancelada(): static
    {
        return $this->state([
            'estado'             => 'cancelada',
            'fecha_vencimiento'  => today()->subDays(8),
            'fecha_cancelacion'  => today(),
        ]);
    }
}
