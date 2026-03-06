# Contratos API: Auth

**Prefijo**: `/api/auth`
**Autenticación**: Ninguna (rutas públicas)

---

## POST /api/auth/register

Registra una nueva empresa + usuario owner + suscripción trial de 30 días.

**Rate limit**: 3 requests/hora por IP

### Request

```json
{
  "plan_id": "uuid",
  "empresa": {
    "ruc": "20123456789",
    "razon_social": "Importaciones García SAC",
    "nombre_comercial": "García Imports",
    "direccion": "Av. Larco 123, Miraflores",
    "ubigeo": "150101",
    "regimen_tributario": "RMT"
  },
  "owner": {
    "nombre": "Juan García",
    "email": "juan@garcia.com",
    "password": "mipassword123",
    "password_confirmation": "mipassword123"
  }
}
```

**Validaciones**:
- `plan_id`: required, uuid, exists:planes,id, activo=true
- `empresa.ruc`: required, string, digits:11, unique:empresas,ruc
- `empresa.razon_social`: required, string, max:200
- `empresa.nombre_comercial`: required, string, max:200
- `empresa.direccion`: required, string
- `empresa.ubigeo`: nullable, string, digits:6
- `empresa.regimen_tributario`: required, in:RER,RG,RMT
- `owner.nombre`: required, string, max:150
- `owner.email`: required, email, max:255, unique:usuarios,email
- `owner.password`: required, string, min:8, confirmed

### Response 201

```json
{
  "success": true,
  "message": "Empresa registrada exitosamente",
  "data": {
    "access_token": "1|plainTextToken...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": "uuid",
      "nombre": "Juan García",
      "email": "juan@garcia.com",
      "rol": "owner",
      "empresa": {
        "id": "uuid",
        "nombre_comercial": "García Imports",
        "ruc": "20123456789",
        "logo_url": null
      },
      "suscripcion": {
        "plan": "pyme",
        "estado": "trial",
        "fecha_vencimiento": "2026-04-04",
        "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia"]
      }
    }
  }
}
```

**Set-Cookie**: `refresh_token=...; HttpOnly; Secure; SameSite=Strict; Max-Age=2592000`

**Side effects**:
1. Crea registro en `empresas`
2. Crea registro en `suscripciones` (estado=trial, fecha_vencimiento=today+30)
3. Crea registro en `usuarios` (rol=owner, activo=true)
4. Encola `BienvenidaMail`
5. Registra en `audit_logs` (accion=register)
6. Establece cookie `has_session=1` (no httpOnly, para Next.js middleware)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 422 | RUC inválido (≠11 dígitos) | "El RUC debe tener exactamente 11 dígitos numéricos" |
| 422 | RUC ya registrado | "Ya existe una empresa con este RUC" |
| 422 | Email ya registrado | "Este email ya tiene una cuenta" |
| 422 | Contraseñas no coinciden | "Las contraseñas no coinciden" |
| 422 | Contraseña < 8 caracteres | "La contraseña debe tener al menos 8 caracteres" |
| 422 | Plan inválido/inactivo | "El plan seleccionado no está disponible" |
| 429 | Rate limit superado | "Demasiadas solicitudes. Intenta en 1 hora" |

---

## POST /api/auth/login

Inicia sesión y retorna access token + refresh token (cookie).

**Rate limit**: 5 intentos/15 minutos por IP (bloqueo temporal al superar)

### Request

```json
{
  "email": "juan@garcia.com",
  "password": "mipassword123"
}
```

**Validaciones**:
- `email`: required, email
- `password`: required, string

### Response 200

```json
{
  "success": true,
  "message": "Sesión iniciada",
  "data": {
    "access_token": "2|plainTextToken...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": "uuid",
      "nombre": "Juan García",
      "email": "juan@garcia.com",
      "rol": "owner",
      "empresa": {
        "id": "uuid",
        "nombre_comercial": "García Imports",
        "ruc": "20123456789",
        "logo_url": "https://r2.example.com/logos/uuid/1234567890.png"
      },
      "suscripcion": {
        "plan": "pyme",
        "estado": "trial",
        "fecha_vencimiento": "2026-04-04",
        "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia"]
      }
    }
  }
}
```

**Set-Cookie**: `refresh_token=...; HttpOnly; Secure; SameSite=Strict; Max-Age=2592000`
**Set-Cookie**: `has_session=1; Secure; SameSite=Strict; Max-Age=2592000`

