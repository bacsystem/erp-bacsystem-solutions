<?php
namespace App\Modules\Superadmin\Empresas\Impersonar;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ImpersonarController {
    public function __invoke(Request $request, string $empresa): JsonResponse {
        return (new ImpersonarService())->execute($empresa, $request->user());
    }
}
