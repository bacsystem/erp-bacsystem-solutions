<?php

namespace App\Modules\Core\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends BaseModel
{
    protected $table = 'suscripciones';

    protected $fillable = [
        'empresa_id', 'plan_id', 'downgrade_plan_id', 'estado',
        'fecha_inicio', 'fecha_vencimiento', 'fecha_proximo_cobro',
        'fecha_cancelacion', 'culqi_subscription_id',
        'culqi_customer_id', 'culqi_card_id', 'card_last4', 'card_brand',
    ];

    protected $casts = [
        'fecha_inicio'        => 'date',
        'fecha_vencimiento'   => 'date',
        'fecha_proximo_cobro' => 'date',
        'fecha_cancelacion'   => 'date',
    ];

    public function esTrial(): bool     { return $this->estado === 'trial'; }
    public function esActiva(): bool    { return $this->estado === 'activa'; }
    public function esVencida(): bool   { return $this->estado === 'vencida'; }
    public function esCancelada(): bool { return $this->estado === 'cancelada'; }

    public function permiteEscritura(): bool
    {
        return in_array($this->estado, ['trial', 'activa']);
    }

    public function calcularMontoProrrateo(Plan $planNuevo): float
    {
        $diasRestantes   = now()->diffInDays($this->fecha_vencimiento, false);
        $diasRestantes   = max(0, $diasRestantes);
        $diferenciaPrecio = (float) $planNuevo->precio_mensual - (float) $this->plan->precio_mensual;

        return round(($diferenciaPrecio / 30) * $diasRestantes, 2);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function downgradePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'downgrade_plan_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
