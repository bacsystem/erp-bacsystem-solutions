# API Contracts: Módulo Productos

**Base URL**: `/api`
**Auth**: Bearer token (Sanctum)
**Tenant**: Todos los recursos filtrados por `empresa_id` del usuario autenticado

---

## Categorías

---

### `GET /api/categorias`

Retorna el árbol de categorías de la empresa con sus subcategorías.

**Auth**: `auth:sanctum, tenant`

**Query params**:
| Param | Tipo | Descripción |
|-------|------|-------------|
| activo | boolean | Filtrar por estado (default: true) |

**Response 200**:
```json
{
  "success": true,
  "message": "OK",
  "data": [
    {
      "id": "uuid",
      "nombre": "Electrónica",
      "descripcion": null,
      "activo": true,
      "subcategorias": [
        {
          "id": "uuid",
          "nombre": "Laptops",
          "descripcion": null,
          "activo": true,
          "subcategorias": []
        }
      ]
    }
  ]
}
```

**Errores**:
| Status | Código | Descripción |
|--------|--------|-------------|
| 401 | - | No autenticado |
| 403 | - | Suscripción inactiva |

---

### `POST /api/categorias`

**Auth**: `auth:sanctum, tenant, role:owner,admin, suscripcion.activa`

**Request**:
```json
{
  "nombre": "Electrónica",
  "descripcion": "Productos electrónicos",
  "categoria_padre_id": null
}
```

**Validaciones**:
| Campo | Regla |
|-------|-------|
| nombre | required, string, max:120, unique por empresa+padre |
| descripcion | nullable, string, max:500 |
| categoria_padre_id | nullable, uuid, debe existir en categorias de la empresa |

**Response 201**:
```json
{
  "success": true,
  "message": "Categoría creada",
  "data": {
    "id": "uuid",
    "nombre": "Electrónica",
    "descripcion": null,
    "categoria_padre_id": null,
    "activo": true,
    "created_at": "2026-03-05T10:00:00Z"
  }
}
```

**Errores**:
| Status | Descripción |
|--------|-------------|
| 422 | Nombre ya existe en esta empresa/nivel |
| 422 | categoria_padre_id no pertenece a la empresa |

---

### `PUT /api/categorias/{id}`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Request**:
```json
{
  "nombre": "Electrónica y Tecnología",
  "descripcion": "Descripción actualizada",
  "activo": true
}
```

**Validaciones**: Igual que POST, todos `sometimes`.

**Response 200**:
```json
{
  "success": true,
  "message": "Categoría actualizada",
  "data": { "...categoria actualizada..." }
}
```

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Categoría no encontrada en la empresa |
| 422 | Nombre duplicado |

---

### `DELETE /api/categorias/{id}`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Side effects**: Ninguno si no tiene productos asignados.

**Response 200**:
```json
{
  "success": true,
  "message": "Categoría eliminada"
}
```

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Categoría no encontrada |
| 422 | La categoría tiene productos asignados |
| 422 | La categoría tiene subcategorías activas |

---

## Productos

---

### `POST /api/productos`

**Auth**: `auth:sanctum, tenant, role:owner,admin, suscripcion.activa`

**Request** (multipart/form-data o application/json):
```json
{
  "nombre": "Laptop HP 14",
  "descripcion": "Laptop HP 14 pulgadas, 8GB RAM",
  "sku": "PROD-LAP-001",
  "codigo_barras": "7501234567890",
  "categoria_id": "uuid-categoria",
  "tipo": "simple",
  "unidad_medida_principal": "NIU",
  "precio_compra": 1800.00,
  "precio_venta": 2499.99,
  "igv_tipo": "gravado",
  "precios_lista": [
    { "lista": "L1", "nombre_lista": "Minorista", "precio": 2499.99 },
    { "lista": "L2", "nombre_lista": "Mayorista", "precio": 2299.99 }
  ],
  "unidades": [
    { "unidad_medida": "CAJA", "factor_conversion": 5, "precio_venta": 11500.00 }
  ],
  "componentes": []
}
```

**Validaciones**:
| Campo | Regla |
|-------|-------|
| nombre | required, string, max:255 |
| sku | required, string, max:100, unique por empresa |
| categoria_id | required, uuid, existe en categorias de la empresa |
| tipo | required, in:simple,compuesto,servicio |
| unidad_medida_principal | required, string, max:20 |
| precio_venta | required, numeric, min:0.01 |
| igv_tipo | required, in:gravado,exonerado,inafecto |
| precios_lista.*.lista | in:L1,L2,L3 |
| precios_lista.*.precio | numeric, min:0.01 |
| unidades.*.factor_conversion | numeric, min:0.001 |
| componentes | required_if:tipo,compuesto, array, min:1 |
| componentes.*.componente_id | uuid, existe en productos de la empresa, != producto actual |
| componentes.*.cantidad | numeric, min:0.001 |

