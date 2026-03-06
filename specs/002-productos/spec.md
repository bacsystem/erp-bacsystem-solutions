# Feature Specification: Módulo Productos

**Feature Branch**: `002-productos`
**Created**: 2026-03-05
**Status**: Draft
**Módulo**: 2 de 8 — Base bloqueante para Facturación (Módulo 3), Ventas (Módulo 4) e Inventario (Módulo 5).

---

> **Nota del proyecto**: Este módulo define el catálogo de productos y servicios de cada empresa.
> No gestiona stock (responsabilidad de Inventario - Módulo 5).
> Provee precios, IGV y unidades de medida a Facturación y Ventas.

---

## Usuarios del módulo

| Usuario  | Descripción                                                         |
|----------|---------------------------------------------------------------------|
| Owner    | CRUD completo de productos, categorías, importación y exportación   |
| Admin    | CRUD completo de productos, categorías, importación y exportación   |
| Empleado | Solo lectura del catálogo                                           |
| Contador | Solo lectura del catálogo                                           |

---

## User Scenarios & Testing *(mandatory)*

---

### User Story 1 — Crear producto (Priority: P1)

Como owner o admin, quiero crear un producto en mi catálogo para poder usarlo en facturas y ventas.

**Why this priority**: Sin productos no existe facturación ni ventas. Es la entidad base del ERP.

**Independent Test**: Un owner puede crear un producto simple con nombre, SKU, precio de venta e IGV y queda disponible en el catálogo de su empresa.

**Acceptance Scenarios**:

1. **Given** un owner autenticado, **When** envía nombre, SKU, categoría, precio de venta e igv_tipo válidos, **Then** el producto se crea con estado activo y se retorna con id y timestamps.

2. **Given** un owner que ya tiene un producto con SKU "PROD-001", **When** intenta crear otro con el mismo SKU, **Then** recibe error 422 "El SKU ya está en uso en tu empresa".

3. **Given** un owner, **When** crea un producto de tipo `compuesto` con componentes, **Then** el sistema valida que ningún componente sea el propio producto (referencia circular).

4. **Given** un owner, **When** crea un producto con `tipo=compuesto` pero sin componentes, **Then** recibe error 422 "Un producto compuesto requiere al menos un componente".

5. **Given** un empleado autenticado, **When** intenta crear un producto, **Then** recibe error 403.

6. **Given** un owner, **When** adjunta 6 imágenes al crear el producto, **Then** recibe error 422 "Máximo 5 imágenes por producto".

---

### User Story 2 — Listar y buscar productos (Priority: P1)

Como cualquier usuario autenticado, quiero buscar y filtrar el catálogo para encontrar un producto rápidamente.

**Why this priority**: Búsqueda es necesaria antes de facturar o vender.

**Independent Test**: Un usuario puede buscar por nombre parcial y obtener solo los productos de su empresa que coincidan.

**Acceptance Scenarios**:

1. **Given** un owner con 50 productos, **When** hace GET /api/productos sin filtros, **Then** recibe la primera página de 20 productos con meta de paginación.

2. **Given** un owner, **When** busca con `q=laptop`, **Then** recibe productos cuyo nombre, SKU o código de barras contenga "laptop".

3. **Given** un owner, **When** filtra por `categoria_id` y `estado=activo`, **Then** recibe solo productos activos de esa categoría.

4. **Given** dos empresas con productos, **When** un usuario de empresa A consulta el catálogo, **Then** solo ve productos de empresa A (tenant isolation).

5. **Given** un owner, **When** filtra por `precio_min=10&precio_max=100`, **Then** recibe productos con precio_venta entre S/10 y S/100.

---

### User Story 3 — Editar producto (Priority: P1)

Como owner o admin, quiero editar los datos de un producto manteniendo el historial de precios.

**Why this priority**: Precios cambian con frecuencia en PYMEs peruanas.

**Independent Test**: Al cambiar el precio_venta de un producto, el sistema guarda el precio anterior en precio_historial con el usuario que lo modificó.

**Acceptance Scenarios**:

