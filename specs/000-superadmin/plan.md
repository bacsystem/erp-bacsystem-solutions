# Plan Técnico: Módulo 0 — Superadmin OperaAI

**Branch**: `000-superadmin` | **Date**: 2026-03-06 | **Spec**: [spec.md](./spec.md)
**Principio rector**: Separación total. El superadmin NUNCA usa middleware de tenant. Los tenants NUNCA pueden acceder a rutas superadmin.

---

## 1. Separación de rutas

| Sistema    | Archivo de rutas         | Prefijo URL          | Middleware base              |
|------------|--------------------------|----------------------|------------------------------|
| Tenant     | `routes/api.php`         | `/api`               | `auth:sanctum`, `tenant`     |
| Superadmin | `routes/superadmin.php`  | `/superadmin/api`    | `auth:sanctum`, `superadmin` |

```php
// bootstrap/app.php — agregar ruta superadmin
->withRouting(
    web:        __DIR__.'/../routes/web.php',
    api:        __DIR__.'/../routes/api.php',
    commands:   __DIR__.'/../routes/console.php',
    health:     '/up',
    then: function () {
        Route::middleware('api')
            ->prefix('superadmin/api')
            ->group(base_path('routes/superadmin.php'));
    },
)
```

---

## 2. Modelo Superadmin

```php
namespace App\Modules\Superadmin\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class Superadmin extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'superadmins';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'nombre', 'email', 'password', 'activo', 'last_login'];
    protected $hidden   = ['password'];
    protected $casts    = ['activo' => 'boolean', 'last_login' => 'datetime'];

    protected static function booting(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }
}
```

**Por qué NO extiende `BaseModel`**:
- `BaseModel` tiene global scope por `empresa_id` — el superadmin no tiene empresa.
- `BaseModel` inyecta `empresa_id` automáticamente en `creating` — causaría error.

---

## 3. Guard Sanctum para superadmin

Agregar en `config/auth.php`:

```php
'guards' => [
    'web'        => ['driver' => 'session', 'provider' => 'users'],
    'sanctum'    => ['driver' => 'sanctum'],
    'superadmin' => ['driver' => 'sanctum'],
],
'providers' => [
    'users'       => ['driver' => 'eloquent', 'model' => App\Models\User::class],
    'superadmins' => ['driver' => 'eloquent', 'model' => App\Modules\Superadmin\Models\Superadmin::class],
],
```

> Sanctum usa el modelo del token para resolver el guard. El `SuperadminMiddleware`
> verifica que el modelo autenticado sea instancia de `Superadmin`.

---

## 4. SuperadminMiddleware

```php
namespace App\Shared\Middleware;

use App\Modules\Superadmin\Models\Superadmin;
use App\Shared\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth('sanctum')->user();

        if (! $user instanceof Superadmin) {
            return ApiResponse::error('Acceso no autorizado', [], 403);
        }

        if (! $user->activo) {
            return ApiResponse::error('Tu cuenta de superadmin está desactivada.', [], 401);
        }

        return $next($request);
    }
}
```

**Registro en `bootstrap/app.php`**:
```php
$middleware->alias([
    // ... existentes ...
    'superadmin' => SuperadminMiddleware::class,
]);
```

---

## 5. Migraciones (orden)

### `2026_03_06_000001_create_superadmins_table`
```php
Schema::create('superadmins', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nombre', 150);
    $table->string('email', 255)->unique();
    $table->string('password', 255);
    $table->boolean('activo')->default(true);
    $table->timestamp('last_login')->nullable();
    $table->timestamps();
});
```

### `2026_03_06_000002_create_impersonation_logs_table`
```php
Schema::create('impersonation_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('superadmin_id')->constrained('superadmins');
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->string('token_hash', 64);
    $table->timestamp('started_at');
    $table->timestamp('ended_at')->nullable();
    $table->string('ip', 45);
    $table->index('superadmin_id');
    $table->index('empresa_id');
    $table->index('started_at');
});

// Índice único parcial — Laravel Blueprint no soporta WHERE nativo
DB::statement('
    CREATE UNIQUE INDEX uq_impersonation_activa
    ON impersonation_logs (empresa_id, superadmin_id)
    WHERE ended_at IS NULL
');
```
> Este índice garantiza que `TerminarImpersonacionService` siempre resuelve exactamente
> 1 registro al buscar por `empresa_id + superadmin_id + ended_at IS NULL`,
> eliminando cualquier ambigüedad por múltiples impersonaciones pasadas.

### `2026_03_06_000003_create_descuentos_tenant_table`
```php
Schema::create('descuentos_tenant', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->foreignUuid('superadmin_id')->constrained('superadmins');
    $table->string('tipo', 15);          // porcentaje|monto_fijo
    $table->decimal('valor', 8, 2);
    $table->string('motivo', 255);
    $table->boolean('activo')->default(true);
    $table->timestamps();
    $table->index(['empresa_id', 'activo']);
});
```

