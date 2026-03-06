<?php
namespace App\Modules\Superadmin\Empresas\Impersonar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class TerminarImpersonacionController {
    public function __invoke(Request $request, string $empresa): JsonResponse {
        return (new TerminarImpersonacionService())->execute($empresa, $request->user());
    }
}
