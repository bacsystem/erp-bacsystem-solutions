<?php
namespace App\Modules\Superadmin\Planes\Descuento;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
class AplicarDescuentoController {
    public function __invoke(AplicarDescuentoRequest $request, string $empresa): JsonResponse {
        return (new AplicarDescuentoService())->execute($empresa, $request->validated(), $request->user());
    }
}
