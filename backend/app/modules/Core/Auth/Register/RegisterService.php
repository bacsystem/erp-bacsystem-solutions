<?php

namespace App\Modules\Core\Auth\Register;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Shared\Mail\BienvenidaMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RegisterService
{
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::findOrFail($data['plan_id']);

            // 1. Crear empresa — RLS permissive para INSERT (setting vacío)
            $empresa = new Empresa($data['empresa']);
            $empresa->save();

            // Inyectar empresa_id en el contexto PostgreSQL para que
            // el RLS permita los INSERTs de suscripción, usuario y audit_log
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SET LOCAL app.empresa_id = '{$empresa->id}'");
            }

            // 2. Crear suscripción trial 30 días
            $suscripcion = Suscripcion::create([
                'empresa_id'        => $empresa->id,
                'plan_id'           => $plan->id,
                'estado'            => 'trial',
                'fecha_inicio'      => today(),
                'fecha_vencimiento' => today()->addDays(30),
            ]);

            // 3. Crear usuario owner
            $owner = new Usuario([
                'empresa_id' => $empresa->id,
                'nombre'     => $data['owner']['nombre'],
                'email'      => $data['owner']['email'],
                'password'   => $data['owner']['password'],
                'rol'        => 'owner',
                'activo'     => true,
            ]);
            $owner->save();

            // 4. Emitir tokens Sanctum
            [$accessToken, $refreshToken] = $this->emitirTokens($owner);

            // 5. Audit log
            AuditLog::create([
                'empresa_id' => $empresa->id,
                'usuario_id' => $owner->id,
                'accion'     => 'register',
                'ip'         => request()->ip(),
                'created_at' => now(),
            ]);

            // 6. Email bienvenida (queued)
            Mail::to($owner->email)->queue(new BienvenidaMail($owner, $empresa, $plan));

            return [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type'    => 'Bearer',
                'expires_in'    => 900,
                'user'          => $this->buildUserPayload($owner, $empresa, $suscripcion, $plan),
            ];
        });
    }

    private function emitirTokens(Usuario $usuario): array
    {
        $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
        $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

        return [$access->plainTextToken, $refresh->plainTextToken];
    }

    private function buildUserPayload(Usuario $u, Empresa $e, Suscripcion $s, Plan $p): array
    {
        return [
            'id'      => $u->id,
            'nombre'  => $u->nombre,
            'email'   => $u->email,
            'rol'     => $u->rol,
            'empresa' => [
                'id'               => $e->id,
                'nombre_comercial' => $e->nombre_comercial,
                'ruc'              => $e->getRawOriginal('ruc'),
                'logo_url'         => $e->logo_url,
            ],
            'suscripcion' => [
                'plan'              => $p->nombre,
                'estado'            => $s->estado,
                'fecha_vencimiento' => $s->fecha_vencimiento->toDateString(),
                'modulos'           => $p->modulos,
            ],
        ];
    }
}
