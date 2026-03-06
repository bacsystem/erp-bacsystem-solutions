# Research: Módulo Core / Auth

**Feature**: `001-core-auth`
**Date**: 2026-03-05
**Phase**: 0 — Outline & Research

---

## 1. Culqi PHP Integration

### Decision
Use `culqi/culqi-php` (Composer package) for charge creation. Tokenization happens client-side via Culqi.js; the PHP backend only receives the token and creates a charge.

### Rationale
Culqi is the only locally-compliant Peruvian payment gateway. The official PHP SDK wraps the REST API and handles authentication, serialization, and error mapping.

### Key Findings

**Installation**
```bash
composer require culqi/culqi-php
```

**Environment variables**
```
CULQI_API_KEY=sk_live_...        # Backend secret key
CULQI_PUBLIC_KEY=pk_live_...     # Frontend public key (Culqi.js)
CULQI_WEBHOOK_SECRET=...         # HMAC-SHA256 webhook verification
```

**Charge flow**
```
Frontend (Culqi.js)
  └─ Tokenize card data → token { id: "tkn_..." }
        ↓ (token sent to backend)
Backend (culqi/culqi-php)
  └─ Culqi::charge_create([
       'amount'        => 12900,         // centavos (S/. 129.00)
       'currency_code' => 'PEN',
       'source_id'     => 'tkn_...',
       'email'         => 'user@email.com',
       'metadata'      => ['empresa_id' => '...', 'plan' => 'pyme'],
     ])
```

**Success response**
```json
{
  "object": "charge",
  "id": "chr_live_...",
  "status": "completed",
  "amount": 12900,
  "currency_code": "PEN",
  "source": {
    "type": "card",
    "number_last4": "1111",
    "brand": "Visa"
  }
}
```

**Failure response**
```json
{
  "object": "error",
  "type": "card_error",
  "merchant_message": "La tarjeta fue rechazada.",
  "user_message": "Tu tarjeta fue rechazada, intenta con otra."
}
```

**Exception types**
- `Culqi\Exception\ValidationException` — invalid payload
- `Culqi\Exception\AuthenticationException` — bad API key
- `Culqi\Exception\HttpException` — network timeout or 5xx

**No native subscription API** — Culqi does NOT provide recurring billing. OperaAI must implement monthly charging via:
1. Store `source_id` from first successful charge (card token, not `tkn_` — must use Culqi Customer + Card API to get a reusable token)
2. Laravel Scheduler runs `ProcessMonthlyCharges` job on `fecha_proximo_cobro`
3. Job creates a new charge using the stored card reference

**Webhook verification**
```php
$signature = $request->header('Culqi-Signature');
$payload   = $request->getContent();
$computed  = hash_hmac('sha256', $payload, config('services.culqi.webhook_secret'));
if (!hash_equals($computed, $signature)) abort(401);
```

**Webhook events**
- `charge.completed` — payment confirmed
- `charge.failed` — payment rejected
- `charge.refund` — refund processed

**Proration formula for upgrade**
```
días_restantes = fecha_vencimiento - today
precio_prorrateado = (precio_nuevo - precio_actual) / 30 * días_restantes
cobro_hoy = max(precio_prorrateado, 0)  // no reembolso en downgrade
```

**Test card**: VISA `4111 1111 1111 1111`, CVV `123`, any future date

**Retry pattern for timeout (FR-013)**
- Attempt 1: immediate
- Attempt 2: 2 minutes (backoff)
- Attempt 3: 10 minutes (backoff)
- After 3 failures: audit `plan_upgrade_failed`, send failure email, leave plan unchanged
- On success: audit `plan_upgrade`, update `suscripciones.plan_id`, issue new JWT

### Alternatives Considered
- **MercadoPago**: International but not Peru-specific; less trusted by local banks
- **Stripe**: No PEN currency support; complex SUNAT compliance
- **Izipay**: Smaller market share; weaker PHP SDK

---

## 2. Laravel Sanctum — Token Strategy

### Decision
Use Sanctum Personal Access Tokens with custom expiry logic. Two tokens per session: access (15 min) and refresh (30 days, httpOnly cookie).

### Rationale
Sanctum provides opaque tokens stored in `personal_access_tokens` table with built-in `expires_at` field. The `$usuario->tokens()->delete()` method enables full session invalidation — required by FR-008.

