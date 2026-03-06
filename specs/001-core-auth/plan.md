# Plan Técnico: Módulo Core / Auth

**Branch**: `001-core-auth` | **Date**: 2026-03-05 | **Spec**: [spec.md](./spec.md)
**Email**: Opción A confirmada — email UNIQUE global, un email = una empresa en todo el sistema.

---

## 1. Migraciones (orden de ejecución)

### `2026_03_05_000001_create_planes_table`

```php
Schema::create('planes', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('nombre', 20)->unique();         // starter|pyme|enterprise
    $table->string('nombre_display', 50);
    $table->decimal('precio_mensual', 8, 2);
    $table->unsignedInteger('max_usuarios')->nullable(); // null = ilimitado
    $table->jsonb('modulos');                        // ["facturacion","clientes",...]
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### `2026_03_05_000002_create_empresas_table`

```php
Schema::create('empresas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('ruc', 11)->unique();
    $table->string('razon_social', 200);
    $table->string('nombre_comercial', 200);
    $table->text('direccion');
    $table->string('ubigeo', 6)->nullable();
    $table->string('logo_url', 500)->nullable();
    $table->string('regimen_tributario', 3);        // RER|RG|RMT
    $table->timestamps();
    $table->index('created_at');
});
```

### `2026_03_05_000003_create_suscripciones_table`

```php
Schema::create('suscripciones', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->foreignUuid('plan_id')->constrained('planes');
    $table->foreignUuid('downgrade_plan_id')->nullable()->constrained('planes');
    $table->string('estado', 10);                   // trial|activa|vencida|cancelada
    $table->date('fecha_inicio');
    $table->date('fecha_vencimiento');
    $table->date('fecha_proximo_cobro')->nullable();
    $table->date('fecha_cancelacion')->nullable();
    $table->string('culqi_subscription_id', 100)->nullable();
    $table->string('culqi_customer_id', 100)->nullable();   // ID cliente en Culqi
    $table->string('culqi_card_id', 100)->nullable();       // ID card token guardado en Culqi
    $table->string('card_last4', 4)->nullable();            // últimos 4 dígitos
    $table->string('card_brand', 20)->nullable();           // Visa, Mastercard, etc.
    $table->timestamps();
    $table->index('empresa_id');
    $table->index('estado');
    $table->index('fecha_vencimiento');
});
```

> `downgrade_plan_id`: registra el plan al que se bajará en el próximo período.
> Confirmado como adición necesaria para el flujo de downgrade.
>
> `culqi_customer_id` / `culqi_card_id`: necesarios para cobros recurrentes mensuales.
> Culqi no provee API de suscripciones — el sistema guarda el card token para cobros manuales.
> `card_last4` / `card_brand`: expuestos en `GET /api/suscripcion` como campo `datos_pago`.

### `2026_03_05_000004_create_usuarios_table`

```php
Schema::create('usuarios', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->string('nombre', 150);
    $table->string('email', 255)->unique();         // UNIQUE global — Opción A confirmada
    $table->string('password', 255);
    $table->string('rol', 10);                      // owner|admin|empleado|contador
    $table->boolean('activo')->default(true);
    $table->timestamp('last_login')->nullable();
    $table->timestamps();
    $table->index('empresa_id');
    $table->index('activo');
});
```

### `2026_03_05_000005_create_invitaciones_usuario_table`

```php
Schema::create('invitaciones_usuario', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->string('email', 255);
    $table->string('rol', 10);
    $table->string('token', 100)->unique();
    $table->foreignUuid('invitado_por')->constrained('usuarios');
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamp('created_at');
    $table->index('empresa_id');
    $table->index('email');
    $table->index('expires_at');
});
```

### `2026_03_05_000006_create_audit_logs_table`

```php
Schema::create('audit_logs', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('empresa_id')->constrained('empresas');
    $table->foreignUuid('usuario_id')->nullable()->constrained('usuarios');
    $table->string('accion', 50);
    $table->string('tabla_afectada', 50)->nullable();
    $table->uuid('registro_id')->nullable();
    $table->jsonb('datos_anteriores')->nullable();
    $table->jsonb('datos_nuevos')->nullable();
    $table->string('ip', 45);
    $table->timestamp('created_at');
    $table->index('empresa_id');
    $table->index('usuario_id');
    $table->index('accion');
    $table->index('created_at');
});
```

### `2026_03_05_000007_create_password_reset_tokens_table`

```php
Schema::create('password_reset_tokens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email', 255)->index();
    $table->string('token', 100)->unique();         // SHA-256 del token plain
    $table->timestamp('expires_at');
    $table->timestamp('used_at')->nullable();
    $table->timestamp('created_at');
});
```

### `2026_03_05_000008_add_rls_policies`

```php
public function up(): void
{
    $tables = ['empresas', 'suscripciones', 'usuarios', 'invitaciones_usuario', 'audit_logs'];

    foreach ($tables as $table) {
        DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY tenant_isolation ON {$table}
            USING (empresa_id = current_setting('app.empresa_id', true)::uuid)
        ");
    }
}

public function down(): void
{
    $tables = ['empresas', 'suscripciones', 'usuarios', 'invitaciones_usuario', 'audit_logs'];
    foreach ($tables as $table) {
        DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
        DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
    }
}
```

### Sanctum (vendor migration)

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
# Genera: 2019_12_14_000001_create_personal_access_tokens_table
```

---

## 2. Seeders

### `PlanSeeder`

```php
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $planes = [
            [
                'nombre'          => 'starter',
                'nombre_display'  => 'Starter',
                'precio_mensual'  => 59.00,
                'max_usuarios'    => 3,
                'modulos'         => ['facturacion', 'clientes', 'productos'],
                'activo'          => true,
            ],
            [
                'nombre'          => 'pyme',
                'nombre_display'  => 'PYME',
                'precio_mensual'  => 129.00,
                'max_usuarios'    => 15,
                'modulos'         => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia'],
                'activo'          => true,
            ],
            [
                'nombre'          => 'enterprise',
                'nombre_display'  => 'Enterprise',
                'precio_mensual'  => 299.00,
                'max_usuarios'    => null,
                'modulos'         => ['facturacion', 'clientes', 'productos', 'inventario', 'crm', 'finanzas', 'ia', 'rrhh'],
                'activo'          => true,
            ],
        ];

        foreach ($planes as $data) {
            Plan::updateOrCreate(['nombre' => $data['nombre']], $data);
        }
    }
}
```

---

## 3. Modelos

### `Shared/Models/BaseModel.php`

```php
abstract class BaseModel extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(fn($m) => $m->id ??= (string) Str::uuid());

        static::addGlobalScope('empresa', function (Builder $query) {
            if (auth()->check() && isset($query->getModel()->empresa_id)) {
                $query->where(
                    $query->getModel()->getTable() . '.empresa_id',
                    auth()->user()->empresa_id
                );
            }
        });
    }
}
```

