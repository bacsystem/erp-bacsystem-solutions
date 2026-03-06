<?php

namespace App\Modules\Core\Producto\PrecioLista;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoPrecioLista;
use Illuminate\Support\Facades\DB;

class ActualizarPrecioListaService
{
    public function handle(string $productoId, array $precios): array
    {
        return DB::transaction(function () use ($productoId, $precios) {
            $producto = Producto::findOrFail($productoId);
            $producto->preciosLista()->delete();

            $result = [];
            foreach ($precios as $pl) {
                $result[] = $producto->preciosLista()->create($pl);
            }

            return $result;
        });
    }
}