### Key Findings

**Token issuance on login**
```php
// Access token — 15 minutes
$accessToken = $usuario->createToken('access', ['*'], now()->addMinutes(15));

// Refresh token — 30 days (stored in httpOnly cookie)
$refreshToken = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));

return response()->json([
    'access_token' => $accessToken->plainTextToken,
    'token_type'   => 'Bearer',
    'expires_in'   => 900,
    'user'         => UserResource::make($usuario),
])->cookie('refresh_token', $refreshToken->plainTextToken, 43200, '/', null, true, true);
//                                                           minutes   secure httpOnly
```

**JWT payload via token metadata**
The JWT payload described in the spec (`empresa_id`, `rol`, `plan`, `modulos[]`) is implemented as a structured response object + Zustand store — Sanctum tokens are opaque. The user payload is returned at login and refreshed via `GET /api/me`.

**Token validation**
```php
// Middleware: auth:sanctum
// Automatically checks expires_at and marks token as invalid
```

**Full session invalidation**
```php
$usuario->tokens()->delete(); // all sessions on all devices
```

**Refresh token rotation**
```php
// On POST /api/auth/refresh:
auth()->user()->tokens()->where('name', 'access')->delete(); // delete old access token
$newAccess = $usuario->createToken('access', ['*'], now()->addMinutes(15));
// Issue new refresh token (rotation)
$oldRefresh->delete();
$newRefresh = $usuario->createToken('refresh', ['refresh'], now()->addDays(30));
```

**Rate limiting** (Laravel 11 built-in)
```php
// config/sanctum.php or RouteServiceProvider
RateLimiter::for('login', fn($req) => Limit::perMinutes(15, 5)->by($req->ip()));
RateLimiter::for('register', fn($req) => Limit::perHour(3)->by($req->ip()));
```

---

## 3. PostgreSQL Row Level Security in Laravel

### Decision
Enable RLS on all tables with `empresa_id` via a dedicated migration (`000007_add_rls_policies`). Set `app.empresa_id` session variable from `TenantMiddleware`.

### Rationale
Three-layer tenant isolation (BaseModel + TenantMiddleware + RLS) is non-negotiable per the constitution. RLS is the database-level safety net that prevents data leakage even if application layers fail.

### Key Findings

**Enable RLS in migration**
```php
DB::statement('ALTER TABLE clientes ENABLE ROW LEVEL SECURITY');
DB::statement('ALTER TABLE clientes FORCE ROW LEVEL SECURITY'); // also applies to owner role
DB::statement("CREATE POLICY tenant_isolation ON clientes
    USING (empresa_id = current_setting('app.empresa_id', true)::uuid)");
```

**Set session variable in TenantMiddleware**
```php
public function handle($request, Closure $next)
{
    $empresaId = auth()->user()->empresa_id;
    DB::statement("SET app.empresa_id = '{$empresaId}'");
    return $next($request);
}
```

**Tables requiring RLS**: `empresas`, `suscripciones`, `usuarios`, `invitaciones_usuario`, `audit_logs`
**Tables exempt**: `planes` (global, no `empresa_id`)

**Connection pooling caveat**: With PgBouncer or Supabase connection pooling, `SET` may not persist. Use `SET LOCAL` (transaction-scoped) or run in a transaction wrapper. For Railway/Supabase initial setup, `SET` is sufficient.

---

## 4. Next.js 14 Middleware for Auth

### Decision
Use Next.js Edge Middleware (`middleware.ts` at project root) to protect `(dashboard)` routes and redirect unauthenticated users to `/login`.

### Rationale
Edge Middleware runs before rendering, enabling server-side redirect without page flash. The access token lives in Zustand (memory), not cookies — so middleware reads a secondary `session` cookie (non-httpOnly boolean flag) to determine auth state.

### Key Findings

**`middleware.ts` pattern**
```typescript
import { NextRequest, NextResponse } from 'next/server'

const PUBLIC_PATHS = ['/login', '/register', '/recuperar-password', '/reset-password']

export function middleware(req: NextRequest) {
  const hasSession = req.cookies.get('has_session')?.value === '1'
  const isPublic = PUBLIC_PATHS.some(p => req.nextUrl.pathname.startsWith(p))

  if (!hasSession && !isPublic) {
    return NextResponse.redirect(new URL('/login', req.url))
  }
  if (hasSession && req.nextUrl.pathname === '/login') {
    return NextResponse.redirect(new URL('/', req.url))
  }
  return NextResponse.next()
}

export const config = { matcher: ['/((?!api|_next|favicon.ico).*)'] }
```

