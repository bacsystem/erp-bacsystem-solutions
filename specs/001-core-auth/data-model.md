# Data Model: Módulo Core / Auth

**Feature**: `001-core-auth`
**Date**: 2026-03-05
**Source**: spec.md + clarifications session 2026-03-04/05

---

## Diagrama de relaciones

```
planes (1) ──────────────── (N) suscripciones
                                      │
empresas (1) ───────────── (N) suscripciones
empresas (1) ───────────── (N) usuarios
empresas (1) ───────────── (N) audit_logs

usuarios (1) ──────────── (N) audit_logs
usuarios (1) ──────────── (N) invitaciones_usuario
usuarios (1) ──────────── (N) password_reset_tokens (implicit via email)
```

---

## Tabla: `planes`

> Sin `empresa_id` — dato global del sistema, no pertenece a ningún tenant.

| Columna          | Tipo              | Restricciones                        |
|------------------|-------------------|--------------------------------------|
| id               | uuid              | PK                                   |
| nombre           | varchar(20)       | NOT NULL, UNIQUE — `starter\|pyme\|enterprise` |
| nombre_display   | varchar(50)       | NOT NULL — `Starter\|PYME\|Enterprise` |
| precio_mensual   | decimal(8,2)      | NOT NULL                             |
| max_usuarios     | integer           | NULL = ilimitado                     |
| modulos          | jsonb             | NOT NULL — array de identificadores de módulo |
| activo           | boolean           | NOT NULL DEFAULT true                |
| created_at       | timestamp         | NOT NULL                             |
| updated_at       | timestamp         | NOT NULL                             |

**Índices**: `nombre` (unique)

**Valores iniciales (PlanSeeder)**:

```
starter    | S/. 59.00  | max_usuarios=3  | modulos=["facturacion","clientes","productos"]
pyme       | S/. 129.00 | max_usuarios=15 | modulos=["facturacion","clientes","productos","inventario","crm","finanzas","ia"]
enterprise | S/. 299.00 | max_usuarios=null| modulos=["facturacion","clientes","productos","inventario","crm","finanzas","ia","rrhh"]
```

---

## Tabla: `empresas`

| Columna              | Tipo          | Restricciones                              |
|----------------------|---------------|--------------------------------------------|
| id                   | uuid          | PK                                         |
| ruc                  | varchar(11)   | NOT NULL, UNIQUE — exactamente 11 dígitos numéricos |
| razon_social         | varchar(200)  | NOT NULL                                   |
| nombre_comercial     | varchar(200)  | NOT NULL                                   |
| direccion            | text          | NOT NULL                                   |
| ubigeo               | varchar(6)    | NULL — código INEI de 6 dígitos            |
| logo_url             | varchar(500)  | NULL — URL en Cloudflare R2                |
| regimen_tributario   | varchar(3)    | NOT NULL — CHECK IN ('RER','RG','RMT')     |
| created_at           | timestamp     | NOT NULL                                   |
| updated_at           | timestamp     | NOT NULL                                   |

**Índices**: `ruc` (unique), `created_at`

**Reglas**:
- `ruc` es inmutable post-creación (no tiene setter en el modelo)
- `logo_url` apunta a un objeto en Cloudflare R2 bajo el path `logos/{empresa_id}/{timestamp}.{ext}`
- Solo se aceptan JPG y PNG para el logo, máximo 2MB

---

## Tabla: `suscripciones`

