# Tasks: Módulo Productos

**Input**: Design documents from `/specs/002-productos/`
**Branch**: `002-productos`
**Generated**: 2026-03-05
**Spec**: [spec.md](./spec.md) | **Plan**: [plan.md](./plan.md) | **Data Model**: [data-model.md](./data-model.md)

**Tests**: Incluidos — requeridos por Definition of Done (feature test por slice, happy path + error cases, tenant isolation).

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Puede ejecutarse en paralelo (archivos distintos, sin dependencias incompletas)
- **[Story]**: A qué user story pertenece la tarea (HU-01..HU-06)
- Rutas relativas a la raíz del repositorio

---

## Phase 1: Setup

**Purpose**: Dependencias, migraciones, modelos y factories base.

### Dependencias

- [ ] T001 Instalar dependencias backend: `composer require maatwebsite/excel barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode` en `backend/`
- [ ] T002 [P] Instalar dependencias frontend: `npm install @tanstack/react-table react-dropzone xlsx` en `frontend/`
- [ ] T003 [P] Publicar config de maatwebsite/excel: `php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider"` — verificar `backend/config/excel.php`
- [ ] T004 [P] Publicar config de dompdf: `php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"` — verificar `backend/config/dompdf.php`

### Migraciones

- [ ] T005 Crear migración `backend/database/migrations/2026_03_06_000001_create_categorias_table.php` con schema según data-model.md
- [ ] T006 Crear migración `backend/database/migrations/2026_03_06_000002_create_productos_table.php` con schema según data-model.md (enum tipo, igv_tipo; unique sku+empresa)
- [ ] T007 [P] Crear migración `backend/database/migrations/2026_03_06_000003_create_producto_imagenes_table.php`
- [ ] T008 [P] Crear migración `backend/database/migrations/2026_03_06_000004_create_producto_precios_lista_table.php`
- [ ] T009 [P] Crear migración `backend/database/migrations/2026_03_06_000005_create_producto_promociones_table.php`
- [ ] T010 [P] Crear migración `backend/database/migrations/2026_03_06_000006_create_producto_unidades_table.php`
- [ ] T011 [P] Crear migración `backend/database/migrations/2026_03_06_000007_create_producto_componentes_table.php` (check constraint producto_id != componente_id)
- [ ] T012 [P] Crear migración `backend/database/migrations/2026_03_06_000008_create_precio_historial_table.php`
- [ ] T013 Crear migración `backend/database/migrations/2026_03_06_000009_add_rls_policies_productos.php` — habilita RLS en `categorias` y `productos` con policy `tenant_isolation`

### Modelos Eloquent

- [ ] T014 Crear modelo `backend/app/Modules/Core/Producto/Models/Categoria.php` — extiende BaseModel, fillable, relaciones (padre, hijos, productos)
- [ ] T015 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/Producto.php` — extiende BaseModel, fillable, casts, relaciones (categoria, imagenes, precios_lista, promociones, unidades, componentes, historial)
- [ ] T016 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/ProductoImagen.php`
- [ ] T017 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/ProductoPrecioLista.php`
- [ ] T018 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/ProductoPromocion.php` — scope `activa()` filtra por activo=true y fechas vigentes
- [ ] T019 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/ProductoUnidad.php`
- [ ] T020 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/ProductoComponente.php`
- [ ] T021 [P] Crear modelo `backend/app/Modules/Core/Producto/Models/PrecioHistorial.php`

### Factories y seeders

- [ ] T022 Crear factory `backend/database/factories/CategoriaFactory.php` con estados: `conSubcategoria()`
- [ ] T023 [P] Crear factory `backend/database/factories/ProductoFactory.php` con estados: `inactivo()`, `compuesto()`, `servicio()`, `conImagen()`, `conPromocion()`
- [ ] T024 [P] Crear helper de test `backend/tests/Feature/Core/Helpers/ProductoHelper.php` — trait con `crearProducto()`, `crearCategoria()`, `actingAsTenant()`

### Rutas

- [ ] T025 Agregar rutas de productos y categorías a `backend/routes/api.php` según plan.md §Slices backend

---

## Phase 2: HU-01 — Crear producto

**Purpose**: Tests primero, luego implementación del slice Crear.

### Tests

- [ ] T026 [HU-01] Crear test `backend/tests/Feature/Core/Productos/CrearProductoTest.php` con casos:
  - owner crea producto simple válido → 201
  - SKU duplicado en misma empresa → 422
  - SKU duplicado en otra empresa → 201 (permitido)
  - empleado intenta crear → 403
  - producto compuesto sin componentes → 422
  - producto compuesto con referencia circular directa → 422
  - más de 5 imágenes → 422
  - precio_venta faltante → 422
  - categoria_id de otra empresa → 422

