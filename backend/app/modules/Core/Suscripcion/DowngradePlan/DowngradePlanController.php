<?php

namespace App\Modules\Core\Suscripcion\DowngradePlan;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class DowngradePlanController
{
    public function __invoke(DowngradePlanRequest $request, DowngradePlanService $service): JsonResponse
    {
        $result = $service->execute($request->validated());

        return ApiResponse::success(
            $result,
            "Cambio de plan programado. Tus módulos actuales estarán disponibles hasta el {$result['efectivo_desde']}"
        );
    }
}
