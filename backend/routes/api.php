<?php

use App\Modules\Core\Auth\Login\LoginController;
use App\Modules\Core\Auth\Logout\LogoutController;
use App\Modules\Core\Auth\Planes\GetPlanesController;
use App\Modules\Core\Auth\RecuperarPassword\RecuperarPasswordController;
use App\Modules\Core\Auth\RecuperarPassword\ResetPasswordController;
use App\Modules\Core\Auth\RefreshToken\RefreshTokenController;
use App\Modules\Core\Auth\Register\RegisterController;
use App\Modules\Core\Empresa\GetEmpresa\GetEmpresaController;
use App\Modules\Core\Empresa\UpdateEmpresa\UpdateEmpresaController;
use App\Modules\Core\Empresa\UploadLogo\UploadLogoController;
use App\Modules\Core\Me\GetProfileController;
use App\Modules\Core\Me\UpdateProfileController;
use App\Modules\Core\Suscripcion\DowngradePlan\DowngradePlanController;
use App\Modules\Core\Suscripcion\GetSuscripcion\GetSuscripcionController;
use App\Modules\Core\Suscripcion\UpgradePlan\UpgradePlanController;
use App\Modules\Core\Usuario\ActivarCuenta\ActivarCuentaController;
use App\Modules\Core\Usuario\ActualizarRol\ActualizarRolController;
use App\Modules\Core\Usuario\DesactivarUsuario\DesactivarUsuarioController;
use App\Modules\Core\Usuario\InviteUsuario\InviteUsuarioController;
use App\Modules\Core\Usuario\ListarUsuarios\ListarUsuariosController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────
// Rutas públicas — sin autenticación
// ─────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('register', RegisterController::class)
        ->middleware('throttle:register');

    Route::post('login', LoginController::class)
        ->middleware('throttle:login');

    Route::post('refresh', RefreshTokenController::class);

    Route::post('recuperar-password', RecuperarPasswordController::class);

    Route::post('reset-password', ResetPasswordController::class);
});

Route::get('planes', GetPlanesController::class);

Route::post('auth/activar-cuenta', ActivarCuentaController::class);

// ─────────────────────────────────────────────────────────────────
// Rutas protegidas — requieren autenticación
// ─────────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::post('auth/logout', LogoutController::class);

    // Perfil
    Route::get('me', GetProfileController::class);
    Route::put('me', UpdateProfileController::class);

    // Empresa — suscripción activa requerida solo para escritura
    // GET /api/empresa NO lleva suscripcion.activa (disponible en estado cancelada)
    Route::get('empresa', GetEmpresaController::class);

    Route::middleware('suscripcion.activa')->group(function () {
        Route::put('empresa', UpdateEmpresaController::class)
            ->middleware('role:owner,admin');
        Route::post('empresa/logo', UploadLogoController::class)
            ->middleware('role:owner,admin');

        // Usuarios
        Route::get('usuarios', ListarUsuariosController::class);
        Route::post('usuarios/invitar', InviteUsuarioController::class)
            ->middleware('role:owner,admin');
        Route::put('usuarios/{usuario}/rol', ActualizarRolController::class)
            ->middleware('role:owner,admin');
        Route::put('usuarios/{usuario}/desactivar', DesactivarUsuarioController::class)
            ->middleware('role:owner,admin');
    });

    // Suscripción — solo owner
    // upgrade siempre disponible (incluso en estado vencida)
    Route::get('suscripcion', GetSuscripcionController::class);
    Route::post('suscripcion/upgrade', UpgradePlanController::class)
        ->middleware('role:owner');
    Route::post('suscripcion/downgrade', DowngradePlanController::class)
        ->middleware(['role:owner', 'suscripcion.activa']);
});
