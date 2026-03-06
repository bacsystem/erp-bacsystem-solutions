<?php

namespace App\Modules\Core\Suscripcion\UpgradePlan;

use App\Shared\Exceptions\PaymentException;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpgradePlanController
{
    public function __invoke(UpgradePlanRequest $request, UpgradePlanService $service): JsonResponse
    {
        try {
            $result = $service->execute($request->validated());
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), [], 402);
        }

        $status = $result['_status'];
        unset($result['_status']);

        if ($status === 409) {
            return ApiResponse::error(
                'Ya hay un pago en proceso para tu cuenta. Espera la confirmación por email antes de intentar nuevamente.',
                [],
                409
            );
        }

        if ($status === 202) {
            return response()->json([
                'success' => true,
                'message' => 'Estamos procesando tu pago. Te notificaremos por email cuando se confirme.',
                'data'    => $result,
            ], 200); // 200 para que el frontend pueda manejar el estado "procesando"
        }

        $refreshToken = $result['refresh_token'];
        unset($result['refresh_token']);

        return ApiResponse::success($result, '¡Plan actualizado! Ya tienes acceso a los nuevos módulos')
            ->cookie('refresh_token', $refreshToken, 43200, '/', null, app()->isProduction(), true);
    }
}
