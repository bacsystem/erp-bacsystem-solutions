<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PrecioHistorial extends Model
{
    protected $table = 'precio_historial';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    protected $fillable = ['producto_id', 'precio_anterior', 'precio_nuevo', 'usuario_id'];

    protected $casts = [
        'precio_anterior' => 'float',
        'precio_nuevo'    => 'float',
        'created_at'      => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
