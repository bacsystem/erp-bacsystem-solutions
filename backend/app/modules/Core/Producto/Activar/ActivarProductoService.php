<?php

namespace App\Modules\Core\Producto\Activar;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class ActivarProductoService
{
    public function handle(string $productoId, string $empresaId, ?string $usuarioId): Producto
    {
        return DB::transaction(function () use ($productoId, $empresaId, $usuarioId) {
            $producto = Producto::findOrFail($productoId);

            $producto->update(['activo' => true]);

            AuditLog::registrar('producto.activar', [
                'empresa_id'     => $empresaId,
                'usuario_id'     => $usuarioId,
                'tabla_afectada' => 'productos',
                'registro_id'    => $producto->id,
            ]);

            return $producto->fresh();
        });
    }
}
