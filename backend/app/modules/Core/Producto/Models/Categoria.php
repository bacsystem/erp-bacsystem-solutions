<?php

namespace App\Modules\Core\Producto\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends BaseModel
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'categoria_padre_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id')
            ->with('hijos');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
