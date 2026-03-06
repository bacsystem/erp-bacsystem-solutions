<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        h1 { font-size: 18px; color: #333; }
        .empresa { font-size: 14px; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #4a90d9; color: white; padding: 8px; text-align: left; }
        td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background: #f5f5f5; }
        .badge-activo { color: #27ae60; font-weight: bold; }
        .badge-inactivo { color: #e74c3c; }
    </style>
</head>
<body>
    <h1>Catálogo de Productos</h1>
    <div class="empresa">{{ $empresa->razon_social ?? '' }}</div>
    <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Tipo</th>
                <th>Precio Venta</th>
                <th>IGV</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $producto)
            <tr>
                <td>{{ $producto->sku }}</td>
                <td>{{ $producto->nombre }}</td>
                <td>{{ $producto->categoria?->nombre ?? '-' }}</td>
                <td>{{ ucfirst($producto->tipo) }}</td>
                <td>S/ {{ number_format($producto->precio_venta, 2) }}</td>
                <td>{{ ucfirst($producto->igv_tipo) }}</td>
                <td class="{{ $producto->activo ? 'badge-activo' : 'badge-inactivo' }}">
                    {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:20px; font-size:10px; color:#999;">
        Total de productos: {{ count($productos) }}
    </p>
</body>
</html>
