<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductoPromocion extends Model
{
    protected $table = 'producto_promociones';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    protected $fillable = [
        'producto_id', 'nombre', 'tipo', 'valor',
        'fecha_inicio', 'fecha_fin', 'activo',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'valor'       => 'float',
        'fecha_inicio'=> 'date',
        'fecha_fin'   => 'date',
    ];

    public function scopeActiva(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now()->toDateString());
            });
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