### `Modules/Core/Models/Plan.php`

```php
class Plan extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nombre', 'nombre_display', 'precio_mensual',
        'max_usuarios', 'modulos', 'activo',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'max_usuarios'   => 'integer',
        'modulos'        => 'array',
        'activo'         => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn($m) => $m->id ??= (string) Str::uuid());
    }

    // Scopes
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('precio_mensual');
    }

    public function esUpgradeDe(Plan $actual): bool
    {
        return $this->precio_mensual > $actual->precio_mensual;
    }

    public function esDowngradeDe(Plan $actual): bool
    {
        return $this->precio_mensual < $actual->precio_mensual;
    }

    // Relations
    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class, 'plan_id');
    }
}
```

### `Modules/Core/Models/Empresa.php`

```php
class Empresa extends BaseModel
{
    protected $fillable = [
        'ruc', 'razon_social', 'nombre_comercial',
        'direccion', 'ubigeo', 'logo_url', 'regimen_tributario',
    ];

    // RUC inmutable — sin setter
    public function setRucAttribute(): never
    {
        throw new \LogicException('El RUC no puede modificarse después del registro.');
    }

    // Relations
    public function suscripcionActiva(): HasOne
    {
        return $this->hasOne(Suscripcion::class)
            ->whereIn('estado', ['trial', 'activa', 'vencida'])
            ->latest();
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
```

### `Modules/Core/Models/Suscripcion.php`

```php
class Suscripcion extends BaseModel
{
    protected $fillable = [
        'empresa_id', 'plan_id', 'downgrade_plan_id', 'estado',
        'fecha_inicio', 'fecha_vencimiento', 'fecha_proximo_cobro',
        'fecha_cancelacion', 'culqi_subscription_id',
    ];

    protected $casts = [
        'fecha_inicio'         => 'date',
        'fecha_vencimiento'    => 'date',
        'fecha_proximo_cobro'  => 'date',
        'fecha_cancelacion'    => 'date',
    ];

    // Estados
    public function esTrial(): bool    { return $this->estado === 'trial'; }
    public function esActiva(): bool   { return $this->estado === 'activa'; }
    public function esVencida(): bool  { return $this->estado === 'vencida'; }
    public function esCancelada(): bool{ return $this->estado === 'cancelada'; }

    public function permiteEscritura(): bool
    {
        return in_array($this->estado, ['trial', 'activa']);
    }

    public function calcularMontoProrrateo(Plan $planNuevo): float
    {
        $diasRestantes = now()->diffInDays($this->fecha_vencimiento, false);
        $diasRestantes = max(0, $diasRestantes);
        $diferenciaPrecio = $planNuevo->precio_mensual - $this->plan->precio_mensual;
        return round(($diferenciaPrecio / 30) * $diasRestantes, 2);
    }

    // Relations
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function downgradePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'downgrade_plan_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
```

### `Modules/Core/Models/Usuario.php`

```php
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
        'password'   => 'hashed',   // Laravel 10+ auto-bcrypt
    ];

    // Email inmutable
    public function setEmailAttribute(): never
    {
        throw new \LogicException('El email no puede modificarse después del registro.');
    }

    // Scopes
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('rol', 'owner')->where('activo', true);
    }

    // Helpers de rol
    public function esOwner(): bool    { return $this->rol === 'owner'; }
    public function esAdmin(): bool    { return $this->rol === 'admin'; }
    public function puedeGestionar(): bool { return in_array($this->rol, ['owner', 'admin']); }

    // Relations
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function invitacionesEnviadas(): HasMany
    {
        return $this->hasMany(InvitacionUsuario::class, 'invitado_por');
    }
}
```

### `Modules/Core/Models/InvitacionUsuario.php`

```php
class InvitacionUsuario extends BaseModel
{
    protected $fillable = [
        'empresa_id', 'email', 'rol', 'token', 'invitado_por',
        'expires_at', 'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function estaVigente(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function scopePendientes(Builder $query): Builder
    {
        return $query->whereNull('used_at')->where('expires_at', '>', now());
    }

    public function invitadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'invitado_por');
    }
}
```

### `Modules/Core/Models/AuditLog.php`

```php
class AuditLog extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'empresa_id', 'usuario_id', 'accion', 'tabla_afectada',
        'registro_id', 'datos_anteriores', 'datos_nuevos', 'ip',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos'     => 'array',
        'created_at'       => 'datetime',
    ];

    public static function registrar(string $accion, array $extra = []): void
    {
        static::create(array_merge([
            'empresa_id'  => auth()->user()?->empresa_id,
            'usuario_id'  => auth()->id(),
            'accion'      => $accion,
            'ip'          => request()->ip(),
            'created_at'  => now(),
        ], $extra));
    }
}
```

---

## 4. Shared — Clases Base e Infraestructura

### `Shared/Http/Responses/ApiResponse.php`

```php
class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public static function error(string $message, array $errors = [], int $status = 422): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors) $body['errors'] = $errors;
        return response()->json($body, $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $paginator->items(),
            'meta'    => [
                'page'     => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total'    => $paginator->total(),
            ],
        ]);
    }
}
```

### `Shared/Middleware/TenantMiddleware.php`

```php
class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $empresaId = auth()->user()->empresa_id;

        // Capa 2: inyectar empresa_id en la sesión PostgreSQL para RLS (Capa 3)
        DB::statement("SET LOCAL app.empresa_id = '{$empresaId}'");

        return $next($request);
    }
}
```

### `Shared/Middleware/CheckPlanMiddleware.php`

```php
class CheckPlanMiddleware
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $suscripcion = auth()->user()->empresa->suscripcionActiva;

        if (!$suscripcion || $suscripcion->esCancelada()) {
            return ApiResponse::error('Tu suscripción está cancelada.', [], 402);
        }

        $plan = $suscripcion->plan;

        if (!in_array($modulo, $plan->modulos)) {
            return ApiResponse::error(
                "Tu plan no incluye el módulo '{$modulo}'. Mejora tu plan para acceder.",
                [],
                403
            );
        }

        return $next($request);
    }
}
```

### `Shared/Middleware/SuscripcionActivaMiddleware.php`

```php
// Verifica que suscripción permite escritura (trial o activa)
// Excepciones: POST /api/suscripcion/upgrade siempre pasa
class SuscripcionActivaMiddleware
{
    private const RUTAS_PERMITIDAS_VENCIDA = [
        'POST:api/suscripcion/upgrade',
        'POST:api/auth/logout',
        'GET:api/me',
        'GET:api/suscripcion',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $suscripcion = auth()->user()->empresa->suscripcionActiva;

        if ($suscripcion?->esCancelada()) {
            return ApiResponse::error('Tu suscripción está cancelada.', ['redirect' => '/reactivar'], 402);
        }

        if ($suscripcion?->esVencida()) {
            $key = $request->method() . ':' . $request->path();
            $isReadOnly = in_array($request->method(), ['GET', 'HEAD']);
            $isExcluida = in_array($key, self::RUTAS_PERMITIDAS_VENCIDA);

            if (!$isReadOnly && !$isExcluida) {
                return ApiResponse::error(
                    'Tu suscripción ha vencido. Activa tu plan para continuar operando.',
                    ['redirect' => '/configuracion/plan'],
                    402
                );
            }
        }

        return $next($request);
    }
}
```

