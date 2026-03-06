<?php
namespace App\Modules\Superadmin\Planes\Descuento;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class DesactivarDescuentoController {
    public function __invoke(Request $request, string $empresa, string $descuento): JsonResponse {
        return (new DesactivarDescuentoService())->execute($empresa, $descuento, $request->user());
    }
}
