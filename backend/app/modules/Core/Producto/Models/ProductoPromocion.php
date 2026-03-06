<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoPromocion extends Model
{
    protected $table = 'producto_promociones';

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
