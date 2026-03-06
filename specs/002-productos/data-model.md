# Data Model: Módulo Productos

**Módulo**: 002-productos
**Generated**: 2026-03-05

---

## Diagrama de relaciones

```
empresas
  └── categorias (empresa_id)
        └── categorias (categoria_padre_id, self-reference)
  └── productos (empresa_id)
        ├── categoria_id → categorias
        ├── producto_imagenes (producto_id)
        ├── producto_precios_lista (producto_id)
        ├── producto_promociones (producto_id)
        ├── producto_unidades (producto_id)
        ├── producto_componentes (producto_id = kit)
        │     └── componente_id → productos
        └── precio_historial (producto_id)
              └── usuario_id → usuarios
```

---

## Tablas

### `categorias`

| Columna            | Tipo                  | Restricciones                        |
|--------------------|-----------------------|--------------------------------------|
| id                 | uuid PK               | default gen_random_uuid()            |
| empresa_id         | uuid FK               | → empresas.id, NOT NULL              |
| nombre             | varchar(120)          | NOT NULL                             |
| descripcion        | text                  | nullable                             |
| categoria_padre_id | uuid FK               | → categorias.id, nullable (self-ref) |
| activo             | boolean               | default true                         |
| created_at         | timestamptz           |                                      |
| updated_at         | timestamptz           |                                      |

**Índices**:
- `idx_categorias_empresa` ON (empresa_id)
- `idx_categorias_padre` ON (categoria_padre_id)
- `unique_categoria_nombre_empresa` ON (empresa_id, nombre, categoria_padre_id)

**RLS**: `empresa_id = auth().empresa_id`

---

### `productos`

| Columna                  | Tipo                              | Restricciones                  |
|--------------------------|-----------------------------------|--------------------------------|
| id                       | uuid PK                           | default gen_random_uuid()      |
| empresa_id               | uuid FK                           | → empresas.id, NOT NULL        |
| categoria_id             | uuid FK                           | → categorias.id, NOT NULL      |
| nombre                   | varchar(255)                      | NOT NULL                       |
| descripcion              | text                              | nullable                       |
| sku                      | varchar(100)                      | NOT NULL                       |
| codigo_barras            | varchar(50)                       | nullable                       |
| tipo                     | enum(simple, compuesto, servicio) | default 'simple'               |
| unidad_medida_principal  | varchar(20)                       | NOT NULL (NIU, KGM, LTR, etc.) |
| precio_compra            | decimal(12,4)                     | nullable                       |
| precio_venta             | decimal(12,4)                     | NOT NULL, check > 0            |
| igv_tipo                 | enum(gravado, exonerado, inafecto)| default 'gravado'              |
| activo                   | boolean                           | default true                   |
| created_at               | timestamptz                       |                                |
| updated_at               | timestamptz                       |                                |

**Índices**:
- `unique_sku_empresa` ON (empresa_id, sku) — SKU único por empresa
- `idx_productos_empresa` ON (empresa_id)
- `idx_productos_categoria` ON (categoria_id)
- `idx_productos_activo` ON (empresa_id, activo)
- `idx_productos_codigo_barras` ON (empresa_id, codigo_barras)

**RLS**: `empresa_id = auth().empresa_id`

**Notas**:
- `sku` es inmutable post-creación (enforced en capa de aplicación)
- `unidad_medida_principal` usa códigos SUNAT: NIU (unidad), KGM (kg), LTR (litro), etc.

---

### `producto_imagenes`

| Columna     | Tipo        | Restricciones                 |
|-------------|-------------|-------------------------------|
| id          | uuid PK     | default gen_random_uuid()     |
| producto_id | uuid FK     | → productos.id, ON DELETE CASCADE |
| url         | text        | NOT NULL (URL de Cloudflare R2) |
| path_r2     | text        | NOT NULL (path interno R2)    |
| orden       | smallint    | default 0                     |
| created_at  | timestamptz |                               |

**Índices**:
- `idx_imagenes_producto` ON (producto_id)

**Restricciones**:
- Máximo 5 imágenes por producto (enforced en aplicación)
- Path R2: `productos/{empresa_id}/{producto_id}/{timestamp}.{ext}`

---

### `producto_precios_lista`

| Columna     | Tipo                  | Restricciones                     |
|-------------|-----------------------|-----------------------------------|
| id          | uuid PK               | default gen_random_uuid()         |
| producto_id | uuid FK               | → productos.id, ON DELETE CASCADE |
| lista       | enum(L1, L2, L3)      | NOT NULL                          |
| nombre_lista| varchar(60)           | default: L1=Minorista, L2=Mayorista, L3=Especial |
| precio      | decimal(12,4)         | NOT NULL, check > 0               |
| created_at  | timestamptz           |                                   |
| updated_at  | timestamptz           |                                   |

