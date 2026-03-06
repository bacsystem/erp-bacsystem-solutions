<?php

namespace App\Modules\Core\Empresa\GetEmpresa;

use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class GetEmpresaController
{
    public function __invoke(): JsonResponse
    {
        $empresa = auth()->user()->empresa;

        return ApiResponse::success([
            'id'                => $empresa->id,
            'ruc'               => $empresa->getRawOriginal('ruc'),
            'razon_social'      => $empresa->razon_social,
            'nombre_comercial'  => $empresa->nombre_comercial,
            'direccion'         => $empresa->direccion,
            'ubigeo'            => $empresa->ubigeo,
            'logo_url'          => $empresa->logo_url,
            'regimen_tributario'=> $empresa->regimen_tributario,
            'created_at'        => $empresa->created_at,
        ]);
    }
}
