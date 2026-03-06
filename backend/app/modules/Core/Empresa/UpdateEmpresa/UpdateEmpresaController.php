<?php

namespace App\Modules\Core\Empresa\UpdateEmpresa;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class UpdateEmpresaController
{
    public function __invoke(UpdateEmpresaRequest $request, UpdateEmpresaService $service): JsonResponse
    {
        $empresa = $service->execute($request->validated());

        return ApiResponse::success([
            'id'                => $empresa->id,
            'ruc'               => $empresa->getRawOriginal('ruc'),
            'razon_social'      => $empresa->razon_social,
            'nombre_comercial'  => $empresa->nombre_comercial,
            'direccion'         => $empresa->direccion,
            'ubigeo'            => $empresa->ubigeo,
            'logo_url'          => $empresa->logo_url,
            'regimen_tributario'=> $empresa->regimen_tributario,
        ], 'Datos de la empresa actualizados');
    }
}