**Side effects**:
1. Actualiza `usuarios.last_login = now()`
2. Crea 2 Sanctum tokens (access + refresh)
3. Registra en `audit_logs` (accion=login)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 401 | Credenciales incorrectas | "Credenciales incorrectas" |
| 401 | Usuario inactivo | "Tu cuenta ha sido desactivada. Contacta al administrador de tu empresa" |
| 429 | > 5 intentos fallidos en 15 min | "Demasiados intentos fallidos. Intenta nuevamente en 15 minutos" |

**Nota**: Intentos fallidos se registran en `audit_logs` (accion=login_failed).

**Nota — suscripción cancelada**: Si `suscripcion.estado === 'cancelada'`, el login retorna **200** normalmente pero el payload del usuario incluye el campo `redirect`:

```json
"suscripcion": {
  "plan": "pyme",
  "estado": "cancelada",
  "fecha_cancelacion": "2026-03-01",
  "modulos": [],
  "redirect": "/reactivar"
}
```

El frontend detecta `estado === 'cancelada'` y redirige a `/reactivar` en vez del dashboard. El token se emite con `modulos: []` para que el middleware del lado cliente bloquee el acceso a rutas protegidas.

---

## POST /api/auth/logout

**Autenticación**: Bearer token requerido

Invalida TODOS los tokens activos del usuario (todas las sesiones, todos los dispositivos).

### Request

Sin body.

### Response 200

```json
{
  "success": true,
  "message": "Sesión cerrada"
}
```

**Set-Cookie**: `refresh_token=; Max-Age=0` (elimina cookie)
**Set-Cookie**: `has_session=; Max-Age=0` (elimina cookie)

**Side effects**:
1. `$usuario->tokens()->delete()` — invalida todos los tokens
2. Registra en `audit_logs` (accion=logout_all)

---

## POST /api/auth/refresh

Renueva el access token usando el refresh token de la cookie httpOnly. Rotación: emite nuevo refresh token.

### Request

Sin body. El refresh token se lee de la cookie `refresh_token`.

### Response 200

```json
{
  "success": true,
  "data": {
    "access_token": "3|newPlainTextToken...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

**Set-Cookie**: `refresh_token=newToken...; HttpOnly; Secure; SameSite=Strict; Max-Age=2592000`
**Set-Cookie**: `has_session=1; Secure; SameSite=Strict; Max-Age=2592000`

> La cookie `has_session` también se renueva en cada refresh exitoso para evitar que expire antes que el refresh token, lo que provocaría un cierre de sesión prematuro en el frontend.

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 401 | Cookie ausente o token inválido | "Sesión expirada. Inicia sesión nuevamente" |
| 401 | Refresh token expirado (>30 días) | "Sesión expirada. Inicia sesión nuevamente" |

---

## POST /api/auth/recuperar-password

Envía link de recuperación de contraseña (válido 60 minutos).

**Rate limit**: 3 requests/hora por IP

### Request

```json
{
  "email": "juan@garcia.com"
}
```

**Validaciones**:
- `email`: required, email

### Response 200

```json
{
  "success": true,
  "message": "Si el email existe, recibirás un link de recuperación en los próximos minutos"
}
```

> Siempre retorna 200 aunque el email no exista (por seguridad — no confirmar existencia).

**Side effects** (solo si el email existe):
1. Crea `password_reset_tokens` (token SHA-256, expires_at=now+60min)
2. Encola `RecuperarPasswordMail`
3. Registra en `audit_logs` (accion=password_reset)

---

## POST /api/auth/reset-password

Cambia la contraseña usando el token del link de recuperación.

### Request

```json
{
  "token": "64-char-random-string",
  "email": "juan@garcia.com",
  "password": "nuevapassword123",
  "password_confirmation": "nuevapassword123"
}
```

**Validaciones**:
- `token`: required, string
- `email`: required, email
- `password`: required, string, min:8, confirmed

### Response 200

```json
{
  "success": true,
  "message": "Contraseña actualizada exitosamente. Inicia sesión con tu nueva contraseña"
}
```

**Side effects**:
1. Actualiza `usuarios.password` (bcrypt cost 12)
2. Marca `password_reset_tokens.used_at = now()`
3. `$usuario->tokens()->delete()` — invalida todos los tokens activos
4. Registra en `audit_logs` (accion=password_changed)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 422 | Token inválido o no encontrado | "Este link no es válido" |
| 422 | Token expirado (>60 min) | "Este link ha expirado. Solicita uno nuevo" |
| 422 | Token ya usado (`used_at IS NOT NULL`) | "Este link ya fue utilizado" |
| 422 | Contraseña < 8 caracteres | "La contraseña debe tener al menos 8 caracteres" |
| 422 | Contraseñas no coinciden | "Las contraseñas no coinciden" |
