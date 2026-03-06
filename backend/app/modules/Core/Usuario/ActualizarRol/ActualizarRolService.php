<?php

namespace App\Modules\Core\Usuario\ActualizarRol;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Usuario;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ActualizarRolService
{
    public function execute(Usuario $usuario, array $data): Usuario
    {
        $actor  = auth()->user();
        $rolNuevo = $data['rol'];

        // No puede cambiar su propio rol
        if ($actor->id === $usuario->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'No puedes cambiar tu propio rol.',
            ], Response::HTTP_FORBIDDEN));
        }

        // Solo owner puede asignar rol owner
        if ($rolNuevo === 'owner' && $actor->rol !== 'owner') {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Solo el owner puede asignar el rol de owner.',
            ], Response::HTTP_FORBIDDEN));
        }

        $rolAnterior = $usuario->rol;
        $usuario->update(['rol' => $rolNuevo]);

        AuditLog::registrar('rol_actualizado', [
            'datos_anteriores' => ['rol' => $rolAnterior],
            'datos_nuevos'     => ['rol' => $rolNuevo, 'usuario_id' => $usuario->id],
        ]);

        return $usuario->fresh();
    }
}
