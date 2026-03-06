<?php

namespace App\Modules\Core\Producto\GetDetalle;

use App\Modules\Core\Producto\Models\Producto;

class GetProductoDetalleService
{
    public function handle(string $productoId): Producto
    {
        return Producto::with([
            'categoria',
            'imagenes',
            'preciosLista',
            'unidades',
            'componentes',
            'promocionActiva',
            'historialPrecios' => fn($q) => $q->limit(10),
        ])->findOrFail($productoId);
    }
}
