<?php

namespace App\Modules\Core\Auth\Logout;

use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Usuario;

class LogoutService
{
    public function execute(Usuario $usuario): void
    {
        $usuario->tokens()->delete();

        AuditLog::registrar('logout_all');
    }
}
