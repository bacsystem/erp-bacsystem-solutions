<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductoUnidad extends Model
{
    protected $table = 'producto_unidades';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    protected $fillable = ['producto_id', 'unidad_medida', 'factor_conversion', 'precio_venta'];

    protected $casts = [
        'factor_conversion' => 'float',
        'precio_venta'      => 'float',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
