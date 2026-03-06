<?php
namespace App\Modules\Superadmin\Empresas\ActivarEmpresa;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ActivarEmpresaController {
    public function __invoke(Request $request, string $empresa): JsonResponse {
        return (new ActivarEmpresaService())->execute($empresa, $request->user());
    }
}