### `Shared/Middleware/AuditLogMiddleware.php`

```php
// Registra automáticamente en audit_logs para rutas marcadas
// Se usa via route middleware: ->middleware('audit:login')
class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next, string $accion): Response
    {
        $response = $next($request);

        if ($response->isSuccessful() && auth()->check()) {
            AuditLog::registrar($accion);
        }

        return $response;
    }
}
```

---

## 5. Slices Backend

### Auth / Register

**`RegisterRequest.php`**
```php
public function rules(): array
{
    return [
        'plan_id'                   => 'required|uuid|exists:planes,id',
        'empresa.ruc'               => 'required|string|digits:11|unique:empresas,ruc',
        'empresa.razon_social'      => 'required|string|max:200',
        'empresa.nombre_comercial'  => 'required|string|max:200',
        'empresa.direccion'         => 'required|string',
        'empresa.ubigeo'            => 'nullable|string|digits:6',
        'empresa.regimen_tributario'=> 'required|in:RER,RG,RMT',
        'owner.nombre'              => 'required|string|max:150',
        'owner.email'               => 'required|email|max:255|unique:usuarios,email',
        'owner.password'            => 'required|string|min:8|confirmed',
    ];
}

public function messages(): array
{
    return [
        'empresa.ruc.digits'           => 'El RUC debe tener exactamente 11 dígitos numéricos.',
        'empresa.ruc.unique'           => 'Ya existe una empresa con este RUC.',
        'owner.email.unique'           => 'Este email ya tiene una cuenta.',
        'owner.password.confirmed'     => 'Las contraseñas no coinciden.',
        'owner.password.min'           => 'La contraseña debe tener al menos 8 caracteres.',
    ];
}
```

**`RegisterController.php`**
```php
public function __invoke(RegisterRequest $request, RegisterService $service): JsonResponse
{
    $result = $service->execute($request->validated());
    return ApiResponse::success($result, 'Empresa registrada exitosamente', 201)
        ->cookie('refresh_token', $result['refresh_token'], 43200, '/', null, true, true)
        ->cookie('has_session', '1', 43200, '/', null, true, false);
}
```

**`RegisterService.php`**
```php
public function execute(array $data): array
{
    return DB::transaction(function () use ($data) {
        $plan = Plan::findOrFail($data['plan_id']);

        // 1. Crear empresa
        $empresa = Empresa::create($data['empresa']);

        // 2. Crear suscripción trial 30 días
        $suscripcion = Suscripcion::create([
            'empresa_id'        => $empresa->id,
            'plan_id'           => $plan->id,
            'estado'            => 'trial',
            'fecha_inicio'      => today(),
            'fecha_vencimiento' => today()->addDays(30),
        ]);

        // 3. Crear usuario owner
        $owner = Usuario::create([
            'empresa_id' => $empresa->id,
            'nombre'     => $data['owner']['nombre'],
            'email'      => $data['owner']['email'],
            'password'   => $data['owner']['password'], // auto-hashed via cast
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        // 4. Emitir tokens
        [$accessToken, $refreshToken] = $this->emitirTokens($owner);

        // 5. Audit log
        AuditLog::create([
            'empresa_id' => $empresa->id,
            'usuario_id' => $owner->id,
            'accion'     => 'register',
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);

        // 6. Email bienvenida (queued)
        Mail::to($owner->email)->queue(new BienvenidaMail($owner, $empresa, $plan));

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,  // se envía en cookie, no en body
            'token_type'    => 'Bearer',
            'expires_in'    => 900,
            'user'          => $this->buildUserPayload($owner, $empresa, $suscripcion, $plan),
        ];
    });
}

private function emitirTokens(Usuario $usuario): array
{
    $access  = $usuario->createToken('access',   ['*'],       now()->addMinutes(15));
    $refresh = $usuario->createToken('refresh',  ['refresh'], now()->addDays(30));
    return [$access->plainTextToken, $refresh->plainTextToken];
}

private function buildUserPayload(Usuario $u, Empresa $e, Suscripcion $s, Plan $p): array
{
    return [
        'id'     => $u->id,
        'nombre' => $u->nombre,
        'email'  => $u->email,
        'rol'    => $u->rol,
        'empresa' => [
            'id'              => $e->id,
            'nombre_comercial'=> $e->nombre_comercial,
            'ruc'             => $e->ruc,
            'logo_url'        => $e->logo_url,
        ],
        'suscripcion' => [
            'plan'              => $p->nombre,
            'estado'            => $s->estado,
            'fecha_vencimiento' => $s->fecha_vencimiento->toDateString(),
            'modulos'           => $p->modulos,
        ],
    ];
}
```

---

### Auth / Login

**`LoginRequest.php`**
```php
public function rules(): array
{
    return [
        'email'    => 'required|email',
        'password' => 'required|string',
    ];
}
```

**`LoginService.php`**
```php
public function execute(array $data): array
{
    $usuario = Usuario::withoutGlobalScope('empresa')
        ->where('email', $data['email'])
        ->first();

    if (!$usuario || !Hash::check($data['password'], $usuario->password)) {
        AuditLog::create([
            'empresa_id' => $usuario?->empresa_id,
            'usuario_id' => $usuario?->id,
            'accion'     => 'login_failed',
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
        throw new AuthenticationException('Credenciales incorrectas.');
    }

    if (!$usuario->activo) {
        throw new AuthenticationException('Tu cuenta ha sido desactivada. Contacta al administrador.');
    }

    // Actualizar last_login
    $usuario->update(['last_login' => now()]);

    // Emitir tokens
    $usuario->tokens()->where('name', 'access')->delete(); // limpiar access tokens anteriores
    $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
    $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

    AuditLog::create([
        'empresa_id' => $usuario->empresa_id,
        'usuario_id' => $usuario->id,
        'accion'     => 'login',
        'ip'         => request()->ip(),
        'created_at' => now(),
    ]);

    $empresa     = $usuario->empresa;
    $suscripcion = $empresa->suscripcionActiva;
    $plan        = $suscripcion->plan;

    return [
        'access_token'  => $access->plainTextToken,
        'refresh_token' => $refresh->plainTextToken,
        'token_type'    => 'Bearer',
        'expires_in'    => 900,
        'user'          => [/* mismo buildUserPayload que Register */],
    ];
}
```

---

### Auth / RefreshToken

