<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nombre', 'nombre_display', 'precio_mensual',
        'max_usuarios', 'modulos', 'activo',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'max_usuarios'   => 'integer',
        'modulos'        => 'array',
        'activo'         => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('precio_mensual');
    }

    public function esUpgradeDe(Plan $actual): bool
    {
        return $this->precio_mensual > $actual->precio_mensual;
    }

    public function esDowngradeDe(Plan $actual): bool
    {
        return $this->precio_mensual < $actual->precio_mensual;
    }

    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'plan_id');
    }
}
