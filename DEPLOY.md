# Guía de Despliegue

## Arquitectura

| Componente | Plataforma | Trigger |
|------------|-----------|---------|
| Frontend   | Vercel    | PR merged a `main` con cambios en `frontend/` |
| Backend    | Railway   | PR merged a `main` con cambios en `backend/` |
| CI (tests) | GitHub Actions | Push o PR a `main` |

---

## Secrets de GitHub Actions

Ve a **GitHub → tu repositorio → Settings → Secrets and variables → Actions → New repository secret** y agrega cada uno.

---

### `VERCEL_TOKEN`

Token personal de la CLI de Vercel. Lo usa el workflow para autenticarse y hacer deploy.

**Pasos:**
1. Ve a [vercel.com/account/tokens](https://vercel.com/account/tokens)
2. Clic en **Create Token**
3. Ponle nombre (ej. `github-actions`) y selecciona scope **Full Account**
4. Copia el token generado — solo se muestra una vez

---

### `VERCEL_ORG_ID`

ID de tu equipo u organización dentro de Vercel.

**Pasos:**
1. En tu terminal, dentro de `frontend/`, ejecuta:
   ```bash
   vercel link
   ```
2. Abre el archivo generado `frontend/.vercel/project.json`
3. Copia el valor del campo `"orgId"`

Alternativa sin CLI:
- Vercel Dashboard → clic en tu equipo/cuenta (esquina superior izquierda) → **Settings** → **General** → copia el **Team ID**

---

### `VERCEL_PROJECT_ID`

ID del proyecto específico dentro de Vercel.

**Pasos:**
1. Mismo archivo `frontend/.vercel/project.json` del paso anterior
2. Copia el valor del campo `"projectId"`

Alternativa sin CLI:
- Vercel Dashboard → entra al proyecto → **Settings** → **General** → copia el **Project ID**

---

### `RAILWAY_TOKEN`

Token personal de la CLI de Railway. Lo usa el workflow para autenticarse y hacer deploy.

**Pasos:**
1. Ve a [railway.app/account/tokens](https://railway.app/account/tokens)
   - O en Railway Dashboard → clic en tu avatar (esquina superior derecha) → **Account Settings** → **Tokens**
2. Clic en **New Token**
3. Ponle nombre (ej. `github-actions`)
4. Copia el token generado — solo se muestra una vez

---

### `RAILWAY_SERVICE_ID`

ID del servicio de tu backend dentro de Railway. Necesario para apuntar el deploy al servicio correcto.

**Pasos:**
1. Entra a [railway.app](https://railway.app) y abre tu proyecto
2. Haz clic en el servicio del **backend** (no en PostgreSQL ni Redis)
3. Ve a la pestaña **Settings**
4. En la sección **General**, busca **Service ID**
5. Copia el UUID (formato: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`)

---

### `NEXT_PUBLIC_API_URL`

URL pública del backend desplegado en Railway. El frontend la usa para hacer llamadas a la API.

**Pasos:**
1. Entra a Railway Dashboard → abre tu proyecto → clic en el servicio backend
2. Ve a la pestaña **Settings** → sección **Networking** → **Public Networking**
3. Si no tiene dominio, clic en **Generate Domain** para crear uno automático
4. El valor debe incluir `/api` al final:
   ```
   https://tu-servicio.up.railway.app/api
   ```

> Este mismo valor va también en Vercel Dashboard → tu proyecto → Settings → Environment Variables

---

### `NEXT_PUBLIC_CULQI_PUBLIC_KEY`

Clave pública de Culqi que usa el frontend para abrir el checkout de pago. Es segura exponerla en el cliente (empieza con `pk_`).

**Pasos:**
1. Ve a [app.culqi.com](https://app.culqi.com) e inicia sesión
2. En el menú izquierdo, ve a **Desarrollo** → **API Keys**
3. Copia la **Llave pública**:
   - Para pruebas: empieza con `pk_test_...`
   - Para producción: empieza con `pk_live_...`

> Usa `pk_test_` mientras estás en desarrollo/staging. Cambia a `pk_live_` solo cuando vayas a producción real.

---

## Flujo de despliegue

```
feature-branch
     │
     ▼
Pull Request → CI corre tests (PHP 8.4 + Next.js build)
     │
     ▼ (PR merged a main)
     ├── cambios en frontend/ → Deploy Frontend a Vercel
     └── cambios en backend/  → Deploy Backend a Railway + php artisan migrate
```

---

## Variables de entorno en producción

### Backend — Railway

Configura en Railway Dashboard → tu servicio → **Variables**:

| Variable | Valor | Cómo obtenerlo |
|----------|-------|----------------|
| `APP_ENV` | `production` | Valor fijo |
| `APP_KEY` | `base64:...` | Ejecuta localmente: `php artisan key:generate --show` |
| `APP_DEBUG` | `false` | Valor fijo |
| `APP_URL` | `https://tu-servicio.up.railway.app` | Railway → servicio → Settings → Networking → dominio generado |
| `DB_CONNECTION` | `pgsql` | Valor fijo |
| `DB_HOST` | `...` | Railway → plugin PostgreSQL → **Connect** → copia `Host` |
| `DB_PORT` | `5432` | Railway → plugin PostgreSQL → **Connect** → copia `Port` |
| `DB_DATABASE` | `...` | Railway → plugin PostgreSQL → **Connect** → copia `Database` |
| `DB_USERNAME` | `...` | Railway → plugin PostgreSQL → **Connect** → copia `User` |
| `DB_PASSWORD` | `...` | Railway → plugin PostgreSQL → **Connect** → copia `Password` |
| `REDIS_URL` | `redis://...` | Railway → plugin Redis → **Connect** → copia `Redis URL` |
| `SANCTUM_STATEFUL_DOMAINS` | `tu-frontend.vercel.app` | El dominio de tu proyecto en Vercel (sin `https://`) |
| `SESSION_DOMAIN` | `tu-frontend.vercel.app` | El dominio de tu proyecto en Vercel (sin `https://`) |
| `CULQI_SECRET_KEY` | `sk_live_...` | [app.culqi.com](https://app.culqi.com) → Desarrollo → API Keys → **Llave secreta** |
| `CULQI_PUBLIC_KEY` | `pk_live_...` | [app.culqi.com](https://app.culqi.com) → Desarrollo → API Keys → **Llave pública** |
| `MAIL_MAILER` | `resend` | Valor fijo |
| `MAIL_FROM_ADDRESS` | `noreply@tudominio.com` | Tu email de envío verificado en Resend |
| `RESEND_API_KEY` | `re_...` | [resend.com/api-keys](https://resend.com/api-keys) → **Create API Key** |
| `AWS_ACCESS_KEY_ID` | `...` | AWS Console → IAM → tu usuario → **Security credentials** → Access keys |
| `AWS_SECRET_ACCESS_KEY` | `...` | Mismo lugar — solo visible al crear la key |
| `AWS_DEFAULT_REGION` | `us-east-1` | La región donde creaste tu bucket S3 |
| `AWS_BUCKET` | `...` | AWS Console → S3 → nombre de tu bucket |

### Frontend — Vercel

Configura en Vercel Dashboard → tu proyecto → **Settings → Environment Variables**:

| Variable | Valor | Cómo obtenerlo |
|----------|-------|----------------|
| `NEXT_PUBLIC_API_URL` | `https://tu-servicio.up.railway.app/api` | Ver `NEXT_PUBLIC_API_URL` arriba |
| `NEXT_PUBLIC_CULQI_PUBLIC_KEY` | `pk_live_...` | Ver `NEXT_PUBLIC_CULQI_PUBLIC_KEY` arriba |

---

## Verificación rápida

```bash
# Verificar que el token de Railway funciona
railway whoami

# Verificar que el token de Vercel funciona
vercel whoami

# Ver IDs del proyecto Vercel (requiere haber ejecutado vercel link antes)
cat frontend/.vercel/project.json
```