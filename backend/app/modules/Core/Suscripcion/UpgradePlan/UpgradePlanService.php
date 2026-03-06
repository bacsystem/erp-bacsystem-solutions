<?php

namespace App\Modules\Core\Suscripcion\UpgradePlan;

use App\Modules\Core\Jobs\UpgradePlanJob;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Plan;
use App\Shared\Contracts\PaymentGateway;
use App\Shared\Exceptions\PaymentException;
use App\Shared\Mail\UpgradePlanMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpgradePlanService
{
    public function __construct(private PaymentGateway $gateway) {}

    public function execute(array $data): array
    {
        $usuario     = auth()->user();
        $empresa     = $usuario->empresa;
        $suscripcion = $empresa->suscripcionActiva;
        $planNuevo   = Plan::findOrFail($data['plan_id']);

        if (! $planNuevo->esUpgradeDe($suscripcion->plan)) {
            throw ValidationException::withMessages([
                'plan_id' => ['El plan seleccionado no es superior al actual.'],
            ]);
        }

        // Verificar 409: upgrade en proceso (audit_log en últimas 2h sin resultado final)
        $upgradeEnCola = AuditLog::where('empresa_id', $empresa->id)
            ->where('accion', 'plan_upgrade_queued')
            ->where('created_at', '>=', now()->subHours(2))
            ->whereNotExists(function ($q) use ($empresa) {
                $q->from('audit_logs as al2')
                    ->whereColumn('al2.empresa_id', 'audit_logs.empresa_id')
                    ->whereIn('al2.accion', ['plan_upgrade', 'plan_upgrade_failed'])
                    ->whereColumn('al2.created_at', '>', 'audit_logs.created_at');
            })
            ->exists();

        if ($upgradeEnCola) {
            return ['_status' => 409];
        }

        $montoProrrateo = $suscripcion->calcularMontoProrrateo($planNuevo);

        try {
            $charge = $this->gateway->charge([
                'amount'        => (int) ($montoProrrateo * 100),
                'currency_code' => 'PEN',
                'source_id'     => $data['culqi_token'],
                'email'         => $usuario->email,
                'metadata'      => [
                    'empresa_id' => $usuario->empresa_id,
                    'plan'       => $planNuevo->nombre,
                ],
            ]);

            return $this->aplicarUpgrade($suscripcion, $planNuevo, $charge, $usuario);
        } catch (PaymentException $e) {
            throw ValidationException::withMessages(['culqi_token' => [$e->getMessage()]]);
        } catch (\Exception $e) {
            // Timeout / connectivity error → encolar Job con reintentos
            $jobId = (string) Str::uuid();
            UpgradePlanJob::dispatch($suscripcion->id, $planNuevo->id, $data['culqi_token'], $usuario->id, $jobId);

            AuditLog::registrar('plan_upgrade_queued', [
                'datos_nuevos' => ['plan' => $planNuevo->nombre, 'job_id' => $jobId],
            ]);

            return ['_status' => 202, 'job_id' => $jobId, 'estado' => 'procesando'];
        }
    }

    private function aplicarUpgrade($sus, Plan $plan, array $charge, $usuario): array
    {
        return DB::transaction(function () use ($sus, $plan, $charge, $usuario) {
            $sus->update([
                'plan_id'             => $plan->id,
                'estado'              => 'activa',
                'fecha_vencimiento'   => today()->addMonth(),
                'fecha_proximo_cobro' => today()->addMonth(),
                'downgrade_plan_id'   => null,
                'card_last4'          => $charge['source']['number_last4'] ?? null,
                'card_brand'          => $charge['source']['brand'] ?? null,
            ]);

            $usuario->tokens()->delete();
            $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
            $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

            AuditLog::registrar('plan_upgrade', ['datos_nuevos' => ['plan' => $plan->nombre]]);
            Mail::to($usuario->email)->queue(new UpgradePlanMail($usuario, $plan));

            $montoDisplay = isset($charge['amount']) ? number_format($charge['amount'] / 100, 2) : '0.00';

            return [
                '_status'       => 200,
                'access_token'  => $access->plainTextToken,
                'refresh_token' => $refresh->plainTextToken,
                'token_type'    => 'Bearer',
                'expires_in'    => 900,
                'suscripcion'   => [
                    'plan'    => $plan->nombre,
                    'estado'  => 'activa',
                    'modulos' => $plan->modulos,
                    'fecha_vencimiento' => today()->addMonth()->toDateString(),
                ],
                'cobro' => [
                    'monto'       => $montoDisplay,
                    'descripcion' => "Upgrade a {$plan->nombre_display}",
                ],
            ];
        });
    }
}
