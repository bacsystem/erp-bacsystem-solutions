<?php

namespace App\Modules\Core\Categoria\Listar;

use App\Modules\Core\Producto\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;

class ListarCategoriasService
{
    public function handle(): Collection
    {
        // Only root categories; children are loaded recursively via "hijos" relation
        return Categoria::whereNull('categoria_padre_id')
            ->with('hijos')
            ->get();
    }
}
