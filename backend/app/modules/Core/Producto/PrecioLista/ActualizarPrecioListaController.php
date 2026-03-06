<?php

namespace App\Modules\Core\Producto\PrecioLista;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ActualizarPrecioListaController
{
    public function __invoke(ActualizarPrecioListaRequest $request, string $producto, ActualizarPrecioListaService $service): JsonResponse
    {
        $precios = $service->handle($producto, $request->validated()['precios_lista']);
        return ApiResponse::success($precios, 'Precios de lista actualizados.');
    }
}
