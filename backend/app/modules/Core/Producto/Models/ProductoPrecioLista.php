<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductoPrecioLista extends Model
{
    protected $table = 'producto_precios_lista';
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    protected $fillable = ['producto_id', 'lista', 'nombre_lista', 'precio'];

    protected $casts = ['precio' => 'float'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
