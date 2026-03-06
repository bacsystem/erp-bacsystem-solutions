<?php

namespace App\Modules\Core\Empresa\UpdateEmpresa;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;

class UpdateEmpresaService
{
    public function execute(array $data): Empresa
    {
        $empresa  = auth()->user()->empresa;
        $anterior = $empresa->only(array_keys($data));

        // Ignorar campos inmutables aunque vengan en el request
        unset($data['ruc'], $data['razon_social'], $data['id']);

        $empresa->update($data);

        AuditLog::registrar('empresa_actualizada', [
            'tabla_afectada'   => 'empresas',
            'registro_id'      => $empresa->id,
            'datos_anteriores' => $anterior,
            'datos_nuevos'     => $data,
        ]);

        return $empresa->fresh();
    }
}
