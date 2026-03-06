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
7. [Patrones de UI frontend](#7-patrones-de-ui-frontend)

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

---

## 7. Patrones de UI frontend

> **Vinculante**: todos los formularios y páginas del módulo tenant DEBEN seguir estos patrones
> para garantizar consistencia visual. La referencia canónica es `RegisterForm` (crear empresa)
> y `ProductoForm` (crear producto).

### 7.1 Layout de página de detalle / creación

```tsx
<div className="flex h-screen overflow-hidden">
  <Sidebar />
  <main className="flex-1 flex flex-col overflow-hidden">
    <SuscripcionBanner />
    <div className="flex-1 overflow-y-auto bg-gray-50">
      <div className="max-w-2xl mx-auto p-8 space-y-6">

        {/* Breadcrumb + título */}
        <div>
          <div className="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <IconoModulo className="w-4 h-4" />
            <Link href="/modulo">Módulo</Link>
            <span>/</span>
            <span className="text-gray-900 font-medium">Acción</span>
          </div>
          <div className="flex items-center gap-2 mt-2">
            <Link href="/modulo" className="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white border border-transparent hover:border-gray-200 rounded-lg transition-all">
              <ArrowLeft className="w-4 h-4" />
            </Link>
            <h1 className="text-2xl font-bold text-gray-900">Título de la página</h1>
          </div>
        </div>

        {/* Tarjeta principal del formulario */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-8">
          <FormularioComponente ... />
        </div>

      </div>
    </div>
  </main>
</div>
```

### 7.2 Constantes de estilo de formulario

Definir al inicio de cada componente de formulario:

```tsx
const INPUT_CLASS =
  'w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder:text-gray-400'

const LABEL = 'block text-sm font-medium text-gray-700 mb-1'

const ERROR = 'text-xs text-red-500 mt-1'

const BTN_PRIMARY =
  'flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 transition-colors'

const BTN_SECONDARY =
  'flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors'
```

### 7.3 Indicador de pasos (step wizard)

Patrón tomado directamente de `RegisterForm`. Usar cuando un formulario tiene ≥ 2 pasos lógicos.

```tsx
// Helpers de estilo — definir dentro del componente
const circleClass = (i: number, current: number) => {
  const base = 'w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold'
  if (i < current)   return `${base} bg-green-500 text-white`          // completado
  if (i === current) return `${base} bg-blue-600 text-white ring-4 ring-blue-100`  // activo
  return `${base} bg-gray-100 text-gray-400`                            // pendiente
}

const labelClass = (i: number, current: number) =>
  `text-xs mt-1 font-medium ${
    i < current ? 'text-green-600' : i === current ? 'text-blue-600' : 'text-gray-400'
  }`

// JSX del indicador
const STEPS = ['General', 'Clasificación', 'Precios']  // ajustar por módulo

<div className="flex items-start justify-between mb-8">
  {STEPS.map((label, i) => (
    <React.Fragment key={i}>
      <div className="flex flex-col items-center">
        <div className={circleClass(i, step)}>
          {i < step ? <Check className="w-4 h-4" /> : i + 1}
        </div>
        <span className={labelClass(i, step)}>{label}</span>
      </div>
      {i < STEPS.length - 1 && (
        <div className={`h-0.5 flex-1 mb-4 transition-all ${i < step ? 'bg-green-400' : 'bg-gray-200'}`} />
      )}
    </React.Fragment>
  ))}
</div>
```

**Regla**: Antes de avanzar al siguiente paso, validar los campos del paso actual con `trigger(STEP_FIELDS[step])` de React Hook Form.

```tsx
const STEP_FIELDS: (keyof FormValues)[][] = [
  ['nombre', 'sku'],         // paso 0
  ['categoria_id', 'tipo'],  // paso 1
  ['precio_venta', 'igv_tipo'], // paso 2
]

const goNext = async () => {
  const ok = await trigger(STEP_FIELDS[step] as any)
  if (ok) setStep((s) => s + 1)
}
```

### 7.4 Selectores de tipo card (radio visual)

Para campos enum con pocas opciones (tipo de producto, tipo IGV, régimen tributario, etc.) usar tarjetas seleccionables en lugar de `<select>`:

```tsx
{OPTIONS.map((opt) => (
  <label
    key={opt.value}
    className={`flex-1 flex flex-col items-center gap-1 p-3 border rounded-xl cursor-pointer transition-all text-center
      ${watch('campo') === opt.value
        ? 'border-blue-500 bg-blue-50 text-blue-700'
        : 'border-gray-200 hover:border-gray-300 text-gray-600'
      }`}
  >
    <input type="radio" value={opt.value} {...register('campo')} className="sr-only" />
    <opt.Icon className="w-5 h-5" />
    <span className="text-xs font-medium">{opt.label}</span>
    {opt.hint && <span className="text-[10px] text-gray-400">{opt.hint}</span>}
  </label>
))}
```

### 7.5 Paginación de tablas

Todas las tablas con listados paginados deben usar este patrón.

**Reglas**:
- `per_page` por defecto: **10**. Opciones disponibles: `[10, 20, 50]`.
- Al cambiar `per_page`, resetear `page` a **1**.
- Mostrar la paginación siempre que `meta.total > 0` (no solo cuando hay más de una página).
- Backend: límite máximo de `per_page` = 100.

**Estado inicial**:
```tsx
const [filters, setFilters] = useState<XyzFilters>({ page: 1, per_page: 10 })
```

**JSX**:
```tsx
{meta && meta.total > 0 && (() => {
  const totalPages = Math.ceil(meta.total / meta.per_page)
  const currentPage = filters.page ?? 1
  return (
    <div className="flex items-center justify-between text-sm text-gray-500">
      <div className="flex items-center gap-2">
        <span>{meta.total} registros · página {currentPage} de {totalPages}</span>
        <select
          value={filters.per_page ?? 10}
          onChange={(e) => setFilters((f) => ({ ...f, page: 1, per_page: Number(e.target.value) }))}
          className="ml-2 px-2 py-1 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          {[10, 20, 50].map((n) => (
            <option key={n} value={n}>{n} por página</option>
          ))}
        </select>
      </div>
      <div className="flex items-center gap-2">
        <button
          disabled={currentPage <= 1}
          onClick={() => setFilters((f) => ({ ...f, page: currentPage - 1 }))}
          className="px-3 py-1.5 border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
        >
          Anterior
        </button>
        <button
          disabled={currentPage >= totalPages}
          onClick={() => setFilters((f) => ({ ...f, page: currentPage + 1 }))}
          className="px-3 py-1.5 border border-gray-200 rounded-lg disabled:opacity-40 hover:bg-gray-50"
        >
          Siguiente
        </button>
      </div>
    </div>
  )
})()}
```

**Tipo de filtros** (añadir a cada módulo):
```tsx
export interface XyzFilters {
  // ...filtros del módulo
  page?: number
  per_page?: number
}
```

**Backend** (`ApiResponse::paginated`):
```php
'meta' => [
    'page'     => $paginator->currentPage(),
    'per_page' => $paginator->perPage(),
    'total'    => $paginator->total(),
]
```

### 7.6 Creación inline de entidad relacionada

Para evitar salir del formulario cuando se necesita crear una entidad relacionada (ej: categoría dentro del formulario de producto):

```tsx
const [showNuevo, setShowNuevo] = useState(false)
const [nuevaNombre, setNuevaNombre] = useState('')
const { mutateAsync: crear, isPending } = useCrearEntidad()

const handleCrear = async () => {
  const entidad = await crear({ nombre: nuevaNombre })
  setValue('entidad_id', entidad.id)  // auto-selecciona la recién creada
  setShowNuevo(false)
  setNuevaNombre('')
}
```

Mostrar el mini-formulario inline debajo del `<select>`, colapsable con un botón `+ Nueva X`.