**Response 201**:
```json
{
  "success": true,
  "message": "Producto creado",
  "data": {
    "id": "uuid",
    "nombre": "Laptop HP 14",
    "sku": "PROD-LAP-001",
    "codigo_barras": "7501234567890",
    "tipo": "simple",
    "unidad_medida_principal": "NIU",
    "precio_compra": 1800.00,
    "precio_venta": 2499.99,
    "igv_tipo": "gravado",
    "activo": true,
    "categoria": {
      "id": "uuid",
      "nombre": "Laptops"
    },
    "imagenes": [],
    "precios_lista": [
      { "id": "uuid", "lista": "L1", "nombre_lista": "Minorista", "precio": 2499.99 }
    ],
    "unidades": [],
    "componentes": [],
    "created_at": "2026-03-05T10:00:00Z"
  }
}
```

**Side effects**:
- Si `precios_lista` incluido: crea registros en `producto_precios_lista`
- Si `unidades` incluido: crea registros en `producto_unidades`
- Si `tipo=compuesto` y `componentes`: crea registros en `producto_componentes`
- Crea audit_log con acción `producto.crear`

**Errores**:
| Status | Descripción |
|--------|-------------|
| 422 | SKU ya existe en la empresa |
| 422 | categoria_id no pertenece a la empresa |
| 422 | componente_id == producto_id (referencia circular directa) |
| 422 | tipo=compuesto sin componentes |
| 422 | componente ya es parte de un ciclo (circular indirecto) |

---

### `GET /api/productos`

**Auth**: `auth:sanctum, tenant`

**Query params**:
| Param | Tipo | Descripción |
|-------|------|-------------|
| q | string | Búsqueda por nombre, SKU o código de barras |
| categoria_id | uuid | Filtrar por categoría (incluye subcategorías) |
| estado | string | activo \| inactivo (default: activo) |
| tipo | string | simple \| compuesto \| servicio |
| precio_min | numeric | Precio venta mínimo |
| precio_max | numeric | Precio venta máximo |
| sort | string | nombre \| precio_venta \| created_at (default: nombre) |
| order | string | asc \| desc (default: asc) |
| per_page | integer | Resultados por página (default: 20, max: 100) |
| page | integer | Página (default: 1) |

**Response 200**:
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "data": [
      {
        "id": "uuid",
        "nombre": "Laptop HP 14",
        "sku": "PROD-LAP-001",
        "tipo": "simple",
        "unidad_medida_principal": "NIU",
        "precio_venta": 2499.99,
        "igv_tipo": "gravado",
        "activo": true,
        "categoria": { "id": "uuid", "nombre": "Laptops" },
        "imagen_principal": "https://r2.example.com/productos/.../foto.jpg",
        "promocion_activa": {
          "tipo": "porcentaje",
          "valor": 15,
          "precio_final": 2124.99
        }
      }
    ],
    "meta": {
      "total": 45,
      "per_page": 20,
      "current_page": 1,
      "last_page": 3
    }
  }
}
```

---

### `GET /api/productos/{id}`

**Auth**: `auth:sanctum, tenant`

**Response 200**:
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "nombre": "Laptop HP 14",
    "descripcion": "Laptop HP 14 pulgadas, 8GB RAM",
    "sku": "PROD-LAP-001",
    "codigo_barras": "7501234567890",
    "tipo": "simple",
    "unidad_medida_principal": "NIU",
    "precio_compra": 1800.00,
    "precio_venta": 2499.99,
    "igv_tipo": "gravado",
    "activo": true,
    "categoria": { "id": "uuid", "nombre": "Laptops", "padre": { "id": "uuid", "nombre": "Electrónica" } },
    "imagenes": [
      { "id": "uuid", "url": "https://r2.example.com/...", "orden": 0 }
    ],
    "precios_lista": [
      { "lista": "L1", "nombre_lista": "Minorista", "precio": 2499.99 }
    ],
    "unidades": [
      { "unidad_medida": "CAJA", "factor_conversion": 5, "precio_venta": 11500.00 }
    ],
    "componentes": [],
    "promocion_activa": null,
    "precio_historial": [
      { "precio_anterior": 2299.99, "precio_nuevo": 2499.99, "usuario": "Juan Pérez", "created_at": "2026-03-01T..." }
    ],
    "created_at": "2026-03-05T10:00:00Z",
    "updated_at": "2026-03-05T10:00:00Z"
  }
}
```

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Producto no encontrado en la empresa |

