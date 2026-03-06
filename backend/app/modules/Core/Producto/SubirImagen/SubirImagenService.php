<?php

namespace App\Modules\Core\Producto\SubirImagen;

use App\Modules\Core\Producto\Models\Producto;
use App\Modules\Core\Producto\Models\ProductoImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubirImagenService
{
    private const MAX_IMAGENES = 5;

    public function handle(string $productoId, UploadedFile $file, string $empresaId): ProductoImagen
    {
        $producto = Producto::findOrFail($productoId);

        $countActual = $producto->imagenes()->count();
        if ($countActual >= self::MAX_IMAGENES) {
            throw ValidationException::withMessages([
                'imagen' => ["Un producto puede tener máximo " . self::MAX_IMAGENES . " imágenes."],
            ]);
        }

        $extension = $file->getClientOriginalExtension();
        $path      = "empresas/{$empresaId}/productos/{$productoId}/" . uniqid() . ".{$extension}";

        Storage::disk('r2')->put($path, $file->getContent(), 'public');

        $url = Storage::disk('r2')->url($path);

        return $producto->imagenes()->create([
            'url'     => $url,
            'path_r2' => $path,
            'orden'   => $countActual + 1,
        ]);
    }
}
