<?php

namespace App\Modules\Core\Usuario\ListarUsuarios;

use App\Modules\Core\Models\InvitacionUsuario;
use App\Modules\Core\Models\Usuario;

class ListarUsuariosService
{
    public function execute(): array
    {
        $empresa = auth()->user()->empresa;

        $usuarios = Usuario::where('empresa_id', $empresa->id)
            ->get(['id', 'nombre', 'email', 'rol', 'activo', 'last_login'])
            ->map(fn ($u) => [
                'id'         => $u->id,
                'nombre'     => $u->nombre,
                'email'      => $u->email,
                'rol'        => $u->rol,
                'activo'     => $u->activo,
                'last_login' => $u->last_login,
            ]);

        $invitaciones = InvitacionUsuario::where('empresa_id', $empresa->id)
            ->pendientes()
            ->get(['id', 'email', 'rol', 'expires_at'])
            ->map(fn ($i) => [
                'id'         => $i->id,
                'email'      => $i->email,
                'rol'        => $i->rol,
                'expires_at' => $i->expires_at->toDateTimeString(),
            ]);

        return [
            'usuarios'     => $usuarios,
            'invitaciones' => $invitaciones,
        ];
    }
}
