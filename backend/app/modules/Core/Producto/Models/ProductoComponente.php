<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoComponente extends Model
{
    protected $table = 'producto_componentes';
    public $timestamps = false;

    protected $fillable = ['producto_id', 'componente_id', 'cantidad'];

    protected $casts = ['cantidad' => 'float'];

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function componente(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'componente_id');
    }
}
