<?php

namespace App\Modules\Core\Producto\Crear;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoComponente;
use App\Modules\Core\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrearProductoService
{
    public function handle(array $data, string $empresaId, ?string $usuarioId): Producto
    {
        // Validar componentes para tipo=compuesto
        if (($data['tipo'] ?? 'simple') === 'compuesto') {
            $componentes = $data['componentes'] ?? [];
            if (empty($componentes)) {
                throw ValidationException::withMessages([
                    'componentes' => ['Un producto compuesto requiere al menos un componente.'],
                ]);
            }
        }

        return DB::transaction(function () use ($data, $empresaId, $usuarioId) {
            $producto = Producto::create([
                'empresa_id'             => $empresaId,
                'categoria_id'           => $data['categoria_id'],
                'nombre'                 => $data['nombre'],
                'descripcion'            => $data['descripcion'] ?? null,
                'sku'                    => $data['sku'],
                'codigo_barras'          => $data['codigo_barras'] ?? null,
                'tipo'                   => $data['tipo'] ?? 'simple',
                'unidad_medida_principal'=> $data['unidad_medida_principal'],
                'precio_compra'          => $data['precio_compra'] ?? null,
                'precio_venta'           => $data['precio_venta'],
                'igv_tipo'               => $data['igv_tipo'] ?? 'gravado',
            ]);

            // Precios de lista
            foreach ($data['precios_lista'] ?? [] as $pl) {
                $producto->preciosLista()->create($pl);
            }

            // Unidades adicionales
            foreach ($data['unidades'] ?? [] as $u) {
                $producto->unidades()->create($u);
            }

            // Componentes (kit)
            if ($producto->tipo === 'compuesto') {
                foreach ($data['componentes'] ?? [] as $c) {
                    if ($c['componente_id'] === $producto->id) {
                        throw ValidationException::withMessages([
                            'componentes' => ['Un producto no puede ser componente de sí mismo.'],
                        ]);
                    }
                    // Detección de ciclos
                    if ($this->creaCirculo($producto->id, $c['componente_id'], $empresaId)) {
                        throw ValidationException::withMessages([
                            'componentes' => ['Se detectó una referencia circular entre componentes.'],
                        ]);
                    }
                    $producto->componentes()->create($c);
                }
            }

            AuditLog::registrar('producto.crear', [
                'empresa_id'     => $empresaId,
                'usuario_id'     => $usuarioId,
                'tabla_afectada' => 'productos',
                'registro_id'    => $producto->id,
                'datos_nuevos'   => ['nombre' => $producto->nombre, 'sku' => $producto->sku],
            ]);

            return $producto->load(['categoria', 'imagenes', 'preciosLista', 'unidades', 'componentes']);
        });
    }

    /** Verifica si agregar $componenteId al kit $kitId crea un ciclo. */
    private function creaCirculo(string $kitId, string $componenteId, string $empresaId): bool
    {
        // Si el componente es ya un kit que contiene al kit actual, hay ciclo
        $visitados = [];
        return $this->esAncestro($kitId, $componenteId, $visitados);
    }

    private function esAncestro(string $kitId, string $candidatoId, array &$visitados): bool
    {
        if (in_array($candidatoId, $visitados)) {
            return false;
        }
        $visitados[] = $candidatoId;

        $subComponentes = ProductoComponente::where('producto_id', $candidatoId)
            ->pluck('componente_id');

        foreach ($subComponentes as $sub) {
            if ($sub === $kitId) {
                return true;
            }
            if ($this->esAncestro($kitId, $sub, $visitados)) {
                return true;
            }
        }

        return false;
    }
}
