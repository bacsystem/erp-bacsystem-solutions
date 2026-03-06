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
use App\Modules\Core\Suscripcion\YapeToken\YapeTokenController;
use App\Modules\Core\Usuario\ActivarCuenta\ActivarCuentaController;
use App\Modules\Core\Usuario\ActualizarRol\ActualizarRolController;
use App\Modules\Core\Usuario\DesactivarUsuario\DesactivarUsuarioController;
use App\Modules\Core\Usuario\InviteUsuario\InviteUsuarioController;
use App\Modules\Core\Usuario\ListarUsuarios\ListarUsuariosController;
use App\Modules\Core\Categoria\Crear\CrearCategoriaController;
use App\Modules\Core\Categoria\Listar\ListarCategoriasController;
use App\Modules\Core\Categoria\Actualizar\ActualizarCategoriaController;
use App\Modules\Core\Categoria\Eliminar\EliminarCategoriaController;
use App\Modules\Core\Producto\Crear\CrearProductoController;
use App\Modules\Core\Producto\Listar\ListarProductosController;
use App\Modules\Core\Producto\GetDetalle\GetProductoDetalleController;
use App\Modules\Core\Producto\Actualizar\ActualizarProductoController;
use App\Modules\Core\Producto\Desactivar\DesactivarProductoController;
use App\Modules\Core\Producto\Activar\ActivarProductoController;
use App\Modules\Core\Producto\SubirImagen\SubirImagenController;
use App\Modules\Core\Producto\EliminarImagen\EliminarImagenController;
use App\Modules\Core\Producto\ImportarCSV\ImportarProductosController;
use App\Modules\Core\Producto\ExportarExcel\ExportarExcelController;
use App\Modules\Core\Producto\ExportarPDF\ExportarPDFController;
use App\Modules\Core\Producto\PrecioLista\ActualizarPrecioListaController;
use App\Modules\Core\Producto\Promocion\CrearPromocion\CrearPromocionController;
use App\Modules\Core\Producto\Promocion\DesactivarPromocion\DesactivarPromocionController;
use App\Modules\Core\Producto\GetQR\GetQRController;
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

Route::middleware(['auth:sanctum', 'tenant', 'suscripcion.activa'])->group(function () {

    // Auth
    Route::post('auth/logout', LogoutController::class);

    // Perfil
    Route::get('me', GetProfileController::class);
    Route::put('me', UpdateProfileController::class);

    // Empresa
    Route::get('empresa', GetEmpresaController::class);
    Route::put('empresa', UpdateEmpresaController::class)->middleware('role:owner,admin');
    Route::post('empresa/logo', UploadLogoController::class)->middleware('role:owner,admin');

    // Usuarios
    Route::get('usuarios', ListarUsuariosController::class);
    Route::post('usuarios/invitar', InviteUsuarioController::class)->middleware('role:owner,admin');
    Route::put('usuarios/{usuario}/rol', ActualizarRolController::class)->middleware('role:owner,admin');
    Route::put('usuarios/{usuario}/desactivar', DesactivarUsuarioController::class)->middleware('role:owner,admin');

    // ─── Categorías ───────────────────────────────────────────────
    Route::get('categorias', ListarCategoriasController::class);
    Route::middleware('role:owner,admin')->group(function () {
        Route::post('categorias', CrearCategoriaController::class);
        Route::put('categorias/{categoria}', ActualizarCategoriaController::class);
        Route::delete('categorias/{categoria}', EliminarCategoriaController::class);
    });

    // ─── Productos ────────────────────────────────────────────────
    Route::get('productos/exportar/pdf', ExportarPDFController::class);
    Route::get('productos/exportar', ExportarExcelController::class);
    Route::get('productos/importar/template', [ImportarProductosController::class, 'template']);
    Route::get('productos/{producto}/qr', GetQRController::class);
    Route::get('productos/{producto}', GetProductoDetalleController::class);
    Route::get('productos', ListarProductosController::class);

    Route::middleware('role:owner,admin')->group(function () {
        Route::post('productos', CrearProductoController::class);
        Route::put('productos/{producto}', ActualizarProductoController::class);
        Route::delete('productos/{producto}', DesactivarProductoController::class);
        Route::patch('productos/{producto}/activar', ActivarProductoController::class);
        Route::post('productos/{producto}/imagenes', SubirImagenController::class);
        Route::delete('productos/{producto}/imagenes/{imagen}', EliminarImagenController::class);
        Route::post('productos/importar', ImportarProductosController::class);
        Route::put('productos/{producto}/precios-lista', ActualizarPrecioListaController::class);
        Route::post('productos/{producto}/promociones', CrearPromocionController::class);
        Route::delete('productos/{producto}/promociones/{promocion}', DesactivarPromocionController::class);
    });

    // ─── Suscripción ──────────────────────────────────────────────
    Route::get('suscripcion', GetSuscripcionController::class);
    Route::post('suscripcion/upgrade', UpgradePlanController::class)->middleware('role:owner');
    Route::post('suscripcion/yape-token', YapeTokenController::class)->middleware('role:owner');
    Route::post('suscripcion/downgrade', DowngradePlanController::class)->middleware('role:owner');
});