**`RefreshTokenService.php`**
```php
public function execute(Request $request): array
{
    $cookieToken = $request->cookie('refresh_token');
    if (!$cookieToken) throw new AuthenticationException('Sesión expirada.');

    // Buscar token en Sanctum
    [$id, $token] = explode('|', $cookieToken, 2);
    $pat = PersonalAccessToken::find($id);

    if (!$pat || !hash_equals($pat->token, hash('sha256', $token))) {
        throw new AuthenticationException('Sesión expirada.');
    }

    if ($pat->expires_at && $pat->expires_at->isPast()) {
        $pat->delete();
        throw new AuthenticationException('Sesión expirada. Inicia sesión nuevamente.');
    }

    if ($pat->name !== 'refresh') {
        throw new AuthenticationException('Token inválido.');
    }

    $usuario = $pat->tokenable;

    // Rotar: borrar refresh anterior, emitir nuevo
    $pat->delete();
    $usuario->tokens()->where('name', 'access')->delete();

    $newAccess  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
    $newRefresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

    return [
        'access_token'  => $newAccess->plainTextToken,
        'refresh_token' => $newRefresh->plainTextToken,
        'token_type'    => 'Bearer',
        'expires_in'    => 900,
    ];
}
```

---

### Auth / Logout

**`LogoutService.php`**
```php
public function execute(Usuario $usuario): void
{
    $usuario->tokens()->delete(); // TODAS las sesiones

    AuditLog::registrar('logout_all');
}
```

**`LogoutController.php`**
```php
public function __invoke(LogoutService $service): JsonResponse
{
    $service->execute(auth()->user());

    return ApiResponse::success(null, 'Sesión cerrada')
        ->withCookie(Cookie::forget('refresh_token'))
        ->withCookie(Cookie::forget('has_session'));
}
```

---

### Auth / RecuperarPassword

**`RecuperarPasswordService.php`**
```php
public function solicitarReset(string $email): void
{
    // Siempre retornar éxito (no confirmar si el email existe)
    $usuario = Usuario::withoutGlobalScope('empresa')
        ->where('email', $email)
        ->first();

    if (!$usuario) return; // silencioso

    // Invalidar tokens anteriores para este email
    PasswordResetToken::where('email', $email)->delete();

    $plainToken = Str::random(64);

    PasswordResetToken::create([
        'email'      => $email,
        'token'      => hash('sha256', $plainToken),
        'expires_at' => now()->addMinutes(60),
        'created_at' => now(),
    ]);

    Mail::to($email)->queue(new RecuperarPasswordMail($usuario, $plainToken));
}

public function resetPassword(array $data): void
{
    $record = PasswordResetToken::where('email', $data['email'])
        ->whereNull('used_at')
        ->where('expires_at', '>', now())
        ->first();

    if (!$record || !hash_equals($record->token, hash('sha256', $data['token']))) {
        throw new ValidationException('Token inválido o expirado.');
    }

    $usuario = Usuario::withoutGlobalScope('empresa')
        ->where('email', $data['email'])
        ->firstOrFail();

    DB::transaction(function () use ($usuario, $data, $record) {
        $usuario->update(['password' => $data['password']]);
        $record->update(['used_at' => now()]);
        $usuario->tokens()->delete();
        AuditLog::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'accion'     => 'password_changed',
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);
    });
}
```

---

### Empresa / UpdateEmpresa

**`UpdateEmpresaRequest.php`**
```php
public function rules(): array
{
    return [
        'nombre_comercial'   => 'sometimes|string|min:2|max:200',
        'direccion'          => 'sometimes|string',
        'ubigeo'             => 'sometimes|nullable|string|digits:6',
        'regimen_tributario' => 'sometimes|in:RER,RG,RMT',
    ];
}
```

**`UpdateEmpresaService.php`**
```php
public function execute(array $data): Empresa
{
    $empresa   = auth()->user()->empresa;
    $anterior  = $empresa->only(array_keys($data));

    $empresa->update($data);

    AuditLog::registrar('empresa_actualizada', [
        'tabla_afectada'   => 'empresas',
        'registro_id'      => $empresa->id,
        'datos_anteriores' => $anterior,
        'datos_nuevos'     => $data,
    ]);

    return $empresa->fresh();
}
```

---

### Empresa / UploadLogo

**`UploadLogoRequest.php`**
```php
public function rules(): array
{
    return ['logo' => 'required|file|mimes:jpg,jpeg,png|max:2048'];
}

public function messages(): array
{
    return [
        'logo.mimes' => 'Solo se aceptan archivos JPG y PNG.',
        'logo.max'   => 'El archivo no debe superar 2MB.',
    ];
}
```

**`UploadLogoService.php`**
```php
public function execute(UploadedFile $file): string
{
    $empresa   = auth()->user()->empresa;
    $timestamp = now()->timestamp;
    $ext       = $file->getClientOriginalExtension();
    $path      = "logos/{$empresa->id}/{$timestamp}.{$ext}";

    // Eliminar logo anterior si existe
    if ($empresa->logo_url) {
        $oldPath = parse_url($empresa->logo_url, PHP_URL_PATH);
        Storage::disk('r2')->delete(ltrim($oldPath, '/'));
    }

    Storage::disk('r2')->put($path, file_get_contents($file));
    $url = Storage::disk('r2')->url($path);

    $empresa->update(['logo_url' => $url]);

    AuditLog::registrar('logo_actualizado', [
        'tabla_afectada' => 'empresas',
        'registro_id'    => $empresa->id,
    ]);

    return $url;
}
```

---

### Suscripcion / UpgradePlan

**`UpgradePlanRequest.php`**
```php
public function rules(): array
{
    return [
        'plan_id'     => 'required|uuid|exists:planes,id',
        'culqi_token' => 'required|string',
    ];
}
```

