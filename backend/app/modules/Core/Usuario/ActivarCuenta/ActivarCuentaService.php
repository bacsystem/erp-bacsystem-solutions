<?php

namespace App\Modules\Core\Usuario\ActivarCuenta;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ActivarCuentaService
{
    public function execute(array $data): array
    {
        $invitacion = InvitacionUsuario::withoutGlobalScope('empresa')
            ->where('token', $data['token'])
            ->first();

        if (! $invitacion) {
            throw ValidationException::withMessages([
                'token' => ['El enlace de activación no es válido.'],
            ]);
        }

        if (! is_null($invitacion->used_at)) {
            throw ValidationException::withMessages([
                'token' => ['Este enlace de activación ya fue utilizado.'],
            ]);
        }

        if ($invitacion->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['El enlace de activación ha expirado.'],
            ]);
        }

        return DB::transaction(function () use ($invitacion, $data) {
            $usuario = Usuario::create([
                'empresa_id' => $invitacion->empresa_id,
                'nombre'     => $data['nombre'],
                'email'      => $invitacion->email,
                'password'   => Hash::make($data['password']),
                'rol'        => $invitacion->rol,
                'activo'     => true,
            ]);

            $invitacion->update(['used_at' => now()]);

            $accessToken  = $usuario->createToken('access', ['*'], now()->addMinutes(15))->plainTextToken;
            $refreshToken = $usuario->createToken('refresh', ['*'], now()->addDays(30))->plainTextToken;

            AuditLog::registrar('usuario_activado', [
                'empresa_id'   => $usuario->empresa_id,
                'usuario_id'   => $usuario->id,
                'datos_nuevos' => ['email' => $usuario->email, 'rol' => $usuario->rol],
            ]);

            return [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'user'          => [
                    'id'    => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'rol'   => $usuario->rol,
                ],
            ];
        });
    }
}
