<?php

namespace App\Modules\Core\Empresa\UploadLogo;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UploadLogoController
{
    public function __invoke(UploadLogoRequest $request, UploadLogoService $service): JsonResponse
    {
        try {
            $url = $service->execute($request->file('logo'));
        } catch (\Exception $e) {
            return ApiResponse::error('Error al subir el archivo. Intenta nuevamente.', [], 500);
        }

        return ApiResponse::success(['logo_url' => $url], 'Logo actualizado');
    }
}
