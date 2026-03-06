# Contratos API: Perfil del Usuario Autenticado

**Prefijo**: `/api`
**Autenticación**: Bearer token requerido en todos los endpoints
**Middleware**: `auth:sanctum`, `tenant`

---

## GET /api/me

Retorna el perfil completo del usuario autenticado con datos de empresa y suscripción.

### Request

Sin parámetros.

### Response 200

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "nombre": "Juan García",
    "email": "juan@garcia.com",
    "rol": "owner",
    "activo": true,
    "last_login": "2026-03-05T10:30:00Z",
    "empresa": {
      "id": "uuid",
      "ruc": "20123456789",
      "razon_social": "Importaciones García SAC",
      "nombre_comercial": "García Imports",
      "direccion": "Av. Larco 123, Miraflores",
      "ubigeo": "150101",
      "logo_url": "https://r2.example.com/logos/uuid/1234567890.png",
      "regimen_tributario": "RMT"
    },
    "suscripcion": {
      "id": "uuid",
      "plan": "pyme",
      "plan_display": "PYME",
      "estado": "trial",
      "fecha_inicio": "2026-03-05",
      "fecha_vencimiento": "2026-04-04",
      "fecha_proximo_cobro": null,
      "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia"]
    }
  }
}
```

**Nota — suscripción cancelada**: Cuando `suscripcion.estado === 'cancelada'`, el campo `suscripcion` incluye `redirect` para que el frontend pueda redirigir automáticamente tras el refresh de token:

```json
"suscripcion": {
  "plan": "pyme",
  "estado": "cancelada",
  "modulos": [],
  "redirect": "/reactivar"
}
```

> Caso de uso: el usuario tiene sesión activa y la suscripción se cancela en background (job nocturno). En el próximo refresh automático del access token, el frontend llama a `GET /api/me`, detecta `redirect`, e inicia el flujo de reactivación sin requerir un nuevo login.

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 401 | Token inválido o expirado | "No autenticado" |

---

## PUT /api/me

Actualiza el perfil del usuario autenticado. Solo permite cambiar `nombre` y `password`. Email e inmutables.

### Request

```json
{
  "nombre": "Juan Carlos García",
  "password_actual": "passwordactual123",
  "password": "nuevapassword123",
  "password_confirmation": "nuevapassword123"
}
```

**Validaciones**:
- `nombre`: sometimes, string, min:2, max:150
- `password_actual`: required_with:password, string
- `password`: sometimes, string, min:8, confirmed, different:password_actual
- `password_confirmation`: required_with:password

**Nota**: `nombre` y `password` son independientes — se puede actualizar solo el nombre sin cambiar contraseña.

### Response 200

```json
{
  "success": true,
  "message": "Perfil actualizado",
  "data": {
    "id": "uuid",
    "nombre": "Juan Carlos García",
    "email": "juan@garcia.com",
    "rol": "owner"
  }
}
```

**Side effects** (solo si `password` fue cambiado):
1. Actualiza `usuarios.password` (bcrypt cost 12)
2. `$usuario->tokens()->delete()` — invalida todas las sesiones
3. Registra en `audit_logs` (accion=password_changed)
4. El frontend debe redirigir a `/login` tras cambio de contraseña

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 401 | Token inválido | "No autenticado" |
| 422 | `password_actual` incorrecto | "La contraseña actual es incorrecta" |
| 422 | `password` igual a `password_actual` | "La nueva contraseña debe ser diferente a la actual" |
| 422 | `password` < 8 caracteres | "La contraseña debe tener al menos 8 caracteres" |
| 422 | `nombre` vacío si se provee | "El nombre no puede estar vacío" |
