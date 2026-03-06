<?php

namespace App\Modules\Core\Categoria\Actualizar;

use App\Modules\Core\Producto\Models\Categoria;

class ActualizarCategoriaService
{
    public function handle(string $categoriaId, array $data): Categoria
    {
        $categoria = Categoria::findOrFail($categoriaId);
        $categoria->update($data);
        return $categoria->fresh();
    }
}
