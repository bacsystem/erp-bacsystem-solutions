<?php

namespace App\Modules\Superadmin\Auth\Logout;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Superadmin\Models\Superadmin;

class LogoutSuperadminService
{
    public function execute(Superadmin $superadmin): void
    {
        AuditLog::create([
            'empresa_id'    => null,
            'usuario_id'    => null,
            'superadmin_id' => $superadmin->id,
            'accion'        => 'superadmin_logout',
            'ip'            => request()->ip(),
            'created_at'    => now(),
        ]);

        $superadmin->tokens()->delete();
    }
}
