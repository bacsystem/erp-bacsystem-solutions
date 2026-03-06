# Contratos API: Suscripción

**Prefijo**: `/api`
**Autenticación**: Bearer token requerido en todos los endpoints
**Middleware**: `auth:sanctum`, `tenant`
**Autorización**: Todos los endpoints → solo `owner`

---

## GET /api/suscripcion

Retorna la suscripción activa de la empresa.

### Request

Sin parámetros.

### Response 200

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "plan": {
      "id": "uuid",
      "nombre": "pyme",
      "nombre_display": "PYME",
      "precio_mensual": "129.00",
      "max_usuarios": 15,
      "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia"]
    },
    "estado": "trial",
    "fecha_inicio": "2026-03-05",
    "fecha_vencimiento": "2026-04-04",
    "fecha_proximo_cobro": null,
    "dias_restantes": 30,
    "culqi_subscription_id": null,
    "datos_pago": {
      "tiene_tarjeta": true,
      "card_last4": "1111",
      "card_brand": "Visa"
    }
  }
}
```

> **`datos_pago`**: Permite mostrar UI diferente en `/configuracion/plan`. Si `tiene_tarjeta=true`, mostrar la tarjeta guardada con opción de cambiar. Si `false`, mostrar el formulario Culqi completo para ingresar una nueva tarjeta.

---

## POST /api/suscripcion/upgrade

Cambia a un plan superior con pago inmediato prorrateado via Culqi.

**Nota**: Este endpoint está SIEMPRE permitido, incluso cuando la suscripción está en estado `vencida`.

### Request

```json
{
  "plan_id": "uuid-plan-enterprise",
  "culqi_token": "tkn_live_..."
}
```

**Validaciones**:
- `plan_id`: required, uuid, exists:planes,id, activo=true
- `culqi_token`: required, string — token generado por Culqi.js en el frontend

**Regla de negocio**: El `plan_id` debe ser de un plan con `precio_mensual` mayor al actual (upgrade, no downgrade ni mismo plan).

### Response 200 — Pago exitoso

```json
{
  "success": true,
  "message": "¡Plan actualizado! Ya tienes acceso a los nuevos módulos",
  "data": {
    "access_token": "4|newPlainTextToken...",
    "token_type": "Bearer",
    "expires_in": 900,
    "suscripcion": {
      "plan": "enterprise",
      "estado": "activa",
      "fecha_vencimiento": "2026-04-05",
      "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia", "rrhh"]
    },
    "cobro": {
      "monto": "170.00",
      "descripcion": "Upgrade PYME → Enterprise (17 días restantes)"
    }
  }
}
```

> El nuevo `access_token` tiene los módulos actualizados. El frontend debe reemplazar el token en Zustand.

**Set-Cookie**: `refresh_token=newToken...; HttpOnly; Secure; SameSite=Strict; Max-Age=2592000`

### Response 202 — Pago en cola (timeout Culqi)

```json
{
  "success": true,
  "message": "Estamos procesando tu pago. Te notificaremos por email cuando se confirme",
  "data": {
    "job_id": "uuid",
    "estado": "procesando"
  }
}
```

**Side effects según resultado**:

**Pago exitoso (inmediato o desde job)**:
1. Actualiza `suscripciones.plan_id` y `estado = activa` (si estaba vencida)
2. Actualiza `suscripciones.fecha_vencimiento`
3. Emite nuevos tokens Sanctum con módulos actualizados
4. Encola `UpgradePlanMail`
5. Registra en `audit_logs` (accion=plan_upgrade)

**Timeout (job encolado)**:
1. Registra en `audit_logs` (accion=plan_upgrade_queued)
2. Job reintenta: inmediato → 2 min → 10 min
3. Si éxito tras retry → igual al flujo de éxito
4. Si 3 fallos → registra (accion=plan_upgrade_failed), encola `UpgradePlanFallidoMail`

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol ≠ owner | "Solo el owner puede cambiar el plan" |
| 409 | Ya hay un upgrade en proceso (job en cola) | "Ya hay un pago en proceso para tu cuenta. Espera la confirmación por email antes de intentar nuevamente" |
| 422 | Plan no es upgrade | "El plan seleccionado no es superior al actual" |
| 422 | `culqi_token` inválido | "Token de pago inválido. Intenta nuevamente" |
| 402 | Tarjeta rechazada | "Tu tarjeta fue rechazada. Intenta con otra tarjeta" |

> **409 — implementación**: Verificar en `audit_logs` si existe `accion=plan_upgrade_queued` en las últimas 2 horas sin un `plan_upgrade` o `plan_upgrade_failed` posterior para el mismo `empresa_id`.

---

## POST /api/suscripcion/downgrade

Programa el cambio a un plan inferior para el inicio del siguiente período de facturación.

### Request

```json
{
  "plan_id": "uuid-plan-starter"
}
```

**Validaciones**:
- `plan_id`: required, uuid, exists:planes,id, activo=true

**Regla de negocio**: El `plan_id` debe ser de un plan con `precio_mensual` menor al actual.

### Response 200

```json
{
  "success": true,
  "message": "Cambio de plan programado. Tus módulos actuales estarán disponibles hasta el 04/04/2026",
  "data": {
    "plan_actual": "pyme",
    "plan_nuevo": "starter",
    "efectivo_desde": "2026-04-05",
    "modulos_que_perdera": ["inventario", "crm", "finanzas", "ia"],
    "nuevo_max_usuarios": 3
  }
}
```

> El cambio NO es inmediato. Los módulos actuales siguen activos hasta `fecha_vencimiento`.

**Side effects**:
1. Actualiza `suscripciones.downgrade_plan_id = plan_id` (campo pendiente de agregar al data model — ver nota)
2. Registra en `audit_logs` (accion=plan_downgrade)
3. En `fecha_proximo_cobro`, el job aplica el downgrade y cobra con el nuevo precio

> **Nota**: El data model debe agregar el campo `suscripciones.downgrade_plan_id uuid NULL` para registrar el downgrade pendiente.

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol ≠ owner | "Solo el owner puede cambiar el plan" |
| 422 | Plan no es downgrade | "El plan seleccionado no es inferior al actual" |
| 422 | Mismo plan | "Ya estás en este plan" |
| 422 | Suscripción cancelada | "No puedes cambiar el plan de una suscripción cancelada" |
