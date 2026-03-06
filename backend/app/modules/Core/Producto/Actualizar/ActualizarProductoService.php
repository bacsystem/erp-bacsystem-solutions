<?php

namespace App\Modules\Core\Producto\Actualizar;

use App\Modules\Core\Producto\Models\PrecioHistorial;
use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class ActualizarProductoService
{
    public function handle(string $productoId, array $data, string $empresaId, ?string $usuarioId): Producto
    {
        return DB::transaction(function () use ($productoId, $data, $empresaId, $usuarioId) {
            $producto = Producto::findOrFail($productoId);

            // Si precio_venta cambia, registrar en historial
            if (isset($data['precio_venta']) && (float) $data['precio_venta'] !== (float) $producto->precio_venta) {
                PrecioHistorial::create([
                    'producto_id'    => $producto->id,
                    'precio_anterior'=> $producto->precio_venta,
                    'precio_nuevo'   => $data['precio_venta'],
                    'usuario_id'     => $usuarioId,
                ]);
            }

            // Actualizar precios_lista si vienen
            if (isset($data['precios_lista'])) {
                $producto->preciosLista()->delete();
                foreach ($data['precios_lista'] as $pl) {
                    $producto->preciosLista()->create($pl);
                }
            }

            $updateFields = array_filter(
                $data,
                fn($key) => ! in_array($key, ['precios_lista', 'componentes']),
                ARRAY_FILTER_USE_KEY
            );

            $producto->update($updateFields);

            AuditLog::registrar('producto.actualizar', [
                'empresa_id'     => $empresaId,
                'usuario_id'     => $usuarioId,
                'tabla_afectada' => 'productos',
                'registro_id'    => $producto->id,
                'datos_nuevos'   => $updateFields,
            ]);

            return $producto->fresh(['categoria', 'imagenes', 'preciosLista', 'unidades', 'componentes']);
        });
    }
}