**`UpgradePlanService.php`**
```php
public function execute(array $data): array
{
    $usuario     = auth()->user();
    $suscripcion = $usuario->empresa->suscripcionActiva;
    $planNuevo   = Plan::findOrFail($data['plan_id']);

    if (!$planNuevo->esUpgradeDe($suscripcion->plan)) {
        throw new ValidationException('El plan seleccionado no es superior al actual.');
    }

    $montoProrrateo = $suscripcion->calcularMontoProrrateo($planNuevo);

    // Intentar cobro Culqi de forma inmediata
    try {
        $charge = $this->cobrarCulqi($data['culqi_token'], $montoProrrateo, $usuario, $planNuevo);
        return $this->aplicarUpgrade($suscripcion, $planNuevo, $charge, $usuario);

    } catch (HttpException $e) {
        // Timeout → encolar Job con reintentos
        $jobId = (string) Str::uuid();
        UpgradePlanJob::dispatch($suscripcion->id, $planNuevo->id, $data['culqi_token'], $usuario->id, $jobId);

        AuditLog::registrar('plan_upgrade_queued', [
            'datos_nuevos' => ['plan' => $planNuevo->nombre, 'job_id' => $jobId],
        ]);

        return ['job_id' => $jobId, 'estado' => 'procesando'];
    }
}

private function cobrarCulqi(string $token, float $monto, Usuario $usuario, Plan $plan): array
{
    Culqi::setApiKey(config('services.culqi.api_key'));
    $charge = Culqi::$charge->create([
        'amount'        => (int) ($monto * 100),  // centavos
        'currency_code' => 'PEN',
        'source_id'     => $token,
        'email'         => $usuario->email,
        'metadata'      => [
            'empresa_id' => $usuario->empresa_id,
            'plan'       => $plan->nombre,
        ],
    ]);

    if (isset($charge->object) && $charge->object === 'error') {
        throw new PaymentException($charge->user_message ?? 'Pago rechazado.');
    }

    return (array) $charge;
}

private function aplicarUpgrade(Suscripcion $sus, Plan $plan, array $charge, Usuario $usuario): array
{
    return DB::transaction(function () use ($sus, $plan, $charge, $usuario) {
        $sus->update([
            'plan_id'             => $plan->id,
            'estado'              => 'activa',
            'fecha_vencimiento'   => today()->addMonth(),
            'fecha_proximo_cobro' => today()->addMonth(),
            'downgrade_plan_id'   => null,
        ]);

        // Revocar tokens actuales y emitir nuevos con módulos actualizados
        $usuario->tokens()->delete();
        $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
        $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

        AuditLog::registrar('plan_upgrade', ['datos_nuevos' => ['plan' => $plan->nombre]]);
        Mail::to($usuario->email)->queue(new UpgradePlanMail($usuario, $plan));

        return [
            'access_token' => $access->plainTextToken,
            'refresh_token'=> $refresh->plainTextToken,
            'suscripcion'  => ['plan' => $plan->nombre, 'estado' => 'activa', 'modulos' => $plan->modulos],
            'cobro'        => ['monto' => number_format($charge['amount'] / 100, 2), 'descripcion' => "Upgrade a {$plan->nombre_display}"],
        ];
    });
}
```

---

### Usuario / InviteUsuario

**`InviteUsuarioService.php`**
```php
public function execute(array $data): InvitacionUsuario
{
    $usuario     = auth()->user();
    $empresa     = $usuario->empresa;
    $suscripcion = $empresa->suscripcionActiva;
    $plan        = $suscripcion->plan;

    // Verificar límite de usuarios
    if ($plan->max_usuarios !== null) {
        $actuales  = Usuario::activos()->count();
        $pendientes = InvitacionUsuario::pendientes()->count();
        if (($actuales + $pendientes) >= $plan->max_usuarios) {
            throw new ValidationException(
                "Tu plan permite máximo {$plan->max_usuarios} usuarios. Mejora tu plan para agregar más."
            );
        }
    }

    // Verificar no existe ya en la empresa
    if (Usuario::where('email', $data['email'])->exists()) {
        throw new ValidationException('Este email ya es parte de tu equipo.');
    }

    // Verificar no hay invitación pendiente
    if (InvitacionUsuario::pendientes()->where('email', $data['email'])->exists()) {
        throw new ValidationException('Ya hay una invitación pendiente para este email.');
    }

    $token = Str::random(64);

    $invitacion = InvitacionUsuario::create([
        'empresa_id'  => $empresa->id,
        'email'       => $data['email'],
        'rol'         => $data['rol'],
        'token'       => $token,
        'invitado_por'=> $usuario->id,
        'expires_at'  => now()->addHours(48),
    ]);

    Mail::to($data['email'])->queue(new InvitacionUsuarioMail($invitacion, $empresa, $token));
    AuditLog::registrar('usuario_invitado', ['datos_nuevos' => ['email' => $data['email'], 'rol' => $data['rol']]]);

    return $invitacion;
}
```

### Usuario / ActivarCuenta (link de invitación)

**`ActivarCuentaService.php`**
```php
public function execute(array $data): array
{
    $invitacion = InvitacionUsuario::withoutGlobalScope('empresa')
        ->where('token', $data['token'])
        ->whereNull('used_at')
        ->where('expires_at', '>', now())
        ->first();

    if (!$invitacion) throw new ValidationException('Esta invitación no es válida o ha expirado.');

    return DB::transaction(function () use ($data, $invitacion) {
        $usuario = Usuario::create([
            'empresa_id' => $invitacion->empresa_id,
            'nombre'     => $data['nombre'],
            'email'      => $invitacion->email,
            'password'   => $data['password'],
            'rol'        => $invitacion->rol,
            'activo'     => true,
        ]);

        $invitacion->update(['used_at' => now()]);

        $access  = $usuario->createToken('access',  ['*'],       now()->addMinutes(15));
        $refresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

        AuditLog::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'accion'     => 'usuario_activado',
            'ip'         => request()->ip(),
            'created_at' => now(),
        ]);

        return [
            'access_token'  => $access->plainTextToken,
            'refresh_token' => $refresh->plainTextToken,
            'user'          => $usuario->load('empresa'),
        ];
    });
}
```

### Usuario / DesactivarUsuario

**`DesactivarUsuarioService.php`**
```php
public function execute(string $id): Usuario
{
    $actor   = auth()->user();
    $usuario = Usuario::findOrFail($id); // empresa_id filtrado por BaseModel scope

    if ($actor->id === $usuario->id) {
        throw new ValidationException('No puedes desactivarte a ti mismo.');
    }

    if ($usuario->esOwner()) {
        $ownerCount = Usuario::owners()->count();
        if ($ownerCount <= 1) {
            throw new ValidationException('Debe existir al menos un owner activo en la empresa.');
        }
    }

    DB::transaction(function () use ($usuario) {
        $usuario->update(['activo' => false]);
        $usuario->tokens()->delete();
        AuditLog::registrar('usuario_desactivado', [
            'tabla_afectada' => 'usuarios',
            'registro_id'    => $usuario->id,
        ]);
    });

    return $usuario;
}
```

---

## 6. Jobs

### `UpgradePlanJob.php`

