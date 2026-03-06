<?php

namespace App\Modules\Core\Auth\RefreshToken;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenService
{
    public function execute(Request $request): array
    {
        $cookieToken = $request->cookie('refresh_token');

        if (! $cookieToken) {
            throw new AuthenticationException('Sesión expirada. Inicia sesión nuevamente.');
        }

        [$id, $token] = explode('|', $cookieToken, 2);
        $pat = PersonalAccessToken::find($id);

        if (! $pat || ! hash_equals($pat->token, hash('sha256', $token))) {
            throw new AuthenticationException('Sesión expirada. Inicia sesión nuevamente.');
        }

        if ($pat->expires_at && $pat->expires_at->isPast()) {
            $pat->delete();
            throw new AuthenticationException('Sesión expirada. Inicia sesión nuevamente.');
        }

        if ($pat->name !== 'refresh') {
            throw new AuthenticationException('Token inválido.');
        }

        $usuario = $pat->tokenable;

        // Rotar: borrar refresh anterior y access tokens, emitir nuevos
        $pat->delete();
        $usuario->tokens()->where('name', 'access')->delete();

        $newAccess  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
        $newRefresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

        return [
            'access_token'  => $newAccess->plainTextToken,
            'refresh_token' => $newRefresh->plainTextToken,
            'token_type'    => 'Bearer',
            'expires_in'    => 900,
        ];
    }
}
