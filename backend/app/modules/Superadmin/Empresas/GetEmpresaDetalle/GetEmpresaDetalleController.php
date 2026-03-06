<?php
namespace App\Modules\Superadmin\Empresas\GetEmpresaDetalle;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class GetEmpresaDetalleController {
    public function __invoke(Request $request, string $empresa): JsonResponse {
        return (new GetEmpresaDetalleService())->execute($empresa);
    }
}
