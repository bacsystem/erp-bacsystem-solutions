# Contratos API: Usuarios

**Prefijo**: `/api`
**Autenticación**: Bearer token requerido en todos los endpoints
**Middleware**: `auth:sanctum`, `tenant`, `suscripcion.activa`

---

## GET /api/usuarios

Lista todos los usuarios de la empresa del usuario autenticado.

**Roles permitidos**: todos (owner, admin, empleado, contador)

### Request

Sin parámetros. (La empresa_id viene del JWT — no del request.)

### Response 200

```json
{
  "success": true,
  "data": {
    "activos": [
      {
        "id": "uuid",
        "nombre": "Juan García",
        "email": "juan@garcia.com",
        "rol": "owner",
        "activo": true,
        "last_login": "2026-03-05T10:30:00Z"
      },
      {
        "id": "uuid",
        "nombre": "María López",
        "email": "maria@garcia.com",
        "rol": "admin",
        "activo": true,
        "last_login": "2026-03-04T15:20:00Z"
      }
    ],
    "invitaciones_pendientes": [
      {
        "id": "uuid",
        "email": "carlos@garcia.com",
        "rol": "empleado",
        "invitado_por": "Juan García",
        "expires_at": "2026-03-07T10:00:00Z",
        "created_at": "2026-03-05T10:00:00Z"
      }
    ]
  }
}
```

**Regla**: Solo usuarios de `empresa_id = auth()->user()->empresa_id`. El BaseModel + RLS garantizan esto.

---

## POST /api/usuarios/invite

Invita un nuevo usuario por email. El invitado recibirá un link de activación válido 48 horas.

**Roles permitidos**: `owner`, `admin`

### Request

```json
{
  "email": "carlos@garcia.com",
  "rol": "empleado"
}
```

**Validaciones**:
- `email`: required, email, max:255
- `rol`: required, in:admin,empleado,contador (no puede invitar como `owner`)

### Response 201

```json
{
  "success": true,
  "message": "Invitación enviada a carlos@garcia.com",
  "data": {
    "id": "uuid",
    "email": "carlos@garcia.com",
    "rol": "empleado",
    "expires_at": "2026-03-07T10:00:00Z"
  }
}
```

**Side effects**:
1. Crea registro en `invitaciones_usuario` (token=Str::random(64), expires_at=now+48h)
2. Encola `InvitacionUsuarioMail` con link: `{APP_URL}/activar?token={token}`
3. Registra en `audit_logs` (accion=usuario_invitado)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol sin permiso (empleado, contador) | "No tienes permiso para invitar usuarios" |
| 422 | Email ya es usuario activo en la empresa | "Este email ya es parte de tu equipo" |
| 422 | Invitación pendiente para el mismo email | "Ya hay una invitación pendiente para este email" |
| 422 | Límite de usuarios del plan alcanzado | "Tu plan permite máximo {N} usuarios. Mejora tu plan para agregar más" |
| 422 | Intentar invitar con rol `owner` | "No puedes asignar el rol owner al invitar usuarios" |

**Nota sobre límite de usuarios**: Se cuentan los `usuarios.activo = true` + `invitaciones_usuario` no expiradas ni usadas. Si `max_usuarios = null` (Enterprise), no hay límite.

---

## PUT /api/usuarios/{id}/rol

Cambia el rol de un usuario de la empresa.

**Roles permitidos**: `owner`, `admin`

### Parámetros de ruta

- `id`: uuid del usuario a modificar

### Request

```json
{
  "rol": "admin"
}
```

**Validaciones**:
- `rol`: required, in:owner,admin,empleado,contador

**Reglas de negocio**:
- Un `admin` NO puede asignar el rol `owner`
- Un usuario NO puede cambiar su propio rol
- Solo un `owner` puede promover a otro usuario a `owner`

### Response 200

```json
{
  "success": true,
  "message": "Rol actualizado",
  "data": {
    "id": "uuid",
    "nombre": "María López",
    "email": "maria@garcia.com",
    "rol": "admin"
  }
}
```

**Side effects**:
1. Registra en `audit_logs` (accion=rol_actualizado, datos_anteriores={rol_anterior}, datos_nuevos={rol_nuevo})

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol sin permiso | "No tienes permiso para cambiar roles" |
| 403 | Admin intentando asignar rol owner | "Solo el owner puede asignar el rol owner" |
| 403 | Usuario modificando su propio rol | "No puedes cambiar tu propio rol" |
| 404 | Usuario no encontrado en la empresa | "Usuario no encontrado" |
| 422 | Rol inválido | "Rol no válido" |

---

## PUT /api/usuarios/{id}/desactivar

> **Middleware**: Requiere suscripción activa o trial. Estado `vencida` → 402 (el `SuscripcionActivaMiddleware` bloquea automáticamente todos los métodos `PUT`/`POST`/`PATCH`/`DELETE`).

Desactiva un usuario. El usuario no podrá iniciar sesión. No se elimina.

**Roles permitidos**: `owner`, `admin`

### Parámetros de ruta

- `id`: uuid del usuario a desactivar

### Request

Sin body.

### Response 200

```json
{
  "success": true,
  "message": "Usuario desactivado",
  "data": {
    "id": "uuid",
    "nombre": "Carlos Pérez",
    "email": "carlos@garcia.com",
    "rol": "empleado",
    "activo": false
  }
}
```

**Side effects**:
1. Actualiza `usuarios.activo = false`
2. `$usuario->tokens()->delete()` — invalida todas las sesiones del usuario desactivado
3. Registra en `audit_logs` (accion=usuario_desactivado)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol sin permiso | "No tienes permiso para desactivar usuarios" |
| 403 | Intentar desactivarse a sí mismo | "No puedes desactivarte a ti mismo" |
| 403 | Desactivar al único owner activo | "Debe existir al menos un owner activo en la empresa" |
| 404 | Usuario no encontrado en la empresa | "Usuario no encontrado" |
| 422 | Usuario ya está inactivo | "El usuario ya está desactivado" |

---

## POST /api/usuarios/activar (link de invitación)

Activa una cuenta desde el link de invitación. El usuario crea su contraseña.

**Autenticación**: Ninguna (ruta pública)

### Request

```json
{
  "token": "64-char-invite-token",
  "nombre": "Carlos Pérez",
  "password": "mipassword123",
  "password_confirmation": "mipassword123"
}
```

**Validaciones**:
- `token`: required, string, exists:invitaciones_usuario,token
- `nombre`: required, string, min:2, max:150
- `password`: required, string, min:8, confirmed

### Response 201

```json
{
  "success": true,
  "message": "Cuenta activada. Bienvenido a tu equipo",
  "data": {
    "access_token": "5|plainTextToken...",
    "token_type": "Bearer",
    "expires_in": 900,
    "user": {
      "id": "uuid",
      "nombre": "Carlos Pérez",
      "email": "carlos@garcia.com",
      "rol": "empleado",
      "empresa": {
        "id": "uuid",
        "nombre_comercial": "García Imports"
      }
    }
  }
}
```

**Side effects**:
1. Crea `usuarios` con `empresa_id`, `email`, `rol` de la invitación
2. Marca `invitaciones_usuario.used_at = now()`
3. Crea tokens Sanctum (access + refresh cookie)
4. Registra en `audit_logs` (accion=usuario_activado)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 422 | Token no encontrado | "Esta invitación no es válida" |
| 422 | Token ya usado | "Esta invitación ya fue utilizada" |
| 422 | Token expirado (>48h) | "Esta invitación ha expirado. Pide al administrador que te invite nuevamente" |
| 422 | Email ya existe como usuario | "Este email ya tiene una cuenta registrada" |