**Índices**:
- `unique_precio_lista` ON (producto_id, lista)

---

### `producto_promociones`

| Columna      | Tipo                        | Restricciones                     |
|--------------|-----------------------------|-----------------------------------|
| id           | uuid PK                     | default gen_random_uuid()         |
| producto_id  | uuid FK                     | → productos.id, ON DELETE CASCADE |
| nombre       | varchar(120)                | NOT NULL                          |
| tipo         | enum(porcentaje, monto_fijo)| NOT NULL                          |
| valor        | decimal(12,4)               | NOT NULL, check > 0               |
| fecha_inicio | date                        | NOT NULL                          |
| fecha_fin    | date                        | nullable                          |
| activo       | boolean                     | default true                      |
| created_at   | timestamptz                 |                                   |
| updated_at   | timestamptz                 |                                   |

**Índices**:
- `idx_promociones_producto` ON (producto_id, activo)
- `idx_promociones_vigencia` ON (producto_id, fecha_inicio, fecha_fin)

**Notas**:
- Solo puede haber una promoción activa por producto a la vez (enforced en aplicación)
- Promoción `tipo=porcentaje`: `valor` entre 0.01 y 100
- `fecha_fin` nullable = sin vencimiento

---

### `producto_unidades`

| Columna           | Tipo          | Restricciones                     |
|-------------------|---------------|-----------------------------------|
| id                | uuid PK       | default gen_random_uuid()         |
| producto_id       | uuid FK       | → productos.id, ON DELETE CASCADE |
| unidad_medida     | varchar(20)   | NOT NULL                          |
| factor_conversion | decimal(12,6) | NOT NULL, check > 0               |
| precio_venta      | decimal(12,4) | nullable (si null, usa el base)   |
| created_at        | timestamptz   |                                   |
| updated_at        | timestamptz   |                                   |

**Índices**:
- `unique_unidad_producto` ON (producto_id, unidad_medida)

**Ejemplo**: 1 CAJA = 12 NIU → `factor_conversion=12, unidad_medida='CAJA'`

---

### `producto_componentes`

| Columna       | Tipo          | Restricciones                              |
|---------------|---------------|--------------------------------------------|
| id            | uuid PK       | default gen_random_uuid()                  |
| producto_id   | uuid FK       | → productos.id (el kit), ON DELETE CASCADE |
| componente_id | uuid FK       | → productos.id (el componente)             |
| cantidad      | decimal(12,4) | NOT NULL, check > 0                        |
| created_at    | timestamptz   |                                            |

**Índices**:
- `unique_componente` ON (producto_id, componente_id)

**Restricciones**:
- `producto_id != componente_id` (check constraint — no circular directo)
- No circular indirecto (enforced en aplicación antes de insertar)
- Solo productos con `tipo=compuesto` pueden tener componentes

---

### `precio_historial`

| Columna        | Tipo          | Restricciones                       |
|----------------|---------------|-------------------------------------|
| id             | uuid PK       | default gen_random_uuid()           |
| producto_id    | uuid FK       | → productos.id, ON DELETE CASCADE   |
| precio_anterior| decimal(12,4) | NOT NULL                            |
| precio_nuevo   | decimal(12,4) | NOT NULL                            |
| usuario_id     | uuid FK       | → usuarios.id, nullable (importación) |
| created_at     | timestamptz   |                                     |

**Índices**:
- `idx_historial_producto` ON (producto_id, created_at DESC)
- `idx_historial_empresa` ON (producto_id) — para joins eficientes desde superadmin

> **Nota RLS**: `precio_historial` **no tiene RLS propia**. Está protegida
> indirectamente a través del FK `producto_id → productos`, que sí tiene RLS
> con policy `tenant_isolation`. Ninguna consulta directa a `precio_historial`
> puede devolver datos de otra empresa sin pasar primero por el filtro de
> `productos`. El índice `idx_historial_empresa` optimiza los joins
> `precio_historial JOIN productos` que el superadmin realiza en queries globales.

---

## Unidades de medida SUNAT (referencia)

| Código | Descripción   |
|--------|---------------|
| NIU    | Unidad        |
| KGM    | Kilogramo     |
| LTR    | Litro         |
| MTR    | Metro         |
| MTK    | Metro cuadrado|
| BX     | Caja          |
| DZN    | Docena        |
| ZZ     | Servicios     |

---

## Migraciones (orden)

1. `create_categorias_table`
2. `create_productos_table`
3. `create_producto_imagenes_table`
4. `create_producto_precios_lista_table`
5. `create_producto_promociones_table`
6. `create_producto_unidades_table`
7. `create_producto_componentes_table`
8. `create_precio_historial_table`
9. `add_rls_policies_productos` — habilita RLS en categorias y productos
