<?php

use App\Modules\Superadmin\Auth\Login\LoginSuperadminController;
use App\Modules\Superadmin\Auth\Logout\LogoutSuperadminController;
use App\Modules\Superadmin\Dashboard\DashboardController;
use App\Modules\Superadmin\Empresas\ActivarEmpresa\ActivarEmpresaController;
use App\Modules\Superadmin\Empresas\GetEmpresaDetalle\GetEmpresaDetalleController;
use App\Modules\Superadmin\Empresas\Impersonar\ImpersonarController;
use App\Modules\Superadmin\Empresas\Impersonar\TerminarImpersonacionController;
use App\Modules\Superadmin\Empresas\ListarEmpresas\ListarEmpresasController;
use App\Modules\Superadmin\Empresas\SuspenderEmpresa\SuspenderEmpresaController;
use App\Modules\Superadmin\Logs\ExportLogsCSVController;
use App\Modules\Superadmin\Logs\LogsGlobalesController;
use App\Modules\Superadmin\Logs\ResumenLogsController;
use App\Modules\Superadmin\Planes\Descuento\AplicarDescuentoController;
use App\Modules\Superadmin\Planes\Descuento\DesactivarDescuentoController;
use App\Modules\Superadmin\Planes\ListarPlanes\ListarPlanesController;
use App\Modules\Superadmin\Planes\UpdatePlan\UpdatePlanController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('auth/login', LoginSuperadminController::class)
    ->middleware('throttle:superadmin-login');

// Rutas protegidas
Route::middleware(['auth:sanctum', 'superadmin'])->group(function () {
    Route::post('auth/logout', LogoutSuperadminController::class);
    Route::get('dashboard', DashboardController::class);

    // Empresas
    Route::get('empresas', ListarEmpresasController::class);
    Route::get('empresas/{empresa}', GetEmpresaDetalleController::class);
    Route::post('empresas/{empresa}/suspender', SuspenderEmpresaController::class);
    Route::post('empresas/{empresa}/activar', ActivarEmpresaController::class);
    Route::post('empresas/{empresa}/impersonar', ImpersonarController::class);
    Route::delete('empresas/{empresa}/impersonar', TerminarImpersonacionController::class);
    Route::post('empresas/{empresa}/descuento', AplicarDescuentoController::class);
    Route::delete('empresas/{empresa}/descuento/{descuento}', DesactivarDescuentoController::class);

    // Planes
    Route::get('planes', ListarPlanesController::class);
    Route::put('planes/{plan}', UpdatePlanController::class);

    // Logs
    Route::get('logs', LogsGlobalesController::class);
    Route::get('logs/export', ExportLogsCSVController::class);
    Route::get('logs/resumen', ResumenLogsController::class);
});
