<?php

namespace App\Modules\Core\Producto\EliminarImagen;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoImagen;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class EliminarImagenService
{
    public function handle(string $productoId, string $imagenId): void
    {
        $producto = Producto::findOrFail($productoId);

        $imagen = $producto->imagenes()->where('id', $imagenId)->firstOrFail();

        // Eliminar del storage si tiene path
        if ($imagen->path_r2) {
            Storage::disk('r2')->delete($imagen->path_r2);
        }

        $imagen->delete();

        // Reordenar imágenes restantes
        $producto->imagenes()->orderBy('orden')->get()->each(function ($img, $index) {
            $img->update(['orden' => $index + 1]);
        });
    }
}
