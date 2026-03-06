<?php
namespace App\Modules\Superadmin\Empresas\SuspenderEmpresa;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class SuspenderEmpresaController {
    public function __invoke(Request $request, string $empresa): JsonResponse {
        return (new SuspenderEmpresaService())->execute($empresa, $request->user());
    }
}
