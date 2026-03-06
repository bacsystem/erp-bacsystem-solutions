<?php

namespace App\Modules\Core\Models;

use App\Shared\Models\BaseModel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends BaseModel implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    protected $fillable = [
        'empresa_id', 'nombre', 'email', 'password',
        'rol', 'activo', 'last_login',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'activo'     => 'boolean',
        'last_login' => 'datetime',
        'password'   => 'hashed',
    ];

    // Email inmutable post-creación
    public function setEmailAttribute(string $value): void
    {
        if ($this->exists) {
            throw new \LogicException('El email no puede modificarse después del registro.');
        }
        $this->attributes['email'] = $value;
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('rol', 'owner')->where('activo', true);
    }

    public function esOwner(): bool        { return $this->rol === 'owner'; }
    public function esAdmin(): bool        { return $this->rol === 'admin'; }
    public function puedeGestionar(): bool { return in_array($this->rol, ['owner', 'admin']); }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function invitacionesEnviadas(): HasMany
    {
        return $this->hasMany(InvitacionUsuario::class, 'invitado_por');
    }
}
