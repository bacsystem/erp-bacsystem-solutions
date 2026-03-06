<?php

namespace App\Modules\Core\Producto\Promocion\DesactivarPromocion;

use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DesactivarPromocionService
{
    public function handle(string $productoId, string $promocionId): void
    {
        $producto = Producto::findOrFail($productoId);

        $promocion = $producto->promociones()
            ->where('id', $promocionId)
            ->firstOrFail();

        $promocion->update(['activo' => false]);
    }
}
