<?php

namespace Database\Factories;

use App\Modules\Core\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'id'         => (string) Str::uuid(),
            'nombre'     => $this->faker->name(),
            'email'      => $this->faker->unique()->safeEmail(),
            'password'   => Hash::make('password'),
            'rol'        => 'empleado',
            'activo'     => true,
            'last_login' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(['rol' => 'owner']);
    }

    public function admin(): static
    {
        return $this->state(['rol' => 'admin']);
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
