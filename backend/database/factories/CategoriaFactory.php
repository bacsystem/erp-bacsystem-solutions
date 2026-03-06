<?php

namespace Database\Factories;

use App\Modules\Core\Producto\Models\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaFactory extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        return [
            'empresa_id'         => null,
            'nombre'             => ucfirst($this->faker->unique()->words(2, true)),
            'descripcion'        => $this->faker->optional()->sentence(),
            'categoria_padre_id' => null,
            'activo'             => true,
        ];
    }

    public function conSubcategoria(): static
    {
        return $this->afterCreating(function (Categoria $categoria) {
            Categoria::factory()->create([
                'empresa_id'         => $categoria->empresa_id,
                'categoria_padre_id' => $categoria->id,
            ]);
        });
    }

    public function inactiva(): static
    {
        return $this->state(['activo' => false]);
    }
}
