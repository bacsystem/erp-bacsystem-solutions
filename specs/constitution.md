# Constitution — OperaAI ERP

**Propósito**: Registro permanente de decisiones de diseño, arquitectura y producto
que aplican a todo el sistema. Cada decisión aquí documentada es vinculante para
todos los módulos presentes y futuros.

**Última actualización**: 2026-03-06

---

## Índice

1. [Arquitectura general](#1-arquitectura-general)
2. [Modelo de datos global](#2-modelo-de-datos-global)
3. [Autenticación y seguridad](#3-autenticación-y-seguridad)
4. [Módulo Facturación ampliado con Ventas](#4-módulo-facturación-ampliado-con-ventas)
5. [Disponibilidad por plan](#5-disponibilidad-por-plan)
6. [Roadmap de módulos](#6-roadmap-de-módulos)

---

## 1. Arquitectura general

- **Backend**: Laravel 11.x + PHP 8.3, API REST JSON, PostgreSQL 16, Redis 7.
- **Frontend**: Next.js 14.x + TypeScript 5.x, Zustand, React Query 5.x, shadcn/ui.
- **Multi-tenant**: aislamiento por `empresa_id` enforceado a nivel de base de datos via RLS (Row Level Security) de PostgreSQL.
- **Autenticación**: Laravel Sanctum con access token (15 min) + refresh token httpOnly cookie (30 días).
- **Pagos**: Culqi (Perú). Sin Stripe ni pasarelas internacionales en v1.
- **Facturación electrónica SUNAT**: Nubefact como proveedor OSE.
- **Storage**: Cloudflare R2 (S3-compatible) para logos, PDFs y adjuntos.
- **Email**: Resend via Laravel Mail.
- **Panel superadmin**: completamente separado del sistema tenant — rutas, middleware, modelos y store frontend propios.

---

## 2. Modelo de datos global

- Todas las tablas de tenant tienen `empresa_id uuid NOT NULL FK → empresas` enforceado por RLS.
- UUIDs como PK en todas las tablas.
- `BaseModel` en Laravel aplica global scope por `empresa_id` y auto-genera UUID en `creating`.
- `email` de usuario es ÚNICO a nivel global (un email = una sola empresa en todo el sistema).
- `ruc` de empresa es ÚNICO a nivel global.
- `planes` es una tabla global sin `empresa_id` — datos del SaaS, no del tenant.

---

## 3. Autenticación y seguridad

- Login rate limit: 5 intentos / 15 min por IP (configurable via `LOGIN_RATE_LIMIT` en env).
- Registro rate limit: 100 intentos / hora por IP.
- Superadmin login rate limit: 3 intentos / 15 min por IP.
- Token superadmin: 4 horas, sin refresh token.
- Impersonación de tenant por superadmin: token temporal 2 horas con `abilities=['impersonated']`.
- Audit log de toda acción relevante en `audit_logs` con `empresa_id` nullable (eventos de sistema sin tenant permitidos).

---

## 4. Módulo Facturación ampliado con Ventas

> **Estado**: Decisión de diseño registrada. Especificación completa pendiente
> hasta que llegue el turno de Facturación en el roadmap.

### 4.1 Flujo principal

```
Cotización → Orden de venta → Factura/Boleta SUNAT
     └──────────────────────→ Factura/Boleta SUNAT  (conversión directa)
```

### 4.2 Reglas de conversión

- Una **Cotización** puede convertirse en:
  - **Orden de venta** (1 click)
  - **Factura** directamente, saltando la Orden de venta (opcional según el caso de uso)
- Una **Orden de venta** puede convertirse en **Factura** (1 click)
- Cada documento mantiene referencia al documento origen:
  - La Factura sabe de qué Orden de venta o Cotización proviene
  - La Orden de venta sabe de qué Cotización proviene (si aplica)
- La conversión no elimina el documento origen — queda en estado `convertida_a`.

### 4.3 Documentos del módulo

#### 4.3.1 Cotización / Proforma

| Campo               | Detalle                                                   |
|---------------------|-----------------------------------------------------------|
| Estados             | `borrador`, `enviada`, `aceptada`, `rechazada`, `vencida` |
| Validez             | Configurable por empresa, default 15 días                 |
| PDF                 | Descargable y enviable por email y WhatsApp               |
| Conversión          | A Orden de venta o a Factura directa                      |
| Numeración          | Formato `COT-0001`, correlativo por empresa               |

#### 4.3.2 Orden de venta

| Campo               | Detalle                                                     |
|---------------------|-------------------------------------------------------------|
| Estados             | `pendiente`, `en_proceso`, `completada`, `cancelada`        |
| Cotización origen   | Opcional — referencia a `cotizaciones.id`                   |
| Conversión          | A Factura/Boleta con 1 click                                |
| Inventario          | Afecta stock al pasar a estado `completada`                 |
| Numeración          | Formato `OV-0001`, correlativo por empresa                  |

#### 4.3.3 Factura / Boleta SUNAT

- Referencia opcional a Orden de venta origen o Cotización origen.
- Emisión vía Nubefact (ya planificado en roadmap).
- Numeración según series SUNAT configuradas por empresa.

#### 4.3.4 POS / Caja rápida

- Venta directa sin cotización ni orden de venta previa.
- Emite boleta automáticamente al cerrar la venta.
- Ideal para tiendas con mostrador o ventas de mostrador.
- No requiere cliente registrado (consumidor final).

#### 4.3.5 Reportes de ventas

- Por vendedor (usuario del sistema)
- Por producto
- Por cliente
- Por período (día, semana, mes, año)

### 4.4 Esquema de datos (diseño preliminar)

#### Tabla: `cotizaciones`

| Columna               | Tipo                | Restricciones                                           |
|-----------------------|---------------------|---------------------------------------------------------|
| id                    | uuid                | PK                                                      |
| empresa_id            | uuid                | NOT NULL, FK → empresas                                 |
| cliente_id            | uuid                | NOT NULL, FK → clientes                                 |
| numero                | varchar(20)         | NOT NULL — `COT-0001`, único por empresa                |
| estado                | varchar(15)         | NOT NULL — `borrador\|enviada\|aceptada\|rechazada\|vencida` |
| fecha_emision         | date                | NOT NULL                                                |
| fecha_vencimiento     | date                | NOT NULL — `fecha_emision + validez_dias`               |
| subtotal              | decimal(12,2)       | NOT NULL                                                |
| igv                   | decimal(12,2)       | NOT NULL — 18% del subtotal gravado                     |
| total                 | decimal(12,2)       | NOT NULL                                                |
| notas                 | text                | NULL                                                    |
| convertida_a          | varchar(15)         | NULL — `orden_venta` o `factura`                        |
| documento_destino_id  | uuid                | NULL — id del documento al que se convirtió             |
| created_at            | timestamp           | NOT NULL                                                |
| updated_at            | timestamp           | NOT NULL                                                |

**Índices**: `empresa_id`, `cliente_id`, `estado`, `fecha_emision`, `(empresa_id, numero)` unique

#### Tabla: `ordenes_venta`

| Columna               | Tipo                | Restricciones                                            |
|-----------------------|---------------------|----------------------------------------------------------|
| id                    | uuid                | PK                                                       |
| empresa_id            | uuid                | NOT NULL, FK → empresas                                  |
| cliente_id            | uuid                | NOT NULL, FK → clientes                                  |
| cotizacion_id         | uuid                | NULL, FK → cotizaciones                                  |
| numero                | varchar(20)         | NOT NULL — `OV-0001`, único por empresa                  |
| estado                | varchar(15)         | NOT NULL — `pendiente\|en_proceso\|completada\|cancelada` |
| fecha_emision         | date                | NOT NULL                                                 |
| fecha_entrega         | date                | NULL                                                     |
| subtotal              | decimal(12,2)       | NOT NULL                                                 |
| igv                   | decimal(12,2)       | NOT NULL                                                 |
| total                 | decimal(12,2)       | NOT NULL                                                 |
| notas                 | text                | NULL                                                     |
| created_at            | timestamp           | NOT NULL                                                 |
| updated_at            | timestamp           | NOT NULL                                                 |

**Índices**: `empresa_id`, `cliente_id`, `cotizacion_id`, `estado`, `fecha_emision`, `(empresa_id, numero)` unique

#### Tabla: `detalle_cotizacion` / `detalle_orden_venta` (pendiente de diseño)

> El esquema de líneas de detalle (producto, cantidad, precio unitario, descuento por línea)
> se definirá en la spec completa del módulo. Aplican las mismas reglas de IGV que las facturas.

### 4.5 Decisiones de diseño tomadas

1. **Numeración correlativa por empresa** — no global. `COT-0001` en la empresa A y en la empresa B son documentos distintos sin conflicto.
2. **IGV siempre calculado en el backend** — el frontend envía subtotal y el backend calcula IGV (18%) y total. Nunca confiar en totales enviados por el cliente.
3. **Conversión es una operación atómica** — crear el documento destino y actualizar `convertida_a` + `documento_destino_id` en una sola transacción DB.
4. **Cotización vencida automáticamente** — un job schedulado diario marca como `vencida` las cotizaciones donde `fecha_vencimiento < today()` y `estado = 'enviada'`.
5. **POS no requiere cliente registrado** — usa un cliente genérico "Varios / Consumidor final" por empresa.

---

## 5. Disponibilidad por plan

| Funcionalidad                          | Starter | PYME | Enterprise |
|----------------------------------------|:-------:|:----:|:----------:|
| Facturación básica (facturas/boletas)  | ✓       | ✓    | ✓          |
| POS / Caja rápida                      | ✓       | ✓    | ✓          |
| Reportes básicos de ventas             | ✓       | ✓    | ✓          |
| Cotizaciones / Proformas               |         | ✓    | ✓          |
| Órdenes de venta                       |         | ✓    | ✓          |
| Reportes avanzados (por vendedor, etc) |         | ✓    | ✓          |
| Múltiples vendedores con metas         |         |      | ✓          |
| Comisiones por vendedor                |         |      | ✓          |

---

## 6. Roadmap de módulos

| Orden | Módulo              | Branch              | Estado         |
|-------|---------------------|---------------------|----------------|
| 0     | Superadmin          | `000-superadmin`    | Especificado   |
| 1     | Core / Auth         | `001-core-auth`     | Implementado   |
| 2     | Clientes            | `002-clientes`      | Pendiente      |
| 3     | Productos           | `003-productos`     | Pendiente      |
| 4     | Facturación + Ventas| `004-facturacion`   | Decisiones ✓   |
| 5     | Inventario          | `005-inventario`    | Pendiente      |
| 6     | CRM                 | `006-crm`           | Pendiente      |
| 7     | Finanzas            | `007-finanzas`      | Pendiente      |
| 8     | IA                  | `008-ia`            | Pendiente      |
| 9     | RRHH (Enterprise)   | `009-rrhh`          | Pendiente      |
