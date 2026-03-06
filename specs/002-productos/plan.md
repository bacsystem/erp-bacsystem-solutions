# Plan: Módulo Productos

**Feature Branch**: `002-productos`
**Generated**: 2026-03-05
**Stack**: Laravel 11 / PHP 8.3 + Next.js 14 / TypeScript 5

---

## Arquitectura

Vertical Slice Architecture. Cada operación vive en su propio slice bajo `app/Modules/Core/Producto/` y `app/Modules/Core/Categoria/`. Los slices no se llaman entre sí; comparten solo modelos Eloquent.

---

## Stack tecnológico

### Backend
| Tecnología | Uso |
|------------|-----|
| Laravel 11 / PHP 8.3 | Framework base |
| PostgreSQL 16 | Base de datos con RLS |
| Cloudflare R2 (S3-compatible) | Almacenamiento de imágenes |
| `maatwebsite/excel` ^3.1 | Importación/exportación Excel |
| `barryvdh/laravel-dompdf` ^2.2 | Exportación PDF catálogo |
| `simplesoftwareio/simple-qrcode` ^4.2 | Generación de QR para productos |
| `league/flysystem-aws-s3-v3` | Driver R2 (ya instalado) |

### Frontend
| Tecnología | Uso |
|------------|-----|
| Next.js 14 / TypeScript | Framework frontend |
| React Query 5 | Fetching y cache |
| React Hook Form 7 + Zod 3 | Formularios y validación |
| shadcn/ui | Componentes UI |
| `@tanstack/react-table` | Tabla con filtros y paginación |
| `react-dropzone` | Upload de imágenes |
| `xlsx` | Preview de importación CSV/Excel |

---

## Estructura de archivos

### Backend

```
backend/app/Modules/Core/
├── Categoria/
│   ├── Crear/
│   │   ├── CrearCategoriaRequest.php
│   │   ├── CrearCategoriaService.php
│   │   └── CrearCategoriaController.php
│   ├── Listar/
│   │   ├── ListarCategoriasService.php
│   │   └── ListarCategoriasController.php
│   ├── Actualizar/
│   │   ├── ActualizarCategoriaRequest.php
│   │   ├── ActualizarCategoriaService.php
│   │   └── ActualizarCategoriaController.php
│   └── Eliminar/
│       ├── EliminarCategoriaService.php
│       └── EliminarCategoriaController.php
└── Producto/
    ├── Models/
    │   ├── Producto.php
    │   ├── Categoria.php
    │   ├── ProductoImagen.php
    │   ├── ProductoPrecioLista.php
    │   ├── ProductoPromocion.php
    │   ├── ProductoUnidad.php
    │   ├── ProductoComponente.php
    │   └── PrecioHistorial.php
    ├── Crear/
    │   ├── CrearProductoRequest.php
    │   ├── CrearProductoService.php
    │   └── CrearProductoController.php
    ├── Listar/
    │   ├── ListarProductosService.php
    │   └── ListarProductosController.php
    ├── GetDetalle/
    │   ├── GetProductoDetalleService.php
    │   └── GetProductoDetalleController.php
    ├── Actualizar/
    │   ├── ActualizarProductoRequest.php
    │   ├── ActualizarProductoService.php
    │   └── ActualizarProductoController.php
    ├── Desactivar/
    │   ├── DesactivarProductoService.php
    │   └── DesactivarProductoController.php
    ├── SubirImagen/
    │   ├── SubirImagenRequest.php
    │   ├── SubirImagenService.php
    │   └── SubirImagenController.php
    ├── EliminarImagen/
    │   ├── EliminarImagenService.php
    │   └── EliminarImagenController.php
    ├── ImportarCSV/
    │   ├── ImportarProductosRequest.php
    │   ├── ImportarProductosService.php
    │   ├── ProductosImport.php          (Maatwebsite import class)
    │   └── ImportarProductosController.php
    ├── ExportarExcel/
    │   ├── ProductosExport.php          (Maatwebsite export class)
    │   └── ExportarExcelController.php
    ├── ExportarPDF/
    │   └── ExportarPDFController.php
    ├── Promocion/
    │   ├── CrearPromocion/
    │   │   ├── CrearPromocionRequest.php
    │   │   ├── CrearPromocionService.php
    │   │   └── CrearPromocionController.php
    │   └── DesactivarPromocion/
    │       ├── DesactivarPromocionService.php
    │       └── DesactivarPromocionController.php
    └── PrecioLista/
        ├── ActualizarPrecioListaRequest.php
        ├── ActualizarPrecioListaService.php
        └── ActualizarPrecioListaController.php

backend/database/
├── migrations/
│   ├── 2026_03_06_000001_create_categorias_table.php
│   ├── 2026_03_06_000002_create_productos_table.php
│   ├── 2026_03_06_000003_create_producto_imagenes_table.php
│   ├── 2026_03_06_000004_create_producto_precios_lista_table.php
│   ├── 2026_03_06_000005_create_producto_promociones_table.php
│   ├── 2026_03_06_000006_create_producto_unidades_table.php
│   ├── 2026_03_06_000007_create_producto_componentes_table.php
│   ├── 2026_03_06_000008_create_precio_historial_table.php
│   └── 2026_03_06_000009_add_rls_policies_productos.php
├── factories/
│   ├── CategoriaFactory.php
│   └── ProductoFactory.php
└── seeders/
    └── ProductosSeeder.php

backend/tests/Feature/Core/Productos/
├── CrearProductoTest.php
├── ListarProductosTest.php
├── ActualizarProductoTest.php
├── DesactivarProductoTest.php
├── ImagenesProductoTest.php
├── CategoriasTest.php
├── ImportarProductosTest.php
├── ExportarProductosTest.php
└── TenantIsolationProductosTest.php

backend/resources/views/pdf/
└── catalogo-productos.blade.php

backend/routes/api.php (additions)
```

