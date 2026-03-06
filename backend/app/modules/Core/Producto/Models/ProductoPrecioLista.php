<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoPrecioLista extends Model
{
    protected $table = 'producto_precios_lista';

    protected $fillable = ['producto_id', 'lista', 'nombre_lista', 'precio'];

    protected $casts = ['precio' => 'float'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
