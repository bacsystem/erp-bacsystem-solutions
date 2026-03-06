<?php

namespace App\Modules\Core\Categoria\Eliminar;

use App\Modules\Core\Producto\Models\Categoria;
use Illuminate\Validation\ValidationException;

class EliminarCategoriaService
{
    public function handle(string $categoriaId): void
    {
        $categoria = Categoria::findOrFail($categoriaId);

        if ($categoria->productos()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => ['No se puede eliminar una categoría que tiene productos asignados.'],
            ]);
        }

        if ($categoria->hijos()->exists()) {
            throw ValidationException::withMessages([
                'categoria' => ['No se puede eliminar una categoría que tiene subcategorías.'],
            ]);
        }

        $categoria->delete();
    }
}