### Frontend

```
frontend/src/
├── app/(tenant)/productos/
│   ├── page.tsx                    (listado con filtros)
│   ├── nuevo/page.tsx              (formulario crear)
│   ├── [id]/page.tsx              (detalle/editar)
│   └── importar/page.tsx          (importación CSV)
├── app/(tenant)/categorias/
│   └── page.tsx
└── modules/core/producto/
    ├── listar-productos/
    │   ├── use-productos.ts
    │   ├── ProductosTable.tsx
    │   ├── ProductosGrid.tsx
    │   └── ProductosFiltros.tsx
    ├── crear-producto/
    │   ├── use-crear-producto.ts
    │   ├── ProductoForm.tsx
    │   ├── ImagenesUpload.tsx
    │   └── ComponentesForm.tsx
    ├── editar-producto/
    │   ├── use-editar-producto.ts
    │   └── EditarProductoForm.tsx
    ├── importar-productos/
    │   ├── use-importar.ts
    │   ├── ImportarDropzone.tsx
    │   └── ImportarPreview.tsx
    ├── categorias/
    │   ├── use-categorias.ts
    │   ├── CategoriasManager.tsx
    │   └── CategoriaForm.tsx
    └── shared/
        ├── producto.types.ts
        └── productos.api.ts
```

---

## Slices backend

| Slice | Método | Ruta | Middleware |
|-------|--------|------|------------|
| Categoria/Listar | GET | `/api/categorias` | auth, tenant |
| Categoria/Crear | POST | `/api/categorias` | auth, tenant, role:owner,admin |
| Categoria/Actualizar | PUT | `/api/categorias/{id}` | auth, tenant, role:owner,admin |
| Categoria/Eliminar | DELETE | `/api/categorias/{id}` | auth, tenant, role:owner,admin |
| Producto/Listar | GET | `/api/productos` | auth, tenant |
| Producto/GetDetalle | GET | `/api/productos/{id}` | auth, tenant |
| Producto/Crear | POST | `/api/productos` | auth, tenant, role:owner,admin, suscripcion.activa |
| Producto/Actualizar | PUT | `/api/productos/{id}` | auth, tenant, role:owner,admin, suscripcion.activa |
| Producto/Desactivar | DELETE | `/api/productos/{id}` | auth, tenant, role:owner,admin, suscripcion.activa |
| Producto/SubirImagen | POST | `/api/productos/{id}/imagenes` | auth, tenant, role:owner,admin |
| Producto/EliminarImagen | DELETE | `/api/productos/{id}/imagenes/{imagen_id}` | auth, tenant, role:owner,admin |
| Producto/ImportarCSV | POST | `/api/productos/importar` | auth, tenant, role:owner,admin |
| Producto/ExportarExcel | GET | `/api/productos/exportar` | auth, tenant |
| Producto/ExportarPDF | GET | `/api/productos/exportar/pdf` | auth, tenant |
| Promocion/Crear | POST | `/api/productos/{id}/promociones` | auth, tenant, role:owner,admin |
| Promocion/Desactivar | DELETE | `/api/productos/{id}/promociones/{promo_id}` | auth, tenant, role:owner,admin |
| PrecioLista/Actualizar | PUT | `/api/productos/{id}/precios-lista` | auth, tenant, role:owner,admin |

