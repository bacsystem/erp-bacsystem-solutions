# Data Model: Módulo 0 — Superadmin OperaAI

**Feature**: `000-superadmin`
**Date**: 2026-03-06
**Source**: spec.md

---

## Diagrama de relaciones

```
superadmins (1) ──────────── (N) impersonation_logs
superadmins (1) ──────────── (N) descuentos_tenant
superadmins (1) ──────────── (N) audit_logs (via superadmin_id, nullable)

empresas (1) ─────────────── (N) impersonation_logs
empresas (1) ─────────────── (N) descuentos_tenant
```

> `superadmins` NO tiene relación con `empresas`, `usuarios` ni `suscripciones`
> directamente. Accede a ellas sin restricción de RLS (bypasa el filtro `empresa_id`).

---

## Tabla: `superadmins`

> Sin `empresa_id` — no pertenece a ningún tenant. Tabla global del sistema.

| Columna      | Tipo          | Restricciones                              |
|--------------|---------------|--------------------------------------------|
| id           | uuid          | PK                                         |
| nombre       | varchar(150)  | NOT NULL                                   |
| email        | varchar(255)  | NOT NULL, UNIQUE                           |
| password     | varchar(255)  | NOT NULL — bcrypt                          |
| activo       | boolean       | NOT NULL DEFAULT true                      |
| last_login   | timestamp     | NULL — se actualiza en cada login exitoso  |
| created_at   | timestamp     | NOT NULL                                   |
| updated_at   | timestamp     | NOT NULL                                   |

**Índices**: `email` (unique)

**Notas**:
- No usa `BaseModel` (no tiene `empresa_id`, no aplica RLS).
- Implementa `HasApiTokens` de Sanctum para emitir tokens con `tipo=superadmin`.
- Token de acceso: 4 horas. Sin refresh token.
- Solo se crean via `SuperadminSeeder` o comando Artisan protegido. No hay endpoint de registro público.

---

## Tabla: `impersonation_logs`

> Registro de auditoría de todas las sesiones de impersonación de superadmin.

| Columna        | Tipo          | Restricciones                                   |
|----------------|---------------|-------------------------------------------------|
| id             | uuid          | PK                                              |
| superadmin_id  | uuid          | NOT NULL, FK → superadmins(id)                  |
| empresa_id     | uuid          | NOT NULL, FK → empresas(id)                     |
| token_hash     | varchar(64)   | NOT NULL — SHA-256 del token temporal           |
| started_at     | timestamp     | NOT NULL                                        |
| ended_at       | timestamp     | NULL — se rellena al terminar la impersonación  |
| ip             | varchar(45)   | NOT NULL — soporta IPv6                         |

**Índices**: `superadmin_id`, `empresa_id`, `started_at`

**Índice único parcial**:
```sql
CREATE UNIQUE INDEX uq_impersonation_activa
  ON impersonation_logs (empresa_id, superadmin_id)
  WHERE ended_at IS NULL;
```
Previene que el mismo superadmin tenga 2 sesiones de impersonación activas simultáneamente para la misma empresa. Se debe crear en la migración via `DB::statement(...)` ya que Laravel Blueprint no soporta índices parciales nativamente.

**Notas**:
- `token_hash`: se guarda el hash SHA-256 del plain text token. Nunca el token completo.
- `ended_at = NULL` significa sesión activa o token expirado sin cierre explícito.
- El índice único parcial garantiza que `TerminarImpersonacionService` siempre encuentre exactamente 0 o 1 registro activo al buscar por `empresa_id + superadmin_id + ended_at IS NULL`.
- Sin `timestamps` estándar — usa `started_at` como `created_at` lógico.
- No tiene RLS — es una tabla interna del superadmin.

---

## Tabla: `descuentos_tenant`

> Descuentos manuales aplicados por el superadmin a tenants específicos.

| Columna        | Tipo                   | Restricciones                              |
|----------------|------------------------|--------------------------------------------|
| id             | uuid                   | PK                                         |
| empresa_id     | uuid                   | NOT NULL, FK → empresas(id)                |
| superadmin_id  | uuid                   | NOT NULL, FK → superadmins(id)             |
| tipo           | varchar(15)            | NOT NULL — `porcentaje` o `monto_fijo`     |
| valor          | decimal(8,2)           | NOT NULL — % o S/. según tipo              |
| motivo         | varchar(255)           | NOT NULL — descripción del descuento       |
| activo         | boolean                | NOT NULL DEFAULT true                      |
| created_at     | timestamp              | NOT NULL                                   |
| updated_at     | timestamp              | NOT NULL                                   |

**Índices**: `empresa_id`, `superadmin_id`, `activo`

**Constraints**:
- `tipo` ∈ `['porcentaje', 'monto_fijo']`
- `valor > 0`
- Para `tipo=porcentaje`: `valor <= 100`
- Solo puede haber un descuento `activo=true` por empresa a la vez (enforced en aplicación)

**Notas**:
- El descuento no modifica directamente `suscripciones`. Se aplica en tiempo de cobro.
- El tenant ve el descuento activo en `GET /api/suscripcion` bajo `datos_pago.descuento`.
- Desactivar un descuento no genera reembolso retroactivo.

---

## Cambios a tablas existentes

### `audit_logs` — columna `superadmin_id` (nueva)

| Columna        | Tipo    | Restricciones                                    |
|----------------|---------|--------------------------------------------------|
| superadmin_id  | uuid    | NULL, FK → superadmins(id)                       |

> Se añade via nueva migración. Permite distinguir acciones de superadmin
> de acciones de usuario tenant. Cuando `superadmin_id IS NOT NULL`,
> la acción fue ejecutada por el superadmin (posiblemente en contexto de impersonación).

**Acciones de superadmin en `audit_logs`**:

| `accion`                        | Descripción                                      |
|---------------------------------|--------------------------------------------------|
| `superadmin_suspend`            | Empresa suspendida por superadmin                |
| `superadmin_activate`           | Empresa reactivada por superadmin                |
| `superadmin_impersonation_start`| Inicio de sesión de impersonación                |
| `superadmin_impersonation_end`  | Fin de sesión de impersonación                   |
| `superadmin_update_plan`        | Superadmin editó precio/módulos de un plan       |
| `superadmin_apply_discount`     | Superadmin aplicó descuento a un tenant          |
| `superadmin_remove_discount`    | Superadmin desactivó descuento de un tenant      |

---

## Consideraciones de seguridad

1. **Sin RLS**: Las tablas `superadmins`, `impersonation_logs` y `descuentos_tenant` no tienen RLS. El acceso está controlado exclusivamente por `SuperadminMiddleware`.

2. **Bypass de RLS en consultas globales**: Los servicios del superadmin que consultan tablas de tenant (empresas, suscripciones, audit_logs, etc.) deben ejecutar `SET LOCAL app.empresa_id = ''` o equivalente para que la RLS retorne todas las filas (el CASE WHEN vacío devuelve `true`).

3. **Token de impersonación**: Tiene `abilities=['impersonated']` — el middleware tenant puede detectar este ability para registrar la acción en logs. El token pertenece al `owner` de la empresa, no al superadmin.

4. **Password del superadmin**: Nunca se retorna en ningún endpoint. Solo se valida via `Hash::check`.