### Implementación

- [ ] T027 [HU-01] Crear `backend/app/Modules/Core/Categoria/Crear/CrearCategoriaRequest.php` y `CrearCategoriaService.php` y `CrearCategoriaController.php`
- [ ] T028 [HU-01] Crear `backend/app/Modules/Core/Producto/Crear/CrearProductoRequest.php` — validaciones según contracts/productos.md
- [ ] T029 [HU-01] Crear `backend/app/Modules/Core/Producto/Crear/CrearProductoService.php`:
  - Verificar SKU único en empresa
  - Si tipo=compuesto: validar mínimo 1 componente y ausencia de referencias circulares (DFS recursivo)
  - Crear producto en transacción DB
  - Crear precios_lista, unidades, componentes si vienen en request
  - Registrar audit_log `producto.crear`
- [ ] T030 [HU-01] Crear `backend/app/Modules/Core/Producto/Crear/CrearProductoController.php` — invocable, retorna 201

---

## Phase 3: HU-02 — Listar y buscar productos

### Tests

- [ ] T031 [HU-02] Crear test `backend/tests/Feature/Core/Productos/ListarProductosTest.php` con casos:
  - listado paginado retorna 20 por defecto → 200
  - búsqueda por q=nombre parcial → filtra correctamente
  - búsqueda por q=sku → filtra correctamente
  - filtro categoria_id → solo productos de esa categoría
  - filtro estado=inactivo → muestra inactivos
  - filtro precio_min + precio_max → rango correcto
  - sort=precio_venta&order=desc → orden correcto
  - tenant isolation: empresa A no ve productos de empresa B

### Implementación

- [ ] T032 [HU-02] Crear `backend/app/Modules/Core/Producto/Listar/ListarProductosService.php`:
  - Query builder con filtros opcionales (q, categoria_id, estado, tipo, precio_min, precio_max)
  - Búsqueda q: OR en nombre ILIKE, sku ILIKE, codigo_barras ILIKE
  - Eager loading: categoria, primera imagen
  - paginate(per_page, ['*'], 'page', page)
- [ ] T033 [HU-02] Crear `backend/app/Modules/Core/Producto/Listar/ListarProductosController.php`
- [ ] T034 [HU-02] Crear `backend/app/Modules/Core/Producto/GetDetalle/GetProductoDetalleService.php` — carga relaciones completas (imagenes, precios_lista, unidades, componentes, promocion_activa, precio_historial últimos 10)
- [ ] T035 [HU-02] Crear `backend/app/Modules/Core/Producto/GetDetalle/GetProductoDetalleController.php`

---

## Phase 4: HU-03 — Editar producto

### Tests

- [ ] T036 [HU-03] Crear test `backend/tests/Feature/Core/Productos/ActualizarProductoTest.php` con casos:
  - actualizar nombre → 200
  - cambiar precio_venta → 200 + registra en precio_historial
  - enviar sku en body → 422
  - desactivar producto → activo=false
  - empleado intenta actualizar → 403
  - producto de otra empresa → 404
- [ ] T037 [HU-03] Crear test `backend/tests/Feature/Core/Productos/DesactivarProductoTest.php`:
  - owner desactiva → activo=false, promociones desactivadas
  - producto inexistente → 404
  - producto de otra empresa → 404

### Implementación

- [ ] T038 [HU-03] Crear `backend/app/Modules/Core/Producto/Actualizar/ActualizarProductoRequest.php` — todos los campos `sometimes`, rechaza `sku`
- [ ] T039 [HU-03] Crear `backend/app/Modules/Core/Producto/Actualizar/ActualizarProductoService.php`:
  - Si precio_venta cambia: insertar en precio_historial con precio_anterior, precio_nuevo, usuario_id
  - Si sku enviado: lanzar ValidationException
  - Actualizar en transacción, retornar producto completo
  - Registrar audit_log `producto.actualizar`
- [ ] T040 [HU-03] Crear `backend/app/Modules/Core/Producto/Actualizar/ActualizarProductoController.php`
- [ ] T041 [HU-03] Crear `backend/app/Modules/Core/Producto/Desactivar/DesactivarProductoService.php` — activo=false, desactiva promociones activas, audit_log `producto.desactivar`
- [ ] T042 [HU-03] Crear `backend/app/Modules/Core/Producto/Desactivar/DesactivarProductoController.php`

### Imágenes

- [ ] T043 [HU-03] Crear test `backend/tests/Feature/Core/Productos/ImagenesProductoTest.php`:
  - subir imagen válida → 201, URL de R2
  - subir cuando ya hay 5 → 422
  - formato inválido → 422
  - tamaño > 5MB → 422
  - eliminar imagen → 200, archivo borrado de R2
