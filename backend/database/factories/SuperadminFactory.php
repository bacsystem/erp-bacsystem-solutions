<?php

namespace Database\Factories;

use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class SuperadminFactory extends Factory
{
    protected $model = Superadmin::class;

    public function definition(): array
    {
        return [
            'nombre'   => $this->faker->name(),
            'email'    => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'activo'   => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }
}
