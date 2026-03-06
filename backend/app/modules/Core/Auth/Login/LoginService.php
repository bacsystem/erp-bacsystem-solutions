<?php

namespace App\Modules\Core\Auth\Login;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function execute(array $data): array
    {
        $usuario = Usuario::withoutGlobalScope('empresa')
            ->where('email', $data['email'])
            ->first();

        if (! $usuario || ! Hash::check($data['password'], $usuario->password)) {
            AuditLog::create([
                'empresa_id' => $usuario?->empresa_id,   // null si el email no existe
                'usuario_id' => $usuario?->id,
                'accion'     => 'login_failed',
                'ip'         => request()->ip(),
                'created_at' => now(),
            ]);
            throw new AuthenticationException('Credenciales incorrectas.');
        }

        if (! $usuario->activo) {
            throw new AuthenticationException(
                'Tu cuenta ha sido desactivada. Contacta al administrador de tu empresa.'
            );
        }

        // Actualizar last_login
        $usuario->timestamps = false;
        $usuario->update(['last_login' => now()]);
        $usuario->timestamps = true;

        // Limpiar access tokens anteriores y emitir nuevos
        $usuario->tokens()->where('name', 'access')->delete();
        $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
        $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

        AuditLog::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'accion'     => 'login',
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);

        $empresa     = $usuario->empresa;
        $suscripcion = $empresa->suscripcionActiva;
        $plan        = $suscripcion->plan;

        return [
            'access_token'  => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'token_type'    => 'Bearer',
            'expires_in'    => 900,
            'user'          => $this->buildUserPayload($usuario, $empresa, $suscripcion, $plan),
        ];
    }

    private function buildUserPayload(Usuario $u, Empresa $e, Suscripcion $s, $p): array
    {
        $suscripcionData = [
            'plan'   => $p->nombre,
            'estado' => $s->estado,
            'modulos'=> $s->esCancelada() ? [] : $p->modulos,
        ];

        if ($s->esCancelada()) {
            $suscripcionData['fecha_cancelacion'] = $s->fecha_cancelacion?->toDateString();
            $suscripcionData['redirect']          = '/configuracion/plan';
        } else {
            $suscripcionData['fecha_vencimiento'] = $s->fecha_vencimiento->toDateString();
        }

        return [
            'id'      => $u->id,
            'nombre'  => $u->nombre,
            'email'   => $u->email,
            'rol'     => $u->rol,
            'empresa' => [
                'id'               => $e->id,
                'razon_social'     => $e->razon_social,
                'nombre_comercial' => $e->nombre_comercial,
                'ruc'              => $e->getRawOriginal('ruc'),
                'logo_url'         => $e->logo_url,
            ],
            'suscripcion' => $suscripcionData,
        ];
    }
}
