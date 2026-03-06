<?php

namespace App\Modules\Core\Models;

use App\Shared\Models\BaseModel;

class AuditLog extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'empresa_id', 'usuario_id', 'accion', 'tabla_afectada',
        'registro_id', 'datos_anteriores', 'datos_nuevos', 'ip', 'created_at',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
        'created_at'       => 'datetime',
    ];

    public static function registrar(string $accion, array $extra = []): void
    {
        static::create(array_merge([
            'empresa_id' => auth()->user()?->empresa_id,
            'usuario_id' => auth()->id(),
            'accion'     => $accion,
            'ip'         => request()->ip(),
            'created_at' => now(),
        ], $extra));
    }
}
