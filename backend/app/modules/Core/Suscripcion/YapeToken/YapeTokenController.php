<?php

namespace App\Modules\Core\Suscripcion\YapeToken;

use App\Modules\Core\Models\Plan;
use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class YapeTokenController
{
    public function __invoke(YapeTokenRequest $request, PaymentGateway $gateway): JsonResponse
    {
        $usuario     = auth()->user();
        $suscripcion = $usuario->empresa->suscripcionActiva;
        $planNuevo   = Plan::findOrFail($request->plan_id);

        $montoProrrateo = $suscripcion->calcularMontoProrrateo($planNuevo);
        $amountCents    = (int) round($montoProrrateo * 100);

        try {
            $tokenId = $gateway->createYapeToken(
                $request->number_phone,
                $request->otp,
                $amountCents,
            );
        } catch (PaymentException $e) {
            return ApiResponse::error($e->getMessage(), [], 402);
        }

        return ApiResponse::success(['token' => $tokenId]);
    }
}
