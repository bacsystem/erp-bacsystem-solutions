<?php

namespace App\Modules\Core\Producto\Promocion\CrearPromocion;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoPromocion;
use Illuminate\Support\Facades\DB;

class CrearPromocionService
{
    public function handle(string $productoId, array $data): ProductoPromocion
    {
        return DB::transaction(function () use ($productoId, $data) {
            $producto = Producto::findOrFail($productoId);

            // Desactivar promociones anteriores activas
            $producto->promociones()->where('activo', true)->update(['activo' => false]);

            return $producto->promociones()->create(array_merge($data, ['activo' => true]));
        });
    }
}