```php
class UpgradePlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [0, 120, 600]; // 0s, 2min, 10min

    public function __construct(
        private string $suscripcionId,
        private string $planId,
        private string $culqiToken,
        private string $usuarioId,
        private string $jobId,
    ) {}

    public function handle(): void
    {
        $suscripcion = Suscripcion::withoutGlobalScope('empresa')->findOrFail($this->suscripcionId);
        $plan        = Plan::findOrFail($this->planId);
        $usuario     = Usuario::withoutGlobalScope('empresa')->findOrFail($this->usuarioId);

        Culqi::setApiKey(config('services.culqi.api_key'));

        $monto  = $suscripcion->calcularMontoProrrateo($plan);
        $charge = Culqi::$charge->create([
            'amount'        => (int) ($monto * 100),
            'currency_code' => 'PEN',
            'source_id'     => $this->culqiToken,
            'email'         => $usuario->email,
        ]);

        if (isset($charge->object) && $charge->object === 'error') {
            throw new \RuntimeException($charge->merchant_message);
        }

        DB::transaction(function () use ($suscripcion, $plan, $usuario) {
            $suscripcion->update([
                'plan_id'             => $plan->id,
                'estado'              => 'activa',
                'fecha_vencimiento'   => today()->addMonth(),
                'fecha_proximo_cobro' => today()->addMonth(),
            ]);

            $usuario->tokens()->delete();
            // Nota: el nuevo JWT se emite en el próximo GET /api/me

            AuditLog::create([
                'empresa_id' => $suscripcion->empresa_id,
                'usuario_id' => $this->usuarioId,
                'accion'     => 'plan_upgrade',
                'ip'         => 'background-job',
                'created_at' => now(),
            ]);

            Mail::to($usuario->email)->queue(new UpgradePlanMail($usuario, $plan));
        });
    }

    public function failed(\Throwable $e): void
    {
        $usuario = Usuario::withoutGlobalScope('empresa')->find($this->usuarioId);

        AuditLog::create([
            'empresa_id' => Suscripcion::withoutGlobalScope('empresa')->find($this->suscripcionId)?->empresa_id,
            'usuario_id' => $this->usuarioId,
            'accion'     => 'plan_upgrade_failed',
            'ip'         => 'background-job',
            'created_at' => now(),
        ]);

        if ($usuario) {
            Mail::to($usuario->email)->queue(new UpgradePlanFallidoMail($usuario));
        }
    }
}
```

---

## 7. Events y Listeners

```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    EmpresaRegistrada::class => [
        EnviarEmailBienvenida::class,  // queued
    ],
    SuscripcionVencida::class => [
        EnviarEmailTrialVencido::class,
        BloquearEscritura::class,
    ],
    PlanActualizado::class => [
        RenovarJWT::class,
        EnviarEmailConfirmacion::class,
    ],
];
```

**`EmpresaRegistrada.php`**
```php
class EmpresaRegistrada
{
    public function __construct(
        public readonly Empresa $empresa,
        public readonly Usuario $owner,
        public readonly Suscripcion $suscripcion,
    ) {}
}
```

**Scheduled Jobs** (para transiciones de estado automáticas):
```php
// backend/routes/console.php — Laravel 11 (no existe Kernel.php)
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ProcesarSuscripcionesVencidasJob)->daily();
// → trial sin pago al día 30 → estado = vencida
// → vencida sin pago al día 7 → estado = cancelada

Schedule::job(new EnviarRecordatoriosTrialJob)->daily();
// → día 25: TrialVencimientoMail (5 días)
// → día 28: TrialVencimientoMail (2 días)

Schedule::job(new ProcessMonthlyChargesJob)->daily();
// → suscripciones activas con fecha_proximo_cobro = today → cobrar
// → aplica downgrade pendiente si corresponde
```

---

## 8. Mailables

Todos implementan `ShouldQueue` y extienden `Mailable`.

```php
// BienvenidaMail — tras registro exitoso
class BienvenidaMail extends Mailable implements ShouldQueue
{
    public function __construct(
        public Usuario $usuario,
        public Empresa $empresa,
        public Plan $plan,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Bienvenido a OperaAI 🎉');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.bienvenida', with: [
            'nombre'         => $this->usuario->nombre,
            'empresa'        => $this->empresa->nombre_comercial,
            'plan'           => $this->plan->nombre_display,
            'vencimiento'    => now()->addDays(30)->format('d/m/Y'),
            'dashboard_url'  => config('app.frontend_url') . '/dashboard',
        ]);
    }
}
```

| Mailable | Trigger | Asunto |
|----------|---------|--------|
| `BienvenidaMail` | Registro exitoso | Bienvenido a OperaAI 🎉 |
| `InvitacionUsuarioMail` | `POST /api/usuarios/invite` | Te invitaron a OperaAI |
| `RecuperarPasswordMail` | `POST /api/auth/recuperar-password` | Recupera tu contraseña |
| `TrialVencimientoMail` | Día 25 y 28 del trial | Tu trial vence en X días |
| `TrialVencidoMail` | Día 30 sin pago | Tu trial ha vencido |
| `UpgradePlanMail` | Upgrade exitoso (inmediato o job) | Plan actualizado ✅ |
| `UpgradePlanFallidoMail` | Job failed() tras 3 reintentos | Problema con tu pago |
| `PagoFallidoMail` | Cobro mensual fallido | Problema con tu pago mensual |

---

## 9. Slices Frontend

### `shared/lib/api.ts` — Axios + interceptor 401

```typescript
import axios from 'axios'
import { useAuthStore } from '@/shared/stores/auth.store'

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL,
  withCredentials: true, // envía httpOnly cookies automáticamente
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// Inyectar access token en cada request
api.interceptors.request.use((config) => {
  const token = useAuthStore.getState().accessToken
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Manejar 401 → refresh automático
let isRefreshing = false
let refreshQueue: Array<(token: string) => void> = []

api.interceptors.response.use(
  (res) => res,
  async (error) => {
    const original = error.config

    if (error.response?.status === 401 && !original._retry) {
      if (isRefreshing) {
        return new Promise((resolve) => {
          refreshQueue.push((token) => {
            original.headers.Authorization = `Bearer ${token}`
            resolve(api(original))
          })
        })
      }

      original._retry = true
      isRefreshing = true

      try {
        const { data } = await axios.post(
          `${process.env.NEXT_PUBLIC_API_URL}/auth/refresh`,
          {},
          { withCredentials: true }
        )
        const newToken = data.data.access_token
        useAuthStore.getState().setAccessToken(newToken)
        refreshQueue.forEach((cb) => cb(newToken))
        refreshQueue = []
        original.headers.Authorization = `Bearer ${newToken}`
        return api(original)
      } catch {
        useAuthStore.getState().logout()
        window.location.href = '/login'
      } finally {
        isRefreshing = false
      }
    }

    return Promise.reject(error)
  }
)
```

### `shared/stores/auth.store.ts`

```typescript
interface AuthState {
  accessToken: string | null
  user: UserPayload | null
  setAccessToken: (token: string) => void
  setUser: (user: UserPayload) => void
  logout: () => void
}

export const useAuthStore = create<AuthState>((set) => ({
  accessToken: null,
  user: null,
  setAccessToken: (token) => set({ accessToken: token }),
  setUser: (user) => set({ user }),
  logout: () => set({ accessToken: null, user: null }),
}))
```

### Slice: `core/auth/login`

**`login.schema.ts`**
```typescript
export const loginSchema = z.object({
  email: z.string().email('Email inválido'),
  password: z.string().min(1, 'Ingresa tu contraseña'),
})
export type LoginForm = z.infer<typeof loginSchema>
```

**`login.api.ts`**
```typescript
export const loginApi = async (data: LoginForm) => {
  const res = await api.post('/auth/login', data)
  return res.data.data
}
```

