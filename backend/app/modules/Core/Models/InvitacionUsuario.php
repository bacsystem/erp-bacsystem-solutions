<?php

namespace App\Modules\Core\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitacionUsuario extends BaseModel
{
    protected $table = 'invitaciones_usuario';

    protected $fillable = [
        'empresa_id', 'email', 'rol', 'token', 'invitado_por',
        'expires_at', 'used_at', 'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        parent::booted();
        static::creating(fn ($m) => $m->created_at ??= now());
    }

    public function estaVigente(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'invitado_por');
    }
}
