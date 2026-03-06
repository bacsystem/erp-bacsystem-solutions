<?php

namespace App\Modules\Core\Producto\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends BaseModel
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'nombre',
        'descripcion',
        'sku',
        'codigo_barras',
        'tipo',
        'unidad_medida_principal',
        'precio_compra',
        'precio_venta',
        'igv_tipo',
        'activo',
    ];

    protected $attributes = [
        'activo'   => true,
        'tipo'     => 'simple',
        'igv_tipo' => 'gravado',
    ];

    protected $casts = [
        'activo'        => 'boolean',
        'precio_compra' => 'float',
        'precio_venta'  => 'float',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function preciosLista(): HasMany
    {
        return $this->hasMany(ProductoPrecioLista::class, 'producto_id');
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(ProductoPromocion::class, 'producto_id');
    }

    public function promocionActiva(): HasMany
    {
        return $this->hasMany(ProductoPromocion::class, 'producto_id')
            ->where('activo', true)
            ->where('fecha_inicio', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now()->toDateString());
            });
    }

    public function unidades(): HasMany
    {
        return $this->hasMany(ProductoUnidad::class, 'producto_id');
    }

    public function componentes(): HasMany
    {
        return $this->hasMany(ProductoComponente::class, 'producto_id')
            ->with('componente');
    }

    public function historialPrecios(): HasMany
    {
        return $this->hasMany(PrecioHistorial::class, 'producto_id')
            ->orderByDesc('created_at');
    }
}
