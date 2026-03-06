# Contratos API: Empresa

**Prefijo**: `/api`
**Autenticación**: Bearer token requerido en todos los endpoints
**Middleware**: `auth:sanctum`, `tenant` (global); `suscripcion.activa` solo en `PUT /api/empresa` y `POST /api/empresa/logo`
**Autorización**: GET → cualquier rol; PUT/POST → solo `owner` o `admin`

---

## GET /api/empresa

> **Disponibilidad**: Este endpoint está disponible en **todos los estados de suscripción**, incluyendo `cancelada`. El usuario necesita ver sus datos de empresa en la pantalla `/reactivar`. Por esta razón, `GET /api/empresa` **no lleva el middleware `suscripcion.activa`**, a diferencia de `PUT /api/empresa` y `POST /api/empresa/logo` que sí lo requieren.

Retorna los datos de la empresa del usuario autenticado.

### Request

Sin parámetros.

### Response 200

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "ruc": "20123456789",
    "razon_social": "Importaciones García SAC",
    "nombre_comercial": "García Imports",
    "direccion": "Av. Larco 123, Miraflores",
    "ubigeo": "150101",
    "logo_url": "https://r2.example.com/logos/uuid/1234567890.png",
    "regimen_tributario": "RMT",
    "created_at": "2026-03-05T10:00:00Z"
  }
}
```

---

## PUT /api/empresa

Actualiza los datos editables de la empresa. El RUC es inmutable y se ignora si se envía.

**Roles permitidos**: `owner`, `admin`

### Request

```json
{
  "nombre_comercial": "García Imports Peru",
  "direccion": "Av. Larco 456, Miraflores",
  "ubigeo": "150101",
  "regimen_tributario": "RG"
}
```

**Validaciones**:
- `nombre_comercial`: sometimes, string, min:2, max:200
- `direccion`: sometimes, string
- `ubigeo`: sometimes, nullable, string, digits:6
- `regimen_tributario`: sometimes, in:RER,RG,RMT

**Campos inmutables** (ignorados si se envían): `ruc`, `razon_social`, `id`

### Response 200

```json
{
  "success": true,
  "message": "Datos de la empresa actualizados",
  "data": {
    "id": "uuid",
    "ruc": "20123456789",
    "razon_social": "Importaciones García SAC",
    "nombre_comercial": "García Imports Peru",
    "direccion": "Av. Larco 456, Miraflores",
    "ubigeo": "150101",
    "logo_url": "https://r2.example.com/logos/uuid/1234567890.png",
    "regimen_tributario": "RG"
  }
}
```

**Side effects**:
1. Registra en `audit_logs` (accion=empresa_actualizada, datos_anteriores, datos_nuevos)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol sin permiso (empleado, contador) | "No tienes permiso para modificar los datos de la empresa" |
| 422 | `regimen_tributario` inválido | "El régimen tributario debe ser RER, RG o RMT" |
| 422 | `ubigeo` no tiene 6 dígitos | "El ubigeo debe tener 6 dígitos" |

---

## POST /api/empresa/logo

Sube o reemplaza el logo de la empresa en Cloudflare R2.

**Roles permitidos**: `owner`, `admin`

### Request

`Content-Type: multipart/form-data`

```
logo: <archivo binario JPG o PNG, máx 2MB>
```

**Validaciones**:
- `logo`: required, file, mimes:jpg,jpeg,png, max:2048 (KB)

### Response 200

```json
{
  "success": true,
  "message": "Logo actualizado",
  "data": {
    "logo_url": "https://r2.example.com/logos/uuid/1709641234.png"
  }
}
```

**Side effects**:
1. Sube archivo a R2: `logos/{empresa_id}/{timestamp}.{ext}`
2. Si había logo anterior, elimina el objeto anterior de R2
3. Actualiza `empresas.logo_url`
4. Registra en `audit_logs` (accion=logo_actualizado)

### Errores

| HTTP | Condición | `message` |
|------|-----------|-----------|
| 403 | Rol sin permiso | "No tienes permiso para cambiar el logo" |
| 422 | Archivo mayor a 2MB | "El archivo no debe superar 2MB" |
| 422 | Formato no permitido | "Solo se aceptan archivos JPG y PNG" |
| 422 | Sin archivo | "Debes seleccionar un archivo" |
| 500 | Error de upload a R2 | "Error al subir el archivo. Intenta nuevamente" |
