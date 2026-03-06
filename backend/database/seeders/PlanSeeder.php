<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'nombre'         => 'starter',
                'nombre_display' => 'Starter',
                'precio_mensual' => 59.00,
                'max_usuarios'   => 3,
                'modulos'        => ['facturacion', 'clientes', 'productos'],
                'activo'         => true,
            ],
            [
                'nombre'         => 'pyme',
                'nombre_display' => 'PYME',
                'precio_mensual' => 129.00,
                'max_usuarios'   => 15,
                'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia'],
                'activo'         => true,
            ],
            [
                'nombre'         => 'enterprise',
                'nombre_display' => 'Enterprise',
                'precio_mensual' => 299.00,
                'max_usuarios'   => null,
                'modulos'        => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia', 'rrhh'],
                'activo'         => true,
            ],
        ];

        foreach ($planes as $data) {
            Plan::updateOrCreate(['nombre' => $data['nombre']], $data);
        }
    }
}
