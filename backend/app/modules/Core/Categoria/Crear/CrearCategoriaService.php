<?php

namespace App\Modules\Core\Categoria\Crear;

use App\Modules\Core\Producto\Models\Categoria;

class CrearCategoriaService
{
    public function handle(array $data, string $empresaId): Categoria
    {
        return Categoria::create([
            'empresa_id'         => $empresaId,
            'nombre'             => $data['nombre'],
            'descripcion'        => $data['descripcion'] ?? null,
            'categoria_padre_id' => $data['categoria_padre_id'] ?? null,
        ]);
    }
}
