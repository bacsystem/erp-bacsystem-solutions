<?php

namespace App\Modules\Core\Producto\Desactivar;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class DesactivarProductoService
{
    public function handle(string $productoId, string $empresaId, ?string $usuarioId): Producto
    {
        return DB::transaction(function () use ($productoId, $empresaId, $usuarioId) {
            $producto = Producto::findOrFail($productoId);

            // Desactivar promociones activas
            $producto->promociones()->where('activo', true)->update(['activo' => false]);

            $producto->update(['activo' => false]);

            AuditLog::registrar('producto.desactivar', [
                'empresa_id'     => $empresaId,
                'usuario_id'     => $usuarioId,
                'tabla_afectada' => 'productos',
                'registro_id'    => $producto->id,
            ]);

            return $producto->fresh();
        });
    }
}