**`use-login.ts`**
```typescript
export function useLogin() {
  const router = useRouter()
  const { setAccessToken, setUser } = useAuthStore()

  return useMutation({
    mutationFn: loginApi,
    onSuccess: (data) => {
      setAccessToken(data.access_token)
      setUser(data.user)
      router.push('/dashboard')
    },
    onError: (error: AxiosError<ApiError>) => {
      // El error se muestra en el formulario vía react-hook-form
    },
  })
}
```

**`LoginForm.tsx`**
```tsx
export function LoginForm() {
  const { mutate: login, isPending, error } = useLogin()
  const form = useForm<LoginForm>({ resolver: zodResolver(loginSchema) })

  return (
    <form onSubmit={form.handleSubmit((data) => login(data))}>
      <Input {...form.register('email')} label="Email" type="email" />
      <PasswordInput {...form.register('password')} label="Contraseña" />
      {error && <Alert variant="error">{getApiError(error)}</Alert>}
      <Button type="submit" loading={isPending}>Ingresar</Button>
    </form>
  )
}
```

### Slice: `core/suscripcion/upgrade-plan`

**`upgrade-plan.schema.ts`**
```typescript
export const upgradePlanSchema = z.object({
  plan_id: z.string().uuid(),
  culqi_token: z.string().min(1),
})
```

**`use-upgrade-plan.ts`**
```typescript
export function useUpgradePlan() {
  const queryClient = useQueryClient()
  const { setAccessToken, setUser } = useAuthStore()

  return useMutation({
    mutationFn: async (data: { plan_id: string; culqi_token: string }) => {
      const res = await api.post('/suscripcion/upgrade', data)
      return res.data.data
    },
    onSuccess: (data) => {
      if (data.access_token) {
        setAccessToken(data.access_token)
      }
      queryClient.invalidateQueries({ queryKey: ['suscripcion'] })
      queryClient.invalidateQueries({ queryKey: ['me'] })
      toast.success('¡Plan actualizado! Ya tienes acceso a los nuevos módulos')
    },
    onError: (error: AxiosError<ApiError>) => {
      if (error.response?.status === 402) {
        toast.error(error.response.data.message)
      }
    },
  })
}
```

### `app/middleware.ts` — Edge Middleware (auth guard)

```typescript
import { NextRequest, NextResponse } from 'next/server'

const PUBLIC_PATHS = [
  '/login', '/register', '/recuperar-password',
  '/reset-password', '/activar',
]

export function middleware(req: NextRequest) {
  const hasSession = req.cookies.get('has_session')?.value === '1'
  const path = req.nextUrl.pathname
  const isPublic = PUBLIC_PATHS.some((p) => path.startsWith(p))

  if (!hasSession && !isPublic) {
    return NextResponse.redirect(new URL('/login', req.url))
  }

  if (hasSession && path === '/login') {
    return NextResponse.redirect(new URL('/dashboard', req.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!api|_next/static|_next/image|favicon.ico).*)'],
}
```

---

## 10. Rutas (routes/api.php)

```php
// Públicas — sin autenticación
Route::prefix('auth')->group(function () {
    Route::post('register', Register\RegisterController::class)
        ->middleware('throttle:register');

    Route::post('login', Login\LoginController::class)
        ->middleware('throttle:login');

    Route::post('refresh', RefreshToken\RefreshTokenController::class);

    Route::post('recuperar-password', RecuperarPassword\RecuperarPasswordController::class);

    Route::post('reset-password', RecuperarPassword\ResetPasswordController::class);
});

Route::get('planes', Planes\GetPlanesController::class);

Route::post('usuarios/activar', Usuario\ActivarCuenta\ActivarCuentaController::class);

// Protegidas — requieren autenticación
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    // Auth
    Route::post('auth/logout', Logout\LogoutController::class);

    // Perfil
    Route::get('me',  Me\GetProfileController::class);
    Route::put('me',  Me\UpdateProfileController::class);

    // Empresa — owner y admin; suscripción activa requerida para escritura
    Route::middleware('suscripcion.activa')->group(function () {
        Route::get('empresa',         Empresa\GetEmpresa\GetEmpresaController::class);
        Route::put('empresa',         Empresa\UpdateEmpresa\UpdateEmpresaController::class)
             ->middleware('role:owner,admin');
        Route::post('empresa/logo',   Empresa\UploadLogo\UploadLogoController::class)
             ->middleware('role:owner,admin');

        // Usuarios
        Route::get('usuarios',                    Usuario\ListarUsuarios\ListarUsuariosController::class);
        Route::post('usuarios/invite',            Usuario\InviteUsuario\InviteUsuarioController::class)
             ->middleware('role:owner,admin');
        Route::put('usuarios/{id}/rol',           Usuario\ActualizarRol\ActualizarRolController::class)
             ->middleware('role:owner,admin');
        Route::put('usuarios/{id}/desactivar',    Usuario\DesactivarUsuario\DesactivarUsuarioController::class)
             ->middleware('role:owner,admin');
    });

    // Suscripción — solo owner
    // upgrade siempre disponible (incluso en estado vencida)
    Route::get('suscripcion',              Suscripcion\GetSuscripcion\GetSuscripcionController::class);
    Route::post('suscripcion/upgrade',     Suscripcion\UpgradePlan\UpgradePlanController::class)
         ->middleware('role:owner');
    Route::post('suscripcion/downgrade',   Suscripcion\DowngradePlan\DowngradePlanController::class)
         ->middleware(['role:owner', 'suscripcion.activa']);
});
```