### `2026_03_06_000004_add_superadmin_id_to_audit_logs`
```php
Schema::table('audit_logs', function (Blueprint $table) {
    $table->foreignUuid('superadmin_id')->nullable()->constrained('superadmins')->after('usuario_id');
    $table->index('superadmin_id');
});
```
> En PostgreSQL con RLS activa: drop policy → alter → recrear policy (igual al patrón de `empresa_id` nullable).

---

## 6. Rate Limiter superadmin login

En `AppServiceProvider::boot()`:

```php
RateLimiter::for('superadmin-login', fn (Request $req) =>
    Limit::perMinutes(15, 3)->by($req->ip())
);
```

En `phpunit.xml`: agregar `<env name="SUPERADMIN_LOGIN_RATE_LIMIT" value="3"/>`.

---

## 7. Flujo de impersonación — técnico

```
POST /superadmin/api/empresas/{id}/impersonar
│
├── SuperadminMiddleware (verifica token superadmin)
├── ImpersonarService::execute($empresa_id)
│   ├── Busca owner activo de la empresa
│   ├── Crea token Sanctum para ese owner:
│   │   abilities=['impersonated'], expiry=2h
│   ├── Guarda sha256(token) en impersonation_logs
│   ├── AuditLog: accion=superadmin_impersonation_start
│   └── Retorna { token, empresa, owner }
│
Frontend: recibe token → abre dashboard con ese token en Zustand
          banner rojo: "Estás viendo [Empresa X] como superadmin"

DELETE /superadmin/api/empresas/{id}/impersonar
├── Busca impersonation_log activo (ended_at IS NULL)
├── Elimina el token Sanctum del owner
├── Actualiza ended_at en impersonation_logs
├── AuditLog: accion=superadmin_impersonation_end
└── Retorna 200
```

---

## 8. Bypass de RLS para queries globales

Los servicios del superadmin que necesitan leer TODOS los tenants deben ejecutar:

```php
DB::statement("SET LOCAL app.empresa_id = ''");
```

Esto activa el `CASE WHEN coalesce(..., '') = '' THEN true` de la RLS policy,
retornando todas las filas sin filtro de tenant.

**Alternativa vía scope**: crear un método helper en `BaseModel`:
```php
public static function sinRls(): Builder
{
    DB::statement("SET LOCAL app.empresa_id = ''");
    return static::withoutGlobalScopes();
}
```

---

## 9. Frontend — estructura de rutas

```
frontend/src/app/(superadmin)/
├── layout.tsx                        ← verifica token superadmin en Zustand; renderiza SuperadminSidebar
├── superadmin/
│   ├── login/page.tsx                ← SuperadminLoginForm (fuera del layout protegido)
│   ├── dashboard/page.tsx            ← GlobalDashboard (métricas + gráfico)
│   ├── empresas/
│   │   ├── page.tsx                  ← EmpresasTable con filtros
│   │   └── [id]/page.tsx             ← EmpresaDetalle
│   ├── planes/page.tsx               ← PlanesManager
│   └── logs/page.tsx                 ← LogsViewer + export CSV

frontend/src/modules/superadmin/
├── auth/
│   ├── SuperadminLoginForm.tsx
│   ├── superadmin-login.api.ts
│   └── superadmin-auth.store.ts      ← Zustand store separado del tenant
├── dashboard/
│   ├── GlobalDashboard.tsx
│   ├── MrrChart.tsx                  ← Gráfico de 6 meses (recharts o similar)
│   └── use-dashboard.ts
├── empresas/
│   ├── EmpresasTable.tsx
│   ├── EmpresaDetalle.tsx
│   ├── SuspenderModal.tsx
│   ├── ActivarModal.tsx
│   └── use-empresas.ts
├── impersonacion/
│   ├── ImpersonacionBanner.tsx       ← Banner rojo siempre visible
│   └── use-impersonacion.ts
├── planes/
│   ├── PlanesManager.tsx
│   ├── EditPlanModal.tsx
│   ├── DescuentoModal.tsx
│   └── use-planes.ts
└── logs/
    ├── LogsViewer.tsx
    ├── LogsFilters.tsx
    └── use-logs.ts
```

**Store separado para superadmin** (`superadmin-auth.store.ts`):
```ts
interface SuperadminAuthState {
  accessToken: string | null
  superadmin: { id: string; nombre: string; email: string } | null
  setAccessToken: (token: string) => void
  setSuperadmin: (s: ...) => void
  logout: () => void
}
```

**SuperadminSidebar** (`frontend/src/modules/superadmin/layout/SuperadminSidebar.tsx`):

