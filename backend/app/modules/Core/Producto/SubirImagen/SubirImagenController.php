<?php

namespace App\Modules\Core\Producto\SubirImagen;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class SubirImagenController
{
    public function __invoke(SubirImagenRequest $request, string $producto, SubirImagenService $service): JsonResponse
    {
        $imagen = $service->handle($producto, $request->file('imagen'), auth()->user()->empresa_id);

        return ApiResponse::success($imagen, 'Imagen subida exitosamente.', 201);
    }
}
