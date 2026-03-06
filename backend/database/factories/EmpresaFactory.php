<?php

namespace Database\Factories;

use App\Modules\Core\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'id'                => (string) Str::uuid(),
            'ruc'               => (string) $this->faker->unique()->numerify('2#########'),
            'razon_social'      => $this->faker->company() . ' SAC',
            'nombre_comercial'  => $this->faker->company(),
            'direccion'         => $this->faker->address(),
            'ubigeo'            => '150101',
            'logo_url'          => null,
            'regimen_tributario'=> $this->faker->randomElement(['RER', 'RG', 'RMT']),
        ];
    }
}
