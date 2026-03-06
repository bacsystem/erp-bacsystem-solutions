<?php

namespace App\Modules\Superadmin\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Superadmin extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;

    protected $table = 'superadmins';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'nombre', 'email', 'password', 'activo', 'last_login'];

    protected $hidden = ['password'];

    protected $casts = [
        'activo'     => 'boolean',
        'last_login' => 'datetime',
    ];

    protected static function booting(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }
}