| Columna               | Tipo         | Restricciones                              |
|-----------------------|--------------|--------------------------------------------|
| id                    | uuid         | PK                                         |
| empresa_id            | uuid         | FK → empresas.id, NOT NULL                 |
| plan_id               | uuid         | FK → planes.id, NOT NULL                  |
| estado                | varchar(10)  | NOT NULL — CHECK IN ('trial','activa','vencida','cancelada') |
| fecha_inicio          | date         | NOT NULL                                   |
| fecha_vencimiento     | date         | NOT NULL                                   |
| fecha_proximo_cobro   | date         | NULL                                       |
| fecha_cancelacion     | date         | NULL — se llena al pasar a `cancelada`     |
| culqi_subscription_id | varchar(100) | NULL                                       |
| culqi_customer_id     | varchar(100) | NULL — ID de cliente en Culqi              |
| culqi_card_id         | varchar(100) | NULL — ID del card token guardado en Culqi |
| card_last4            | varchar(4)   | NULL — últimos 4 dígitos de la tarjeta     |
| card_brand            | varchar(20)  | NULL — marca: Visa, Mastercard, etc.       |
| created_at            | timestamp    | NOT NULL                                   |
| updated_at            | timestamp    | NOT NULL                                   |

**Índices**: `empresa_id`, `estado`, `fecha_vencimiento`

**Nota — columnas Culqi**: `culqi_customer_id` y `culqi_card_id` son necesarias para cobros mensuales recurrentes. Culqi no provee API de suscripciones recurrentes — el sistema debe guardar el card token para ejecutar cobros manuales en `fecha_proximo_cobro`. `card_last4` y `card_brand` se usan para mostrar `datos_pago` en `GET /api/suscripcion`.

**Máquina de estados**:

```
trial
  └─ Sin pago al día 30 → vencida (via SuscripcionVencimientoJob)
  └─ Con pago antes del día 30 → activa (via UpgradePlanService)

activa
  └─ Fallo de cobro mensual → vencida (via SuscripcionVencimientoJob)
  └─ Downgrade → activa con nuevo plan al inicio del siguiente período

vencida
  └─ Pago recibido → activa (via ReactivarPlanService)
  └─ Sin pago en 7 días → cancelada (via SuscripcionCancelacionJob)

cancelada
  └─ Pago recibido en /reactivar → activa (via ReactivarPlanService)
  └─ Sin acción en 90 días → datos eliminados (via PurgeTenantJob)
```

**Reglas de acceso por estado**:

| Estado    | GET | POST/PUT/PATCH/DELETE | Ruta especial |
|-----------|-----|-----------------------|---------------|
| trial     | ✅  | ✅                    | —             |
| activa    | ✅  | ✅                    | —             |
| vencida   | ✅  | ❌ → 402              | POST /api/suscripcion/upgrade siempre permitido |
| cancelada | ❌  | ❌                    | Solo /reactivar permitida |

---

## Tabla: `usuarios`

| Columna        | Tipo         | Restricciones                                            |
|----------------|--------------|----------------------------------------------------------|
| id             | uuid         | PK                                                       |
| empresa_id     | uuid         | FK → empresas.id, NOT NULL                               |
| nombre         | varchar(150) | NOT NULL                                                 |
| email          | varchar(255) | NOT NULL, UNIQUE (global)                                |
| password       | varchar(255) | NOT NULL — bcrypt cost factor 12                         |
| rol            | varchar(10)  | NOT NULL — CHECK IN ('owner','admin','empleado','contador') |
| activo         | boolean      | NOT NULL DEFAULT true                                    |
| last_login     | timestamp    | NULL                                                     |
| created_at     | timestamp    | NOT NULL                                                 |
| updated_at     | timestamp    | NOT NULL                                                 |

**Índices**: `empresa_id`, `email` (unique), `activo`

**Reglas**:
- `email` es inmutable post-creación
- `rol` solo puede ser modificado por owner o admin, nunca por el propio usuario
- Un usuario desactivado (`activo = false`) no puede autenticarse
- Debe existir al menos 1 usuario con `rol = 'owner'` y `activo = true` por empresa

---

## Tabla: `invitaciones_usuario`

> Entidad implícita en el spec (US8, FR-019). Necesaria para el flujo de invitación.