**Registrar middleware en `bootstrap/app.php`** (Laravel 11):
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tenant'           => TenantMiddleware::class,
        'suscripcion.activa' => SuscripcionActivaMiddleware::class,
        'check.plan'       => CheckPlanMiddleware::class,
        'audit'            => AuditLogMiddleware::class,
        'role'             => CheckRoleMiddleware::class,
    ]);

    $middleware->throttleWithRedis();
})
```

**RateLimiters en `AppServiceProvider`**:
```php
RateLimiter::for('login',    fn($req) => Limit::perMinutes(15, 5)->by($req->ip()));
RateLimiter::for('register', fn($req) => Limit::perHour(3)->by($req->ip()));
```

---

## 11. Tests (estructura y casos)

### Convenciones

- Un archivo de test por slice
- Cada test usa `RefreshDatabase` + `WithFaker`
- Factories para: `EmpresaFactory`, `UsuarioFactory`, `PlanFactory`, `SuscripcionFactory`
- Helper `actingAsOwner()`: crea empresa + suscripción + token para el test

```php
// tests/Feature/Core/Helpers/AuthHelper.php
trait AuthHelper
{
    protected function actingAsOwner(array $planData = []): array
    {
        $plan        = Plan::factory()->create(array_merge(['nombre' => 'pyme'], $planData));
        $empresa     = Empresa::factory()->create();
        $suscripcion = Suscripcion::factory()->create([
            'empresa_id'      => $empresa->id,
            'plan_id'         => $plan->id,
            'estado'          => 'trial',
            'fecha_vencimiento' => today()->addDays(30),
        ]);
        $owner = Usuario::factory()->create([
            'empresa_id' => $empresa->id,
            'rol'        => 'owner',
            'activo'     => true,
        ]);
        $token = $owner->createToken('access')->plainTextToken;
        return [$owner, $empresa, $suscripcion, $plan, $token];
    }
}
```

### `RegisterTest.php`

```php
// ✅ Happy path
it('registers empresa, owner and trial suscripcion', function () {
    $plan = Plan::factory()->create(['nombre' => 'pyme']);

    $response = $this->postJson('/api/auth/register', [
        'plan_id' => $plan->id,
        'empresa' => [...],
        'owner'   => [...],
    ]);

    $response->assertStatus(201)
             ->assertJsonPath('data.user.rol', 'owner')
             ->assertJsonPath('data.user.suscripcion.estado', 'trial')
             ->assertCookie('refresh_token');

    $this->assertDatabaseHas('empresas', ['ruc' => '20123456789']);
    $this->assertDatabaseHas('suscripciones', ['estado' => 'trial']);
    $this->assertDatabaseHas('audit_logs', ['accion' => 'register']);
    Mail::assertQueued(BienvenidaMail::class);
});

// ❌ RUC duplicado
it('rejects duplicate RUC', function () {
    Empresa::factory()->create(['ruc' => '20123456789']);

    $this->postJson('/api/auth/register', ['empresa' => ['ruc' => '20123456789'], ...])
         ->assertStatus(422)
         ->assertJsonPath('errors.empresa\.ruc.0', 'Ya existe una empresa con este RUC.');
});

// ❌ Email duplicado
it('rejects duplicate email', function () { ... });

// ❌ RUC con 10 dígitos
it('rejects RUC with wrong length', function () { ... });

// ❌ Contraseñas no coinciden
it('rejects mismatched passwords', function () { ... });
```

### `LoginTest.php`

```php
it('returns tokens on valid credentials', function () { ... });
it('returns 401 on wrong password', function () { ... });
it('returns 401 on inactive user', function () { ... });
it('rate limits after 5 failed attempts', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'x@x.com', 'password' => 'wrong']);
    }
    $this->postJson('/api/auth/login', [...])
         ->assertStatus(429);
});
it('updates last_login on success', function () { ... });
```

### `UpgradePlanTest.php`

```php
it('upgrades plan and returns new tokens when Culqi succeeds', function () {
    Http::fake(['culqi.com/*' => Http::response(['object' => 'charge', 'id' => 'chr_...', 'amount' => 17000])]);

    [$owner, , $sus, $actual, $token] = $this->actingAsOwner(['nombre' => 'pyme']);
    $enterprise = Plan::factory()->create(['nombre' => 'enterprise', 'precio_mensual' => 299]);

    $res = $this->withToken($token)
                ->postJson('/api/suscripcion/upgrade', ['plan_id' => $enterprise->id, 'culqi_token' => 'tkn_test']);

    $res->assertOk()
        ->assertJsonPath('data.suscripcion.plan', 'enterprise')
        ->assertJsonStructure(['data' => ['access_token']]);

    $this->assertDatabaseHas('suscripciones', ['plan_id' => $enterprise->id, 'estado' => 'activa']);
    $this->assertDatabaseHas('audit_logs', ['accion' => 'plan_upgrade']);
    Mail::assertQueued(UpgradePlanMail::class);
});

it('queues job on Culqi timeout', function () {
    Http::fake(['culqi.com/*' => Http::response(null, 504)]);
    Queue::fake();

    [..., $token] = $this->actingAsOwner();
    $enterprise = Plan::factory()->create(['precio_mensual' => 299]);

    $this->withToken($token)
         ->postJson('/api/suscripcion/upgrade', ['plan_id' => $enterprise->id, 'culqi_token' => 'tkn_test'])
         ->assertStatus(200)
         ->assertJsonPath('data.estado', 'procesando');

    Queue::assertPushed(UpgradePlanJob::class);
    $this->assertDatabaseHas('audit_logs', ['accion' => 'plan_upgrade_queued']);
});

it('returns 402 on card rejected', function () { ... });
it('rejects downgrade attempt via upgrade endpoint', function () { ... });
```

### `TenantIsolationTest.php`

```php
it('empresa A cannot see empresa B data', function () {
    // Empresa A
    [$ownerA, $empresaA, , , $tokenA] = $this->actingAsOwner();

    // Empresa B con datos propios
    [$ownerB, $empresaB] = $this->actingAsOwner();
    Usuario::factory()->create(['empresa_id' => $empresaB->id]);

    // Owner A consulta usuarios — solo debe ver los suyos
    $res = $this->withToken($tokenA)->getJson('/api/usuarios');
    $res->assertOk();

    $ids = collect($res->json('data.activos'))->pluck('id');
    $this->assertTrue($ids->contains($ownerA->id));
    $this->assertFalse($ids->contains($ownerB->id));
});

it('empresa A cannot access empresa B empresa data', function () { ... });
it('empresa A cannot modify empresa B usuarios', function () { ... });
```

### Demás tests (estructura por slice)

| Test | Happy Path | Errores |
|------|-----------|---------|
| `LogoutTest` | tokens eliminados, cookies borradas, audit log | 401 sin token |
| `RefreshTokenTest` | nuevo access token, rotación refresh | cookie ausente, token expirado |
| `RecuperarPasswordTest` | email enviado si existe, 200 si no existe | link expirado, ya usado |
| `GetEmpresaTest` | retorna datos de empresa del tenant | 401 sin auth |
| `UpdateEmpresaTest` | campos actualizados, RUC intacto | 403 si empleado, 422 campos inválidos |
| `UploadLogoTest` | URL retornada, R2 llamado, logo anterior borrado | >2MB, formato inválido, 403 si empleado |
| `GetSuscripcionTest` | datos del plan y estado | — |
| `DowngradePlanTest` | programado para siguiente período | mismo plan, no es downgrade |
| `GetProfileTest` | datos usuario + empresa + suscripcion | — |
| `UpdateProfileTest` | nombre actualizado; password cambiado invalida tokens | password_actual incorrecto, nueva = actual |
| `ListarUsuariosTest` | activos + invitaciones pendientes | solo de la propia empresa |
| `InviteUsuarioTest` | invitación creada, email enviado | límite de plan, email duplicado, invitación pendiente |
| `ActivarCuentaTest` | usuario creado, token usado | token expirado, token ya usado |
| `ActualizarRolTest` | rol actualizado, audit log | auto-cambio, admin→owner |
| `DesactivarUsuarioTest` | activo=false, tokens eliminados | único owner, auto-desactivación |