1. **Given** un owner, **When** actualiza el precio_venta de un producto, **Then** el precio_historial registra precio_anterior, precio_nuevo y usuario_id.

2. **Given** un owner, **When** intenta actualizar el SKU de un producto existente, **Then** recibe error 422 "El SKU no puede modificarse".

3. **Given** un owner, **When** desactiva un producto (activo=false), **Then** el producto deja de aparecer en listados por defecto pero puede buscarse con `estado=inactivo`.

4. **Given** un owner, **When** intenta eliminar un producto que ya fue usado en una factura, **Then** recibe error 422 "Este producto tiene ventas registradas y no puede eliminarse; puedes desactivarlo".

---

### User Story 4 — Gestionar categorías (Priority: P2)

Como owner o admin, quiero organizar mis productos en categorías y subcategorías.

**Why this priority**: Necesaria para filtrar el catálogo correctamente.

**Independent Test**: Un owner puede crear una categoría padre "Electrónica" y una subcategoría "Laptops", luego asignar un producto a "Laptops".

**Acceptance Scenarios**:

1. **Given** un owner, **When** crea una categoría con nombre válido, **Then** la categoría se crea asociada a su empresa.

2. **Given** un owner, **When** crea una subcategoría con `categoria_padre_id` válido, **Then** queda anidada bajo la categoría padre.

3. **Given** un owner, **When** intenta eliminar una categoría que tiene productos asignados, **Then** recibe error 422 "Esta categoría tiene productos asignados".

4. **Given** dos empresas, **When** owner A consulta sus categorías, **Then** solo ve las de su empresa.

---

### User Story 5 — Importar productos (Priority: P2)

Como owner o admin, quiero importar mi catálogo desde un archivo CSV o Excel para migrar datos rápidamente.

**Why this priority**: PYMEs frecuentemente tienen catálogos en Excel.

**Independent Test**: Un owner puede descargar el template, llenar 3 productos y subirlo; los 3 productos quedan creados en su catálogo.

**Acceptance Scenarios**:

1. **Given** un owner con el template correctamente llenado, **When** sube el archivo, **Then** recibe un preview con los registros a importar antes de confirmar.

2. **Given** un owner que sube un CSV con 10 filas donde la fila 5 tiene SKU duplicado, **Then** la importación muestra error en fila 5 pero importa las demás filas válidas.

3. **Given** un owner, **When** confirma la importación del preview, **Then** los productos se crean y recibe reporte con conteo de creados/errores.

4. **Given** un owner, **When** sube un archivo con formato inválido (no CSV/XLSX), **Then** recibe error 422 "Formato de archivo no soportado".

---

### User Story 6 — Exportar catálogo (Priority: P3)

Como owner o admin, quiero exportar mi catálogo para compartirlo o respaldarlo.

**Why this priority**: Útil para auditorías y presentación a clientes.

**Independent Test**: Un owner puede exportar su catálogo a Excel con los filtros activos y descargar el archivo.

**Acceptance Scenarios**:

1. **Given** un owner, **When** exporta a Excel sin filtros, **Then** descarga un archivo `.xlsx` con todos sus productos activos.

2. **Given** un owner, **When** exporta a PDF, **Then** descarga un catálogo con nombre, imagen (si tiene), precio de venta e IGV de cada producto.

3. **Given** un owner, **When** exporta con filtro `categoria_id=X`, **Then** el archivo solo contiene productos de esa categoría.

---

## Reglas de negocio globales

| Regla | Detalle |
|-------|---------|
| SKU único | Único por empresa, inmutable post-creación |
| Producto con ventas | Solo se puede desactivar, nunca eliminar |
| Referencia circular | Kit no puede ser componente de sí mismo, ni de forma indirecta |
| Máx imágenes | 5 por producto; formatos: JPG, PNG, WEBP; tamaño: máx 5MB c/u |
| Promoción activa | Tiene prioridad sobre precio de lista al facturar |
| Tenant isolation | Todos los recursos filtrados por empresa_id |
| Precio venta | Siempre requerido; precio_compra es opcional |
| IGV por defecto | `gravado` (18%) |