**`has_session` cookie**: Set on login (non-httpOnly, readable by middleware), cleared on logout. The actual access token stays in Zustand memory.

**Axios interceptor for 401 handling**
```typescript
// shared/lib/api.ts
api.interceptors.response.use(
  (res) => res,
  async (error) => {
    if (error.response?.status === 401 && !error.config._retry) {
      error.config._retry = true
      try {
        const { data } = await axios.post('/api/auth/refresh', {}, { withCredentials: true })
        useAuthStore.getState().setAccessToken(data.access_token)
        error.config.headers['Authorization'] = `Bearer ${data.access_token}`
        return api(error.config)
      } catch {
        useAuthStore.getState().logout()
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)
```

---

## 5. Email via Resend + Laravel Mail

### Decision
Use Resend as SMTP/API provider with Laravel's built-in `Mail` facade and `Mailable` classes. Queue all emails via Laravel Queue (Redis).

### Rationale
Resend provides reliable transactional email with good deliverability and a simple API. Laravel's `Mail` facade with `ShouldQueue` keeps email sending non-blocking and retryable.

### Key Findings

**Mailable classes for Core/Auth**
- `BienvenidaMail` — after registration
- `InvitacionUsuarioMail` — invitation link (48h token)
- `RecuperarPasswordMail` — reset link (60 min token)
- `TrialVencimientoMail` — day 25 and 28 reminders
- `TrialVencidoMail` — day 30, subscription expired
- `UpgradePlanMail` — plan upgrade confirmation
- `UpgradePlanFallidoMail` — after 3 failed Culqi retries
- `PagoFallidoMail` — monthly charge failure

**Queue dispatch**
```php
Mail::to($usuario->email)->queue(new BienvenidaMail($usuario, $empresa));
```

---

## 6. Cloudflare R2 for Logo Storage

### Decision
Use `league/flysystem-aws-s3-v3` (R2 is S3-compatible) as the Laravel filesystem driver for logo uploads.

### Key Findings

**Path convention**: `logos/{empresa_id}/{timestamp}.{ext}`
**Accepted formats**: JPG, PNG only — validated via `mimes:jpg,jpeg,png` + max 2MB
**Config** (`.env`):
```
CLOUDFLARE_R2_KEY=...
CLOUDFLARE_R2_SECRET=...
CLOUDFLARE_R2_BUCKET=operaai-logos
CLOUDFLARE_R2_ENDPOINT=https://{account_id}.r2.cloudflarestorage.com
CLOUDFLARE_R2_REGION=auto
```

---

## 7. Password Reset Token Strategy

### Decision
Use a custom `password_reset_tokens` table (NOT Laravel's built-in) to allow storing `empresa_id` and tracking usage. Token validity: 60 minutes, single-use.

### Key Findings

**Why custom**: Laravel's built-in `password_reset_tokens` doesn't support single-use tracking (`used_at`) or `empresa_id` isolation. A custom implementation with `PasswordResetToken` model gives full control.

**Token generation**
```php
$token = Str::random(64); // 64-char URL-safe random string
// store: hash with bcrypt or SHA-256 for the DB, return plain text in the link
```

**Link format**: `{APP_URL}/reset-password?token={token}&email={email}`

---

## Summary of Decisions

| Topic | Decision | Package/Approach |
|-------|----------|-----------------|
| Payments | Culqi with manual recurring | `culqi/culqi-php` |
| Auth tokens | Sanctum PAT with custom expiry | `laravel/sanctum` |
| Tenant isolation | 3-layer: BaseModel + Middleware + RLS | Built-in + `DB::statement` |
| Email | Resend via Laravel Mail queued | `resend/resend-php` + Laravel Mail |
| File storage | Cloudflare R2 (S3-compatible) | `league/flysystem-aws-s3-v3` |
| Frontend auth | Next.js Edge Middleware + Zustand + Axios interceptor | Built-in |
| Password reset | Custom table with `used_at` tracking | Custom `PasswordResetToken` model |