---

## Reglas de negocio

| Regla | Implementación |
|-------|---------------|
| SKU único por empresa | unique constraint en DB + validación en CrearProductoService |
| SKU inmutable | ActualizarProductoRequest ignora campo `sku`; lanza 422 si se envía |
| Producto con ventas | DesactivarProductoService verifica existencia en `detalle_facturas` antes de eliminar (Módulo 3 futuro — por ahora permite eliminación) |
| Máx 5 imágenes | SubirImagenService cuenta imágenes actuales antes de subir |
| Kit sin circular ref | CrearProductoService recorre árbol de componentes recursivamente |
| Solo un kit activo | CrearPromocionService desactiva promoción anterior antes de crear |
| Historial de precios | ActualizarProductoService detecta cambio en precio_venta y registra en precio_historial |
| Tenant isolation | BaseModel global scope en todos los modelos con empresa_id |
| R2 path | `productos/{empresa_id}/{producto_id}/{timestamp}.{ext}` |

---

## Template de importación CSV/Excel

El endpoint `GET /api/productos/importar/template` retorna un archivo `.xlsx`
con las siguientes columnas en orden exacto:

| Col | Nombre          | Requerido | Valores válidos / Notas |
|-----|-----------------|-----------|--------------------------|
| A   | nombre          | ✓         | string, max 255 |
| B   | sku             | ✓         | string, max 100, único por empresa |
| C   | categoria       |           | Nombre exacto de la categoría (debe existir en la empresa) |
| D   | tipo            |           | `simple` \| `compuesto` \| `servicio` (default: simple) |
| E   | unidad_medida   |           | Código SUNAT: NIU, KGM, LTR, MTR, BX, DZN, ZZ (default: NIU) |
| F   | precio_compra   |           | decimal, min: 0 |
| G   | precio_venta    | ✓         | decimal, min: 0.01 |
| H   | igv_tipo        |           | `gravado` \| `exonerado` \| `inafecto` (default: gravado) |
| I   | descripcion     |           | text, max 1000 |
| J   | codigo_barras   |           | string, max 50 |

**Notas de importación**:
- La fila 1 es la cabecera (no se importa)
- Filas con `sku` duplicado dentro del archivo se marcan como error; no se importan
- Filas con `sku` que ya existe en la empresa se marcan como error (no actualiza)
- Si `categoria` no existe en la empresa, la fila se marca como error
- Campos no requeridos con celda vacía usan el valor por defecto indicado

---

## Instalación de dependencias

```bash
# Backend
cd backend
composer require maatwebsite/excel barryvdh/laravel-dompdf simplesoftwareio/simple-qrcode

# Frontend
cd frontend
npm install @tanstack/react-table react-dropzone xlsx
```

---

## Variables de entorno

No se requieren variables nuevas. Las credenciales R2 ya están en `AWS_*` del módulo 001.

---

## Dependencias con otros módulos

| Módulo | Dependencia |
|--------|-------------|
| Facturación (003) | Lee `productos.precio_venta`, `igv_tipo`, `unidad_medida_principal` |
| Inventario (005) | Extiende `productos` con stock; no modifica la tabla base |
| CRM (futuro) | Usa productos en propuestas comerciales |
| Superadmin (000) | Sin dependencia directa |
