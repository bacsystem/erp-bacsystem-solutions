<?php

namespace App\Modules\Core\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empresa extends BaseModel
{
    protected $fillable = [
        'ruc', 'razon_social', 'nombre_comercial',
        'direccion', 'ubigeo', 'logo_url', 'regimen_tributario',
    ];

    // RUC inmutable — lanzar excepción si se intenta modificar post-creación
    public function setRucAttribute(string $value): void
    {
        if ($this->exists) {
            throw new \LogicException('El RUC no puede modificarse después del registro.');
        }
        $this->attributes['ruc'] = $value;
    }

    public function suscripcionActiva(): HasOne
    {
        return $this->hasOne(Suscripcion::class)
            ->whereIn('estado', ['trial', 'activa', 'vencida', 'cancelada'])
            ->latest();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