- [ ] T044 [HU-03] Crear `backend/app/Modules/Core/Producto/SubirImagen/SubirImagenRequest.php` — mimes:jpg,jpeg,png,webp, max:5120
- [ ] T045 [HU-03] Crear `backend/app/Modules/Core/Producto/SubirImagen/SubirImagenService.php` — verifica límite 5, sube a R2 con path correcto, guarda en producto_imagenes
- [ ] T046 [HU-03] Crear `backend/app/Modules/Core/Producto/SubirImagen/SubirImagenController.php`
- [ ] T047 [HU-03] Crear `backend/app/Modules/Core/Producto/EliminarImagen/EliminarImagenService.php` — elimina de R2 y de DB, reordena
- [ ] T048 [HU-03] Crear `backend/app/Modules/Core/Producto/EliminarImagen/EliminarImagenController.php`

---

## Phase 5: HU-04 — Gestionar categorías

### Tests

- [ ] T049 [HU-04] Crear test `backend/tests/Feature/Core/Productos/CategoriasTest.php` con casos:
  - crear categoría raíz → 201
  - crear subcategoría con padre válido → 201
  - crear con padre de otra empresa → 422
  - nombre duplicado en mismo nivel y empresa → 422
  - listar → árbol anidado
  - actualizar nombre → 200
  - eliminar con productos asignados → 422
  - eliminar con subcategorías → 422
  - eliminar sin dependencias → 200
  - tenant isolation categorías

### Implementación

- [ ] T050 [HU-04] Crear `backend/app/Modules/Core/Categoria/Listar/ListarCategoriasService.php` — retorna árbol recursivo (eager loading hijos)
- [ ] T051 [HU-04] Crear `backend/app/Modules/Core/Categoria/Listar/ListarCategoriasController.php`
- [ ] T052 [HU-04] Crear `backend/app/Modules/Core/Categoria/Actualizar/ActualizarCategoriaRequest.php`, `ActualizarCategoriaService.php`, `ActualizarCategoriaController.php`
- [ ] T053 [HU-04] Crear `backend/app/Modules/Core/Categoria/Eliminar/EliminarCategoriaService.php` — verifica productos y subcategorías antes de eliminar
- [ ] T054 [HU-04] Crear `backend/app/Modules/Core/Categoria/Eliminar/EliminarCategoriaController.php`

### Precios lista y promociones

- [ ] T055 [HU-04] [P] Crear `backend/app/Modules/Core/Producto/PrecioLista/ActualizarPrecioListaRequest.php`, `ActualizarPrecioListaService.php`, `ActualizarPrecioListaController.php`
- [ ] T056 [HU-04] [P] Crear `backend/app/Modules/Core/Producto/Promocion/CrearPromocion/CrearPromocionRequest.php`, `CrearPromocionService.php`, `CrearPromocionController.php` — desactiva promoción anterior
- [ ] T057 [HU-04] [P] Crear `backend/app/Modules/Core/Producto/Promocion/DesactivarPromocion/DesactivarPromocionService.php`, `DesactivarPromocionController.php`

---

## Phase 6: HU-05 — Importar CSV/Excel

### Tests

- [ ] T058 [HU-05] Crear test `backend/tests/Feature/Core/Productos/ImportarProductosTest.php` con casos:
  - descargar template → 200, Content-Type xlsx
  - subir CSV válido (3 filas) sin confirmar → preview con 3 validos
  - subir CSV con fila inválida → preview muestra error en esa fila
  - confirmar import_token válido → 201, productos creados
  - import_token expirado → 422
  - formato inválido (pdf) → 422
  - archivo vacío → 422

### Implementación

- [ ] T059 [HU-05] Crear `backend/app/Modules/Core/Producto/ImportarCSV/ProductosImport.php` — Maatwebsite import class con WithHeadingRow, WithValidation, SkipsOnError
- [ ] T060 [HU-05] Crear `backend/app/Modules/Core/Producto/ImportarCSV/ImportarProductosService.php`:
  - Step 1 (preview): parsea, valida fila a fila, guarda resultado en cache por 10min con import_token UUID
  - Step 2 (confirmar): recupera del cache, inserta en DB en transacción, retorna reporte
- [ ] T061 [HU-05] Crear `backend/app/Modules/Core/Producto/ImportarCSV/ImportarProductosRequest.php` y `ImportarProductosController.php`
- [ ] T062 [HU-05] Crear endpoint `GET /api/productos/importar/template` que retorna Excel con columnas del modelo

---

## Phase 7: HU-06 — Exportar catálogo

### Tests

