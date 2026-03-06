<?php

namespace App\Modules\Superadmin\Auth\Login;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginSuperadminService
{
    public function execute(array $data): array
    {
        $superadmin = Superadmin::where('email', $data['email'])->first();

        if (! $superadmin || ! Hash::check($data['password'], $superadmin->password)) {
            throw new AuthenticationException('Credenciales incorrectas.');
        }

        if (! $superadmin->activo) {
            throw new AuthenticationException('Tu cuenta de superadmin está desactivada.');
        }

        $superadmin->timestamps = false;
        $superadmin->update(['last_login' => now()]);
        $superadmin->timestamps = true;

        $superadmin->tokens()->delete();
        $token = $superadmin->createToken('superadmin', ['*'], now()->addHours(4));

        AuditLog::create([
            'empresa_id'    => null,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_login',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        return [
            'access_token' => $token->plainTextToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 4 * 3600,
            'superadmin'   => [
                'id'     => $superadmin->id,
                'nombre' => $superadmin->nombre,
                'email'  => $superadmin->email,
            ],
        ];
    }
}
