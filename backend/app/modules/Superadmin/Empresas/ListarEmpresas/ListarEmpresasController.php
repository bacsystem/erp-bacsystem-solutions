<?php
namespace App\Modules\Superadmin\Empresas\ListarEmpresas;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ListarEmpresasController {
    public function __invoke(Request $request): JsonResponse {
        return (new ListarEmpresasService())->execute($request);
    }
}