---

### `PUT /api/productos/{id}`

**Auth**: `auth:sanctum, tenant, role:owner,admin, suscripcion.activa`

**Request**:
```json
{
  "nombre": "Laptop HP 14 Actualizada",
  "descripcion": "Nueva descripción",
  "precio_venta": 2699.99,
  "igv_tipo": "gravado",
  "activo": true
}
```

**Validaciones**:
- Todos los campos del POST excepto `sku` y `tipo` (ambos inmutables)
- Si `sku` es enviado → 422 "El SKU no puede modificarse"
- `precio_venta`: si cambia, se registra en `precio_historial`

**Response 200**:
```json
{
  "success": true,
  "message": "Producto actualizado",
  "data": { "...producto completo..." }
}
```

**Side effects**:
- Si `precio_venta` cambia: inserta en `precio_historial`
- Crea audit_log con acción `producto.actualizar`

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Producto no encontrado |
| 422 | SKU enviado (inmutable) |
| 422 | categoria_id no pertenece a la empresa |

---

### `DELETE /api/productos/{id}`

Desactiva el producto (soft delete lógico: `activo = false`).

**Auth**: `auth:sanctum, tenant, role:owner,admin, suscripcion.activa`

**Response 200**:
```json
{
  "success": true,
  "message": "Producto desactivado"
}
```

**Side effects**:
- Desactiva promociones activas del producto
- Crea audit_log con acción `producto.desactivar`

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Producto no encontrado |
| 422 | Producto tiene ventas registradas (cuando Módulo 3 esté implementado) |

---

### `POST /api/productos/{id}/imagenes`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Content-Type**: `multipart/form-data`

**Request**:
```
imagen: [archivo binario]
```

**Validaciones**:
| Campo | Regla |
|-------|-------|
| imagen | required, file, mimes:jpg,jpeg,png,webp, max:5120 (5MB) |

**Response 201**:
```json
{
  "success": true,
  "message": "Imagen subida",
  "data": {
    "id": "uuid",
    "url": "https://r2.cloudflare.com/productos/empresa_id/producto_id/1709647200.jpg",
    "orden": 1
  }
}
```

**Side effects**:
- Sube imagen a R2 en path `productos/{empresa_id}/{producto_id}/{timestamp}.{ext}`
- Incrementa contador de orden

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Producto no encontrado |
| 422 | Formato de imagen no válido |
| 422 | Tamaño excede 5MB |
| 422 | El producto ya tiene 5 imágenes |

---

### `DELETE /api/productos/{id}/imagenes/{imagen_id}`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Response 200**:
```json
{
  "success": true,
  "message": "Imagen eliminada"
}
```

**Side effects**:
- Elimina archivo de R2
- Elimina registro de `producto_imagenes`
- Reordena imágenes restantes (orden secuencial)

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Imagen no encontrada en el producto |

---

### `POST /api/productos/importar`

Importación masiva desde CSV o Excel. Operación en 2 pasos: preview → confirmar.

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Content-Type**: `multipart/form-data`

**Step 1 — Preview** (`confirmar=false`):
```
archivo: [archivo .csv o .xlsx]
confirmar: false
```

**Response 200 (preview)**:
```json
{
  "success": true,
  "message": "Preview listo",
  "data": {
    "total": 10,
    "validos": 8,
    "errores": 2,
    "filas": [
      {
        "fila": 2,
        "nombre": "Laptop HP",
        "sku": "LAP-001",
        "precio_venta": 2499.99,
        "estado": "valido"
      },
      {
        "fila": 5,
        "nombre": "Monitor LG",
        "sku": "MON-001",
        "precio_venta": null,
        "estado": "error",
        "errores": ["precio_venta es requerido"]
      }
    ],
    "import_token": "token-temporal-uuid"
  }
}
```

**Step 2 — Confirmar** (`confirmar=true`):
```json
{
  "import_token": "token-temporal-uuid"
}
```

**Response 201 (confirmado)**:
```json
{
  "success": true,
  "message": "Importación completada",
  "data": {
    "creados": 8,
    "errores": 2,
    "detalle_errores": [
      { "fila": 5, "errores": ["precio_venta es requerido"] }
    ]
  }
}
```

