<?php
namespace App\Modules\Superadmin\Planes\ListarPlanes;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ListarPlanesController {
    public function __invoke(Request $request): JsonResponse {
        return (new ListarPlanesService())->execute();
    }
}