| Columna      | Tipo         | Restricciones                              |
|--------------|--------------|--------------------------------------------|
| id           | uuid         | PK                                         |
| empresa_id   | uuid         | FK → empresas.id, NOT NULL                 |
| email        | varchar(255) | NOT NULL                                   |
| rol          | varchar(10)  | NOT NULL — rol asignado al activar         |
| token        | varchar(100) | NOT NULL, UNIQUE — token de activación     |
| invitado_por | uuid         | FK → usuarios.id, NOT NULL                 |
| expires_at   | timestamp    | NOT NULL — 48 horas desde creación         |
| used_at      | timestamp    | NULL — se llena al activar                 |
| created_at   | timestamp    | NOT NULL                                   |

**Índices**: `empresa_id`, `email`, `token` (unique), `expires_at`

**Reglas**:
- Un token de un solo uso: si `used_at IS NOT NULL` → inválido
- Si `expires_at < NOW()` → inválido (link expirado)
- No puede existir una invitación pendiente para el mismo email + empresa

---

## Tabla: `audit_logs`

> Sin soft deletes — registro inmutable.

| Columna          | Tipo         | Restricciones                              |
|------------------|--------------|--------------------------------------------|
| id               | uuid         | PK                                         |
| empresa_id       | uuid         | FK → empresas.id, NOT NULL                 |
| usuario_id       | uuid         | FK → usuarios.id, NULL (acciones sin auth) |
| accion           | varchar(50)  | NOT NULL — ver catálogo de acciones abajo  |
| tabla_afectada   | varchar(50)  | NULL                                       |
| registro_id      | uuid         | NULL                                       |
| datos_anteriores | jsonb        | NULL                                       |
| datos_nuevos     | jsonb        | NULL                                       |
| ip               | varchar(45)  | NOT NULL — soporta IPv4 e IPv6             |
| created_at       | timestamp    | NOT NULL                                   |

**Índices**: `empresa_id`, `usuario_id`, `accion`, `created_at`

**Catálogo de acciones auditadas**:

```
register          — registro de nueva empresa + owner
login             — inicio de sesión exitoso
login_failed      — intento fallido (incluye intentos que desencadenan bloqueo)
logout_all        — cierre de TODAS las sesiones activas
password_changed  — cambio de contraseña
password_reset    — reset via link de email
plan_upgrade      — cambio a plan superior (exitoso)
plan_upgrade_queued — upgrade encolado por timeout Culqi
plan_upgrade_failed — upgrade fallido tras 3 reintentos Culqi
plan_downgrade    — cambio a plan inferior programado
suscripcion_reactivada — reactivación desde estado cancelada
usuario_invitado  — invitación enviada
usuario_activado  — invitado activó su cuenta
rol_actualizado   — cambio de rol de usuario
usuario_desactivado — desactivación de usuario
empresa_actualizada — cambio en datos de la empresa
logo_actualizado  — cambio de logo
```

---

## Migraciones (orden de ejecución)

```
2026_03_05_000001_create_planes_table
2026_03_05_000002_create_empresas_table
2026_03_05_000003_create_suscripciones_table
2026_03_05_000004_create_usuarios_table
2026_03_05_000005_create_invitaciones_usuario_table
2026_03_05_000006_create_audit_logs_table
2026_03_05_000007_add_rls_policies
```

> La migración `add_rls_policies` ejecuta `DB::statement()` para habilitar
> PostgreSQL Row Level Security en todas las tablas con `empresa_id`.

---

## Personal Access Tokens (Laravel Sanctum)

Laravel Sanctum usa la tabla `personal_access_tokens` (automática).
Se emiten **2 tokens por sesión**:

| Token        | Expiración | Almacenamiento           | Uso                          |
|--------------|------------|--------------------------|------------------------------|
| access_token | 15 minutos | Memoria Zustand (no localStorage) | Request Authorization header |
| refresh_token| 30 días    | httpOnly Cookie           | Renovar access_token         |

**Invalidación completa** (`$usuario->tokens()->delete()`):
- Logout estándar
- Cambio de contraseña
- Desactivación del usuario por owner/admin
- Cancelación de suscripción
