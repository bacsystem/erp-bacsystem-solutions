# Contratos API: Planes

**Prefijo**: `/api`
**Autenticación**: Ninguna (ruta pública)

---

## GET /api/planes

Lista los 3 planes disponibles. Usado en la pantalla de selección de plan durante el registro.

### Request

Sin parámetros.

### Response 200

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nombre": "starter",
      "nombre_display": "Starter",
      "precio_mensual": "59.00",
      "max_usuarios": 3,
      "modulos": ["facturacion", "clientes", "productos"],
      "recomendado": false
    },
    {
      "id": "uuid",
      "nombre": "pyme",
      "nombre_display": "PYME",
      "precio_mensual": "129.00",
      "max_usuarios": 15,
      "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia"],
      "recomendado": true
    },
    {
      "id": "uuid",
      "nombre": "enterprise",
      "nombre_display": "Enterprise",
      "precio_mensual": "299.00",
      "max_usuarios": null,
      "modulos": ["facturacion", "clientes", "productos", "inventario", "crm", "finanzas", "ia", "rrhh"],
      "recomendado": false
    }
  ]
}
```

**Notas**:
- Solo retorna planes con `activo = true`
- `max_usuarios: null` = ilimitado
- `recomendado: true` solo para el plan `pyme` (lógica en el response, no en DB)
- Ordenados por `precio_mensual` ASC
