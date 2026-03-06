<?php

namespace App\Modules\Core\Auth\RecuperarPassword;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\PasswordResetToken;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\RecuperarPasswordMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RecuperarPasswordService
{
    public function solicitarReset(string $email): void
    {
        // Siempre retorna éxito — no confirmar si el email existe
        $usuario = Usuario::withoutGlobalScope('empresa')
            ->where('email', $email)
            ->first();

        if (! $usuario) {
            return; // silencioso
        }

        // Invalidar tokens anteriores para este email
        PasswordResetToken::where('email', $email)->delete();

        $plainToken = Str::random(64);

        PasswordResetToken::create([
            'email'      => $email,
            'token'      => hash('sha256', $plainToken),
            'expires_at' => now()->addMinutes(60),
            'created_at' => now(),
        ]);

        Mail::to($email)->queue(new RecuperarPasswordMail($usuario, $plainToken));
    }

    public function resetPassword(array $data): void
    {
        $record = PasswordResetToken::where('email', $data['email'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $record || ! hash_equals($record->token, hash('sha256', $data['token']))) {
            throw ValidationException::withMessages([
                'token' => ['Este link no es válido o ha expirado.'],
            ]);
        }

        $usuario = Usuario::withoutGlobalScope('empresa')
            ->where('email', $data['email'])
            ->firstOrFail();

        DB::transaction(function () use ($usuario, $data, $record) {
            $usuario->update(['password' => $data['password']]);
            $record->update(['used_at' => now()]);
            $usuario->tokens()->delete();

            AuditLog::create([
                'empresa_id' => $usuario->empresa_id,
                'usuario_id' => $usuario->id,
                'accion'     => 'password_changed',
                'ip'         => request()->ip(),
                'created_at' => now(),
            ]);
        });
    }
}
