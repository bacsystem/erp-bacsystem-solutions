# Quickstart: Módulo Core / Auth

**Feature**: `001-core-auth`
**Date**: 2026-03-05

---

## Prerrequisitos

- PHP 8.3 + Composer
- Node.js 20+ + npm
- PostgreSQL 16
- Redis 7
- Cuenta Cloudflare R2 (o usar local storage para dev)
- Cuenta Culqi (sandbox para desarrollo)
- Cuenta Resend (o usar Mailpit para dev)

---

## Setup Backend (Laravel)

### 1. Instalar dependencias

```bash
cd backend
composer install
```

### 2. Variables de entorno

```bash
cp .env.example .env
php artisan key:generate
```

**Configurar `.env`**:

```env
APP_NAME=OperaAI
APP_URL=http://localhost:8000
APP_ENV=local
APP_KEY=base64:...  # generado con artisan key:generate

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=operaai
DB_USERNAME=postgres
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=localhost:3000
SESSION_DOMAIN=localhost

# Culqi (sandbox)
CULQI_API_KEY=sk_test_...
CULQI_PUBLIC_KEY=pk_test_...
CULQI_WEBHOOK_SECRET=...

# Cloudflare R2 (o usar 'local' para dev)
FILESYSTEM_DISK=r2
CLOUDFLARE_R2_KEY=...
CLOUDFLARE_R2_SECRET=...
CLOUDFLARE_R2_BUCKET=operaai-logos
CLOUDFLARE_R2_ENDPOINT=https://{account_id}.r2.cloudflarestorage.com
CLOUDFLARE_R2_REGION=auto

# Email (Mailpit para dev: puerto 1025)
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_FROM_ADDRESS=noreply@operaai.pe
MAIL_FROM_NAME=OperaAI

# Frontend URL (para links en emails)
FRONTEND_URL=http://localhost:3000
```

### 3. Base de datos

```bash
# Crear base de datos
createdb operaai

# Ejecutar migraciones (en orden)
php artisan migrate

# Verificar que las 7 migraciones se ejecutaron:
# ✓ 2026_03_05_000001_create_planes_table
# ✓ 2026_03_05_000002_create_empresas_table
# ✓ 2026_03_05_000003_create_suscripciones_table
# ✓ 2026_03_05_000004_create_usuarios_table
# ✓ 2026_03_05_000005_create_invitaciones_usuario_table
# ✓ 2026_03_05_000006_create_audit_logs_table
# ✓ 2026_03_05_000007_add_rls_policies
```

### 4. Seeders obligatorios

```bash
# PlanSeeder DEBE ejecutarse antes de cualquier registro
php artisan db:seed --class=PlanSeeder

# Verificar:
php artisan tinker
>>> App\Modules\Core\Models\Plan::count()  // Debe retornar 3
>>> App\Modules\Core\Models\Plan::pluck('nombre')
// => ["starter", "pyme", "enterprise"]
```

**Contenido de PlanSeeder** (referencia):

| nombre | precio_mensual | max_usuarios | modulos |
|--------|---------------|--------------|---------|
| starter | 59.00 | 3 | facturacion, clientes, productos |
| pyme | 129.00 | 15 | facturacion, clientes, productos, inventario, crm, finanzas, ia |
| enterprise | 299.00 | null | todos + rrhh |

### 5. Levantar servidor y workers

```bash
# Terminal 1: Servidor HTTP
php artisan serve  # http://localhost:8000

# Terminal 2: Queue worker (para emails, jobs de Culqi)
php artisan queue:work --queue=default --tries=3

# Terminal 3: Scheduler (para jobs de suscripción)
php artisan schedule:work
```

---

## Setup Frontend (Next.js)

### 1. Instalar dependencias

```bash
cd frontend
npm install
```

### 2. Variables de entorno

```bash
cp .env.local.example .env.local
```

**Configurar `.env.local`**:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_CULQI_PUBLIC_KEY=pk_test_...
```

### 3. Levantar servidor de desarrollo

```bash
npm run dev  # http://localhost:3000
```

### 4. Verificar TypeScript

```bash
npm run build  # Debe completar sin errores
```

---

## Verificación del Módulo

### Flujo de registro completo (happy path)

```bash
# 1. Obtener planes
curl http://localhost:8000/api/planes

# 2. Registrar empresa
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "plan_id": "<uuid-pyme>",
    "empresa": {
      "ruc": "20123456789",
      "razon_social": "Test SAC",
      "nombre_comercial": "Test",
      "direccion": "Av. Test 123",
      "regimen_tributario": "RMT"
    },
    "owner": {
      "nombre": "Test Owner",
      "email": "owner@test.com",
      "password": "password123",
      "password_confirmation": "password123"
    }
  }'

# Respuesta esperada: 201 con access_token y suscripción trial
```

### Verificación de tenant isolation

```bash
php artisan test --filter=TenantIsolationTest
```

El test debe:
1. Crear empresa A con usuario A
2. Crear empresa B con usuario B
3. Autenticarse como usuario A
4. Intentar acceder a datos de empresa B → esperar 403/404
5. Verificar que solo se retornan datos de empresa A

### Ejecutar todos los tests del módulo

```bash
php artisan test --filter=Core
# Resultado esperado: PASS — 0 failures, 0 errors
```

---

## Comandos útiles de desarrollo

```bash
# Ver rutas del módulo Core
php artisan route:list --path=api

# Limpiar cache después de cambios
php artisan cache:clear
php artisan config:clear

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Tinker para debug
php artisan tinker
>>> App\Modules\Core\Models\Usuario::with('empresa', 'suscripcion')->first()
```

---

## Culqi en modo sandbox

1. Usar la public key `pk_test_...` en el frontend
2. Usar la API key `sk_test_...` en el backend
3. Tarjeta de prueba: VISA `4111 1111 1111 1111`, CVV `123`, fecha futura
4. Para simular rechazo, usar `4000 0000 0000 0002`

Los webhooks de Culqi sandbox se pueden simular usando la dashboard de Culqi o con ngrok para exponer localhost.

---

## Troubleshooting

| Problema | Solución |
|----------|----------|
| `SQLSTATE[42501]: Insufficient privilege` | RLS policy bloqueando — verificar que `app.empresa_id` está seteado en TenantMiddleware |
| Emails no se envían | Verificar que el queue worker está corriendo: `php artisan queue:work` |
| `401 Unauthenticated` en refresh | Verificar que la cookie `refresh_token` se envía con `withCredentials: true` en Axios |
| CORS error en frontend | Verificar `SANCTUM_STATEFUL_DOMAINS=localhost:3000` en `.env` |
| Logo no sube | Verificar credenciales R2 o cambiar `FILESYSTEM_DISK=local` para dev |
