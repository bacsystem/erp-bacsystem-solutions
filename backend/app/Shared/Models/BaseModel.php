<?php

namespace App\Shared\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    use HasFactory;
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());

        static::addGlobalScope('empresa', function (Builder $query) {
            if (auth()->check() && $query->getModel()->isFillable('empresa_id')) {
                $query->where(
                    $query->getModel()->getTable() . '.empresa_id',
                    auth()->user()->empresa_id
                );
            }
        });
    }
}