**Template CSV** (`GET /api/productos/importar/template`):
Retorna archivo `.xlsx` descargable con columnas: nombre, sku, categoria, tipo, unidad_medida, precio_compra, precio_venta, igv_tipo, descripcion, codigo_barras

**Errores**:
| Status | Descripción |
|--------|-------------|
| 422 | Formato de archivo no soportado (solo csv/xlsx) |
| 422 | Archivo vacío o sin filas de datos |
| 422 | import_token inválido o expirado |

---

### `GET /api/productos/exportar`

**Auth**: `auth:sanctum, tenant`

**Query params**:
| Param | Tipo | Descripción |
|-------|------|-------------|
| formato | string | excel \| csv (default: excel) |
| categoria_id | uuid | Filtrar por categoría |
| estado | string | activo \| inactivo \| todos (default: activo) |

**Response 200**:
- `Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`
- `Content-Disposition: attachment; filename="catalogo-2026-03-05.xlsx"`

**Side effects**: Ninguno.

---

### `GET /api/productos/exportar/pdf`

**Auth**: `auth:sanctum, tenant`

**Query params**: igual que exportar Excel.

**Response 200**:
- `Content-Type: application/pdf`
- `Content-Disposition: attachment; filename="catalogo-2026-03-05.pdf"`

---

## Precios y promociones

---

### `PUT /api/productos/{id}/precios-lista`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Request**:
```json
{
  "precios": [
    { "lista": "L1", "nombre_lista": "Minorista", "precio": 2499.99 },
    { "lista": "L2", "nombre_lista": "Mayorista", "precio": 2299.99 },
    { "lista": "L3", "nombre_lista": "Especial", "precio": 2199.99 }
  ]
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Precios de lista actualizados",
  "data": [
    { "lista": "L1", "nombre_lista": "Minorista", "precio": 2499.99 },
    { "lista": "L2", "nombre_lista": "Mayorista", "precio": 2299.99 }
  ]
}
```

---

### `POST /api/productos/{id}/promociones`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Request**:
```json
{
  "nombre": "Promo Verano",
  "tipo": "porcentaje",
  "valor": 15,
  "fecha_inicio": "2026-03-01",
  "fecha_fin": "2026-03-31"
}
```

**Validaciones**:
| Campo | Regla |
|-------|-------|
| nombre | required, string, max:120 |
| tipo | required, in:porcentaje,monto_fijo |
| valor | required, numeric, min:0.01; si porcentaje: max:100 |
| fecha_inicio | required, date |
| fecha_fin | nullable, date, after_or_equal:fecha_inicio |

**Response 201**:
```json
{
  "success": true,
  "message": "Promoción creada",
  "data": {
    "id": "uuid",
    "nombre": "Promo Verano",
    "tipo": "porcentaje",
    "valor": 15,
    "precio_resultante": 2124.99,
    "fecha_inicio": "2026-03-01",
    "fecha_fin": "2026-03-31",
    "activo": true
  }
}
```

**Side effects**:
- Desactiva la promoción activa anterior del producto

**Errores**:
| Status | Descripción |
|--------|-------------|
| 422 | valor > 100 cuando tipo=porcentaje |
| 422 | fecha_fin anterior a fecha_inicio |

---

### `DELETE /api/productos/{id}/promociones/{promo_id}`

**Auth**: `auth:sanctum, tenant, role:owner,admin`

**Response 200**:
```json
{
  "success": true,
  "message": "Promoción desactivada"
}
```

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Promoción no encontrada en el producto |

---

## QR de producto

---

### `GET /api/productos/{id}/qr`

Genera y retorna el código QR del SKU del producto.

**Auth**: `auth:sanctum, tenant`

**Query params**:
| Param | Tipo | Descripción |
|-------|------|-------------|
| formato | string | `svg` \| `png` (default: `svg`) |

**Response 200 — SVG** (`formato=svg`):
```
Content-Type: image/svg+xml
Body: <svg ...>...</svg>
```

**Response 200 — PNG** (`formato=png`):
```
Content-Type: image/png
Body: [binario PNG]
```

> El contenido del QR es el SKU del producto. Útil para etiquetas de precio
> y para el flujo de entrada en el POS (lectura por cámara o scanner).

**Errores**:
| Status | Descripción |
|--------|-------------|
| 404 | Producto no encontrado en la empresa |
| 422 | formato no válido (debe ser svg o png) |
