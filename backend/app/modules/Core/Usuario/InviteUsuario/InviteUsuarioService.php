<?php

namespace App\Modules\Core\Usuario\InviteUsuario;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\InvitacionUsuarioMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteUsuarioService
{
    public function execute(array $data): InvitacionUsuario
    {
        $usuario     = auth()->user();
        $empresa     = $usuario->empresa;
        $suscripcion = $empresa->suscripcionActiva;

        // Verificar límite de usuarios del plan
        $maxUsuarios = $suscripcion->plan->max_usuarios;
        if ($maxUsuarios !== null) {
            $usuariosActivos = Usuario::where('empresa_id', $empresa->id)->where('activo', true)->count();
            $invitacionesPendientes = InvitacionUsuario::where('empresa_id', $empresa->id)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->count();

            if (($usuariosActivos + $invitacionesPendientes) >= $maxUsuarios) {
                throw ValidationException::withMessages([
                    'email' => ["Has alcanzado el límite de {$maxUsuarios} usuarios de tu plan."],
                ]);
            }
        }

        // Verificar que el email no sea ya un usuario activo de esta empresa
        $existeUsuario = Usuario::where('empresa_id', $empresa->id)
            ->where('email', $data['email'])
            ->where('activo', true)
            ->exists();

        if ($existeUsuario) {
            throw ValidationException::withMessages([
                'email' => ['Este email ya pertenece a un usuario activo de tu empresa.'],
            ]);
        }

        // Verificar que no haya una invitación pendiente para este email
        $invitacionPendiente = InvitacionUsuario::where('empresa_id', $empresa->id)
            ->where('email', $data['email'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($invitacionPendiente) {
            throw ValidationException::withMessages([
                'email' => ['Ya existe una invitación pendiente para este email.'],
            ]);
        }

        $invitacion = InvitacionUsuario::create([
            'empresa_id'   => $empresa->id,
            'email'        => $data['email'],
            'rol'          => $data['rol'],
            'token'        => Str::random(64),
            'invitado_por' => $usuario->id,
            'expires_at'   => now()->addHours(48),
            'created_at'   => now(),
        ]);

        Mail::queue(new InvitacionUsuarioMail($invitacion, $empresa));

        AuditLog::registrar('usuario_invitado', [
            'datos_nuevos' => ['email' => $data['email'], 'rol' => $data['rol']],
        ]);

        return $invitacion;
    }
}
