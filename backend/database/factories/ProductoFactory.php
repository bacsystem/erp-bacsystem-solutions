<?php

namespace Database\Factories;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoPromocion;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'empresa_id'             => null,
            'categoria_id'           => null,
            'nombre'                 => ucwords($this->faker->words(3, true)),
            'descripcion'            => $this->faker->optional()->sentence(),
            'sku'                    => strtoupper($this->faker->unique()->bothify('???-###')),
            'codigo_barras'          => $this->faker->optional()->ean13(),
            'tipo'                   => 'simple',
            'unidad_medida_principal'=> 'NIU',
            'precio_compra'          => $this->faker->optional()->randomFloat(2, 10, 500),
            'precio_venta'           => $this->faker->randomFloat(2, 15, 1000),
            'igv_tipo'               => 'gravado',
            'activo'                 => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(['activo' => false]);
    }

    public function compuesto(): static
    {
        return $this->state(['tipo' => 'compuesto']);
    }

    public function servicio(): static
    {
        return $this->state(['tipo' => 'servicio', 'unidad_medida_principal' => 'ZZ']);
    }

    public function conPromocion(): static
    {
        return $this->afterCreating(function (Producto $producto) {
            ProductoPromocion::create([
                'producto_id'  => $producto->id,
                'nombre'       => 'Promo Test',
                'tipo'         => 'porcentaje',
                'valor'        => 10,
                'fecha_inicio' => now()->toDateString(),
                'fecha_fin'    => now()->addDays(30)->toDateString(),
                'activo'       => true,
            ]);
        });
    }
}
