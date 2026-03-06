<?php

namespace Tests\Feature\Core\Helpers;

use App\Modules\Core\Producto\Models\Categoria;
use App\Modules\Core\Producto\Models\Producto;

trait ProductoHelper
{
    use AuthHelper;

    protected function crearCategoria(array $attrs = []): Categoria
    {
        [$usuario] = isset($attrs['empresa_id'])
            ? [null]
            : $this->actingAsTenant();

        $empresaId = $attrs['empresa_id'] ?? $usuario?->empresa_id;

        return Categoria::factory()->create(array_merge(
            ['empresa_id' => $empresaId],
            $attrs,
        ));
    }

    protected function crearProducto(array $attrs = []): Producto
    {
        $empresaId = $attrs['empresa_id'] ?? null;

        if (! $empresaId) {
            [$usuario] = $this->actingAsTenant();
            $empresaId = $usuario->empresa_id;
        }

        if (empty($attrs['categoria_id'])) {
            $cat = Categoria::factory()->create(['empresa_id' => $empresaId]);
            $attrs['categoria_id'] = $cat->id;
        }

        return Producto::factory()->create(array_merge(
            ['empresa_id' => $empresaId],
            $attrs,
        ));
    }
}
