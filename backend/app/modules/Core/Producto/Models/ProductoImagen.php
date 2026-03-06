<?php

namespace App\Modules\Core\Producto\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagenes';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }

    protected $fillable = ['producto_id', 'url', 'path_r2', 'orden'];

    protected $casts = ['orden' => 'integer'];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