- [ ] T063 [HU-06] Crear test `backend/tests/Feature/Core/Productos/ExportarProductosTest.php` con casos:
  - GET /exportar sin params → 200, Content-Type xlsx
  - GET /exportar?formato=csv → Content-Type text/csv
  - GET /exportar?categoria_id=X → solo productos de esa categoría
  - GET /exportar/pdf → 200, Content-Type application/pdf
  - tenant isolation: solo exporta sus propios productos

### Implementación

- [ ] T064 [HU-06] Crear `backend/app/Modules/Core/Producto/ExportarExcel/ProductosExport.php` — Maatwebsite export class con FromQuery, WithHeadings, WithMapping
- [ ] T065 [HU-06] Crear `backend/app/Modules/Core/Producto/ExportarExcel/ExportarExcelController.php` — soporta formato excel y csv
- [ ] T066 [HU-06] Crear vista `backend/resources/views/pdf/catalogo-productos.blade.php` — layout con logo empresa, tabla de productos, precios e IGV
- [ ] T067 [HU-06] Crear `backend/app/Modules/Core/Producto/ExportarPDF/ExportarPDFController.php` — usa dompdf, stream response

---

## Phase 7b: Frontend

**Purpose**: Interfaces de usuario para el módulo de productos.

- [ ] T068 [P] Crear tipos `frontend/src/modules/core/producto/shared/producto.types.ts` — interfaces Producto, Categoria, ProductoImagen, PrecioLista, Promocion, PrecioHistorial
- [ ] T069 [P] Crear cliente API `frontend/src/modules/core/producto/shared/productos.api.ts` — axios instance del tenant con funciones CRUD
- [ ] T070 [P] Crear hook `frontend/src/modules/core/producto/listar-productos/use-productos.ts` — useProductos, useProductoDetalle con React Query
- [ ] T071 [P] Crear hook `frontend/src/modules/core/producto/categorias/use-categorias.ts` — useCategorias, useCrearCategoria, etc.
- [ ] T072 Crear componente `ProductosFiltros.tsx` — inputs q, select categoria, select estado, select tipo, rango precios
- [ ] T073 Crear componente `ProductosTable.tsx` — @tanstack/react-table, columnas: nombre, SKU, precio, IGV, categoría, estado, acciones
- [ ] T074 [P] Crear componente `ProductosGrid.tsx` — vista en grid con imagen, nombre, precio
- [ ] T075 Crear página `frontend/src/app/(tenant)/productos/page.tsx` — toggle tabla/grid, filtros, paginación
- [ ] T076 Crear componente `ImagenesUpload.tsx` — react-dropzone, preview, límite 5, indica errores
- [ ] T077 Crear componente `ComponentesForm.tsx` — buscador de productos para agregar componentes, cantidad
- [ ] T078 Crear componente `ProductoForm.tsx` — React Hook Form + Zod, todos los campos del producto, tabs: General | Precios | Imágenes | Componentes
- [ ] T079 Crear página `frontend/src/app/(tenant)/productos/nuevo/page.tsx`
- [ ] T080 Crear página `frontend/src/app/(tenant)/productos/[id]/page.tsx` — carga producto, tabs: Ver | Editar | Historial precios | Promociones
- [ ] T081 Crear componente `ImportarDropzone.tsx` — dropzone para xlsx/csv, llama preview
- [ ] T082 Crear componente `ImportarPreview.tsx` — tabla con filas válidas/errores, botón confirmar
- [ ] T083 Crear página `frontend/src/app/(tenant)/productos/importar/page.tsx`
- [ ] T084 [P] Crear componente `CategoriasManager.tsx` — árbol de categorías, CRUD inline
- [ ] T085 [P] Crear página `frontend/src/app/(tenant)/categorias/page.tsx`

---

## Phase 8: Polish + DoD

**Purpose**: Tenant isolation, validaciones finales y build.

- [ ] T086 Crear test `backend/tests/Feature/Core/Productos/TenantIsolationProductosTest.php`:
  - empresa A no puede ver productos de empresa B
  - empresa A no puede editar productos de empresa B
  - empresa A no puede usar categorías de empresa B
  - empresa A no puede importar hacia empresa B
  - empleado no puede crear/editar/eliminar
- [ ] T087 Verificar que todas las rutas de productos tengan middleware `suscripcion.activa` donde corresponde
- [ ] T088 Agregar QR al detalle del producto: `GET /api/productos/{id}/qr` — retorna SVG/PNG con QR del SKU usando simple-qrcode
- [ ] T089 [P] Ejecutar `php artisan test --filter=Productos` — todos los tests deben pasar
- [ ] T090 [P] Ejecutar `php artisan test --filter=Categorias` — todos los tests deben pasar
- [ ] T091 Ejecutar `php artisan test` completo — sin regresiones en módulos anteriores
- [ ] T092 Ejecutar `npm run build` — build limpio sin errores TypeScript
