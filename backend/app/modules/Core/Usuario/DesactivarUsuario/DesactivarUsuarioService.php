<?php

namespace App\Modules\Core\Usuario\DesactivarUsuario;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Usuario;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class DesactivarUsuarioService
{
    public function execute(Usuario $usuario): void
    {
        $actor = auth()->user();

        // Ya inactivo
        if (! $usuario->activo) {
            throw ValidationException::withMessages([
                'usuario' => ['Este usuario ya está inactivo.'],
            ]);
        }

        // Verificar que no sea el único owner activo (tiene prioridad sobre auto-desactivación)
        if ($usuario->rol === 'owner') {
            $ownersActivos = Usuario::where('empresa_id', $usuario->empresa_id)
                ->where('rol', 'owner')
                ->where('activo', true)
                ->count();

            if ($ownersActivos <= 1) {
                throw ValidationException::withMessages([
                    'usuario' => ['No puedes desactivar al único owner activo de la empresa.'],
                ]);
            }
        }

        // No puede auto-desactivarse
        if ($actor->id === $usuario->id) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'No puedes desactivarte a ti mismo.',
            ], Response::HTTP_FORBIDDEN));
        }

        $usuario->update(['activo' => false]);
        $usuario->tokens()->delete();

        AuditLog::registrar('usuario_desactivado', [
            'datos_nuevos' => ['usuario_id' => $usuario->id, 'email' => $usuario->email],
        ]);
    }
}