```tsx
// Ítems de navegación — completamente independiente del Sidebar tenant
const NAV = [
  { href: '/superadmin/dashboard', label: 'Dashboard',  icon: BarChart2  },
  { href: '/superadmin/empresas',  label: 'Empresas',   icon: Building2  },
  { href: '/superadmin/planes',    label: 'Planes',     icon: CreditCard },
  { href: '/superadmin/logs',      label: 'Logs',       icon: ScrollText },
]
```

- NO importa ni renderiza ningún componente del sidebar tenant.
- NO muestra módulos de negocio (facturación, clientes, etc.).
- Sección inferior: nombre + email del superadmin + botón "Cerrar sesión" (mismo patrón de confirmación que el sidebar tenant).
- El layout `(superadmin)/layout.tsx` renderiza `SuperadminSidebar` + `<main>` para el contenido, igual que el layout dashboard tenant pero con store de superadmin.

**Middleware Next.js** — separar protección de rutas:
- `/superadmin/login` → público, redirige a `/superadmin/dashboard` si ya autenticado
- `/superadmin/*` (resto) → verifica `superadmin_token` en store/cookie, redirige a `/superadmin/login` si no autenticado
- `/dashboard/*` → verifica `access_token` tenant (existente, sin cambios)

---

## 10. Seeder SuperadminSeeder

```php
namespace Database\Seeders;

use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        Superadmin::firstOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'admin@operaai.com')],
            [
                'nombre'   => env('SUPERADMIN_NOMBRE', 'Admin OperaAI'),
                'password' => Hash::make(env('SUPERADMIN_PASSWORD')),
                'activo'   => true,
            ]
        );
    }
}
```

Variables en `.env`:
```
SUPERADMIN_EMAIL=admin@operaai.com
SUPERADMIN_NOMBRE="Admin OperaAI"
SUPERADMIN_PASSWORD=cambiar_esto_en_produccion
```

---

## 11. Estructura de directorios backend

```
backend/app/Modules/Superadmin/
├── Models/
│   └── Superadmin.php
├── Auth/
│   ├── Login/
│   │   ├── LoginSuperadminController.php
│   │   ├── LoginSuperadminRequest.php
│   │   └── LoginSuperadminService.php
│   └── Logout/
│       ├── LogoutSuperadminController.php
│       └── LogoutSuperadminService.php
├── Dashboard/
│   ├── DashboardController.php
│   └── DashboardService.php
├── Empresas/
│   ├── ListarEmpresas/
│   │   ├── ListarEmpresasController.php
│   │   └── ListarEmpresasService.php
│   ├── GetEmpresaDetalle/
│   │   ├── GetEmpresaDetalleController.php
│   │   └── GetEmpresaDetalleService.php
│   ├── SuspenderEmpresa/
│   │   ├── SuspenderEmpresaController.php
│   │   └── SuspenderEmpresaService.php
│   ├── ActivarEmpresa/
│   │   ├── ActivarEmpresaController.php
│   │   └── ActivarEmpresaService.php
│   └── Impersonar/
│       ├── ImpersonarController.php
│       ├── ImpersonarService.php
│       ├── TerminarImpersonacionController.php
│       └── TerminarImpersonacionService.php
├── Planes/
│   ├── ListarPlanes/
│   │   ├── ListarPlanesController.php
│   │   └── ListarPlanesService.php
│   ├── UpdatePlan/
│   │   ├── UpdatePlanController.php
│   │   ├── UpdatePlanRequest.php
│   │   └── UpdatePlanService.php
│   └── Descuento/
│       ├── AplicarDescuentoController.php
│       ├── AplicarDescuentoRequest.php
│       ├── AplicarDescuentoService.php
│       ├── DesactivarDescuentoController.php
│       └── DesactivarDescuentoService.php
└── Logs/
    ├── LogsGlobalesController.php
    ├── LogsGlobalesService.php
    ├── ExportLogsCSVController.php
    ├── ExportLogsCSVService.php
    └── ResumenLogsController.php

backend/tests/Feature/Superadmin/
├── Auth/
│   └── LoginSuperadminTest.php
├── Dashboard/
│   └── DashboardTest.php
├── Empresas/
│   ├── ListarEmpresasTest.php
│   ├── GetEmpresaDetalleTest.php
│   ├── SuspenderEmpresaTest.php
│   ├── ActivarEmpresaTest.php
│   └── ImpersonarTest.php
├── Planes/
│   ├── UpdatePlanTest.php
│   └── DescuentoTest.php
├── Logs/
│   └── LogsGlobalesTest.php
└── Helpers/
    └── SuperadminHelper.php
```

---

## 12. Rutas superadmin (`routes/superadmin.php`)

```php
use App\Modules\Superadmin\Auth\Login\LoginSuperadminController;
use App\Modules\Superadmin\Auth\Logout\LogoutSuperadminController;
// ... demás imports

// Públicas
Route::post('auth/login', LoginSuperadminController::class)
    ->middleware('throttle:superadmin-login');

// Protegidas
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
```
