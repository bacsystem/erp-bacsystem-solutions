<?php

namespace App\Modules\Core\Me;

use App\Modules\Core\Models\AuditLog;
use App\Shared\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UpdateProfileController
{
    public function __invoke(UpdateProfileRequest $request): JsonResponse
    {
        $usuario = auth()->user();
        $data    = $request->validated();

        if (isset($data['nombre'])) {
            $usuario->update(['nombre' => $data['nombre']]);
        }

        if (isset($data['password'])) {
            if (! Hash::check($data['password_actual'], $usuario->password)) {
                throw ValidationException::withMessages([
                    'password_actual' => ['La contraseña actual es incorrecta.'],
                ]);
            }

            $usuario->update(['password' => Hash::make($data['password'])]);
            $usuario->tokens()->delete();

            AuditLog::registrar('password_changed', []);
        }

        return ApiResponse::success([
            'id'     => $usuario->id,
            'nombre' => $usuario->nombre,
            'email'  => $usuario->getRawOriginal('email'),
            'rol'    => $usuario->rol,
        ], 'Perfil actualizado correctamente.');
    }
}
