<?php

namespace Database\Seeders;

use App\Modules\Core\Models\Empresa;
use App\Modules\Core\Models\Plan;
use App\Modules\Core\Models\Suscripcion;
use App\Modules\Core\Models\Usuario;
use App\Modules\Core\Producto\Models\Categoria;
use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Empresa demo ──────────────────────────────────────────────────
        $empresa = Empresa::updateOrCreate(
            ['ruc' => '20123456789'],
            [
                'razon_social'       => 'Demo SAC',
                'nombre_comercial'   => 'Demo Store',
                'direccion'          => 'Av. Demo 123, Lima',
                'ubigeo'             => '150101',
                'regimen_tributario' => 'RG',
            ]
        );
        $empresaId = $empresa->id;

        // Limpiar datos de demo anteriores para re-seed limpio
        $usuarioIds = DB::table('usuarios')->where('empresa_id', $empresaId)->pluck('id');
        DB::table('audit_logs')->whereIn('usuario_id', $usuarioIds)->delete();
        $productoIds = DB::table('productos')->where('empresa_id', $empresaId)->pluck('id');
        DB::table('precio_historial')->whereIn('producto_id', $productoIds)->delete();
        DB::table('producto_componentes')->whereIn('producto_id', $productoIds)->delete();
        Producto::withoutGlobalScopes()->where('empresa_id', $empresaId)->delete();
        Categoria::withoutGlobalScopes()->where('empresa_id', $empresaId)->delete();
        Suscripcion::withoutGlobalScopes()->where('empresa_id', $empresaId)->delete();
        Usuario::withoutGlobalScopes()->where('empresa_id', $empresaId)->delete();

        // ── 2. Plan y suscripción ────────────────────────────────────────────
        $plan = Plan::where('nombre', 'pyme')->first()
            ?? Plan::where('nombre', 'starter')->first()
            ?? Plan::first();

        Suscripcion::create([
            'id'                  => (string) Str::uuid(),
            'empresa_id'          => $empresaId,
            'plan_id'             => $plan->id,
            'estado'              => 'activa',
            'fecha_inicio'        => today(),
            'fecha_vencimiento'   => today()->addMonth(),
            'fecha_proximo_cobro' => today()->addMonth(),
        ]);

        // ── 3. Owner demo ────────────────────────────────────────────────────
        Usuario::create([
            'id'         => (string) Str::uuid(),
            'empresa_id' => $empresaId,
            'nombre'     => 'Admin Demo',
            'email'      => 'demo@demo.com',
            'password'   => Hash::make('password'),
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        // ── 4. Categorías ────────────────────────────────────────────────────
        $categorias = [
            ['nombre' => 'Electrónica',    'hijos' => ['Laptops', 'Smartphones', 'Accesorios']],
            ['nombre' => 'Ropa',           'hijos' => ['Hombre', 'Mujer', 'Niños']],
            ['nombre' => 'Alimentación',   'hijos' => ['Lácteos', 'Bebidas', 'Snacks']],
            ['nombre' => 'Servicios',      'hijos' => []],
            ['nombre' => 'Herramientas',   'hijos' => ['Manuales', 'Eléctricas']],
        ];

        $catIds = [];
        foreach ($categorias as $cat) {
            $padre = Categoria::create([
                'id'         => (string) Str::uuid(),
                'empresa_id' => $empresaId,
                'nombre'     => $cat['nombre'],
            ]);
            $catIds[$cat['nombre']] = $padre->id;

            foreach ($cat['hijos'] as $hijo) {
                $sub = Categoria::create([
                    'id'                 => (string) Str::uuid(),
                    'empresa_id'         => $empresaId,
                    'nombre'             => $hijo,
                    'categoria_padre_id' => $padre->id,
                ]);
                $catIds[$hijo] = $sub->id;
            }
        }

        // ── 5. Productos ─────────────────────────────────────────────────────
        $productos = [
            // Electrónica
            ['nombre' => 'Laptop ASUS VivoBook 15',      'sku' => 'LAP-001', 'tipo' => 'simple',   'cat' => 'Laptops',      'compra' => 2200, 'venta' => 2899.90, 'igv' => 'gravado',   'barras' => '7501234567890'],
            ['nombre' => 'Laptop Lenovo IdeaPad 3',      'sku' => 'LAP-002', 'tipo' => 'simple',   'cat' => 'Laptops',      'compra' => 1800, 'venta' => 2399.00, 'igv' => 'gravado',   'barras' => null],
            ['nombre' => 'Smartphone Samsung Galaxy A54','sku' => 'CEL-001', 'tipo' => 'simple',   'cat' => 'Smartphones',  'compra' => 850,  'venta' => 1199.00, 'igv' => 'gravado',   'barras' => '7509876543210'],
            ['nombre' => 'Smartphone Xiaomi Redmi Note 12','sku'=>'CEL-002', 'tipo' => 'simple',   'cat' => 'Smartphones',  'compra' => 550,  'venta' => 799.00,  'igv' => 'gravado',   'barras' => null],
            ['nombre' => 'Audífonos Sony WH-1000XM4',    'sku' => 'ACC-001', 'tipo' => 'simple',   'cat' => 'Accesorios',   'compra' => 650,  'venta' => 899.00,  'igv' => 'gravado',   'barras' => null],
            ['nombre' => 'Mouse Logitech MX Master 3',   'sku' => 'ACC-002', 'tipo' => 'simple',   'cat' => 'Accesorios',   'compra' => 180,  'venta' => 249.90,  'igv' => 'gravado',   'barras' => null],
            ['nombre' => 'Kit Oficina Básico',            'sku' => 'KIT-001', 'tipo' => 'compuesto','cat' => 'Accesorios',   'compra' => null, 'venta' => 549.00,  'igv' => 'gravado',   'barras' => null],

            // Ropa
            ['nombre' => 'Polo Cuello Redondo Hombre',   'sku' => 'ROP-001', 'tipo' => 'simple',   'cat' => 'Hombre',       'compra' => 18,   'venta' => 39.90,   'igv' => 'exonerado', 'barras' => null],
            ['nombre' => 'Jeans Slim Fit Mujer',         'sku' => 'ROP-002', 'tipo' => 'simple',   'cat' => 'Mujer',        'compra' => 45,   'venta' => 89.90,   'igv' => 'exonerado', 'barras' => null],
            ['nombre' => 'Conjunto Deportivo Niños',     'sku' => 'ROP-003', 'tipo' => 'simple',   'cat' => 'Niños',        'compra' => 30,   'venta' => 59.90,   'igv' => 'exonerado', 'barras' => null],

            // Alimentación
            ['nombre' => 'Leche Gloria Entera 1L',       'sku' => 'ALI-001', 'tipo' => 'simple',   'cat' => 'Lácteos',      'compra' => 3.50, 'venta' => 5.50,    'igv' => 'exonerado', 'barras' => '7500123451234'],
            ['nombre' => 'Yogurt Laive Fresa 1kg',       'sku' => 'ALI-002', 'tipo' => 'simple',   'cat' => 'Lácteos',      'compra' => 6.00, 'venta' => 9.90,    'igv' => 'exonerado', 'barras' => null],
            ['nombre' => 'Gaseosa Inca Kola 3L',         'sku' => 'ALI-003', 'tipo' => 'simple',   'cat' => 'Bebidas',      'compra' => 4.20, 'venta' => 7.50,    'igv' => 'gravado',   'barras' => '7500435612340'],
            ['nombre' => 'Chips Pringles Original 149g', 'sku' => 'ALI-004', 'tipo' => 'simple',   'cat' => 'Snacks',       'compra' => 5.50, 'venta' => 9.90,    'igv' => 'gravado',   'barras' => null],

            // Servicios
            ['nombre' => 'Soporte Técnico por Hora',     'sku' => 'SRV-001', 'tipo' => 'servicio', 'cat' => 'Servicios',    'compra' => null, 'venta' => 120.00,  'igv' => 'gravado',   'barras' => null, 'unidad' => 'ZZ'],
            ['nombre' => 'Consultoría TI (día)',         'sku' => 'SRV-002', 'tipo' => 'servicio', 'cat' => 'Servicios',    'compra' => null, 'venta' => 850.00,  'igv' => 'gravado',   'barras' => null, 'unidad' => 'ZZ'],
            ['nombre' => 'Garantía Extendida 1 año',     'sku' => 'SRV-003', 'tipo' => 'servicio', 'cat' => 'Servicios',    'compra' => null, 'venta' => 199.00,  'igv' => 'gravado',   'barras' => null, 'unidad' => 'ZZ'],

            // Herramientas
            ['nombre' => 'Martillo Stanley 500g',        'sku' => 'HER-001', 'tipo' => 'simple',   'cat' => 'Manuales',     'compra' => 28,   'venta' => 49.90,   'igv' => 'gravado',   'barras' => null],
            ['nombre' => 'Taladro Percutor Bosch 500W',  'sku' => 'HER-002', 'tipo' => 'simple',   'cat' => 'Eléctricas',   'compra' => 180,  'venta' => 289.00,  'igv' => 'gravado',   'barras' => null],

            // Inactivo para probar estado
            ['nombre' => 'Producto Descontinuado',       'sku' => 'OLD-001', 'tipo' => 'simple',   'cat' => 'Electrónica',  'compra' => 50,   'venta' => 99.90,   'igv' => 'gravado',   'barras' => null, 'activo' => false],
        ];

        $productosCreados = [];
        foreach ($productos as $p) {
            $catId = $catIds[$p['cat']] ?? array_values($catIds)[0];
            $prod  = Producto::create([
                'id'                     => (string) Str::uuid(),
                'empresa_id'             => $empresaId,
                'categoria_id'           => $catId,
                'nombre'                 => $p['nombre'],
                'descripcion'            => null,
                'sku'                    => $p['sku'],
                'codigo_barras'          => $p['barras'] ?? null,
                'tipo'                   => $p['tipo'],
                'unidad_medida_principal'=> $p['unidad'] ?? 'NIU',
                'precio_compra'          => $p['compra'],
                'precio_venta'           => $p['venta'],
                'igv_tipo'               => $p['igv'],
                'activo'                 => $p['activo'] ?? true,
            ]);
            $productosCreados[$p['sku']] = $prod;
        }

        // ── 6. Componentes del kit ───────────────────────────────────────────
        if (isset($productosCreados['KIT-001'], $productosCreados['ACC-001'], $productosCreados['ACC-002'])) {
            DB::table('producto_componentes')->insert([
                ['id' => (string) Str::uuid(), 'producto_id' => $productosCreados['KIT-001']->id, 'componente_id' => $productosCreados['ACC-001']->id, 'cantidad' => 1],
                ['id' => (string) Str::uuid(), 'producto_id' => $productosCreados['KIT-001']->id, 'componente_id' => $productosCreados['ACC-002']->id, 'cantidad' => 1],
            ]);
        }

        // ── 7. Historial de precios (algunos productos) ──────────────────────
        $historial = [
            ['sku' => 'LAP-001', 'cambios' => [[2000, 2499.90, '-60 days'], [2499.90, 2699.00, '-30 days'], [2699.00, 2899.90, '-5 days']]],
            ['sku' => 'CEL-001', 'cambios' => [[999,  1099.00, '-45 days'], [1099.00, 1199.00, '-15 days']]],
            ['sku' => 'ROP-001', 'cambios' => [[32.90, 39.90,  '-20 days']]],
            ['sku' => 'ALI-003', 'cambios' => [[6.50,  7.00,   '-90 days'], [7.00, 7.50, '-30 days']]],
        ];

        foreach ($historial as $h) {
            if (! isset($productosCreados[$h['sku']])) continue;
            $prodId = $productosCreados[$h['sku']]->id;
            foreach ($h['cambios'] as [$ant, $nuevo, $dias]) {
                DB::table('precio_historial')->insert([
                    'id'              => (string) Str::uuid(),
                    'producto_id'     => $prodId,
                    'precio_anterior' => $ant,
                    'precio_nuevo'    => $nuevo,
                    'usuario_id'      => null,
                    'created_at'      => now()->modify($dias),
                ]);
            }
        }

        $this->command->info('✓ Demo creado — email: demo@demo.com / password: password');

        // ── Empresa 2: suscripción vencida (estado=vencida, venció ayer) ─────
        $empresa2 = Empresa::updateOrCreate(
            ['ruc' => '20999888777'],
            [
                'razon_social'       => 'Vencida SAC',
                'nombre_comercial'   => 'Vencida Store',
                'direccion'          => 'Av. Expirada 999, Lima',
                'ubigeo'             => '150101',
                'regimen_tributario' => 'RG',
            ]
        );
        $empresa2Id = $empresa2->id;

        $usuario2Ids = DB::table('usuarios')->where('empresa_id', $empresa2Id)->pluck('id');
        DB::table('audit_logs')->whereIn('usuario_id', $usuario2Ids)->delete();
        Suscripcion::withoutGlobalScopes()->where('empresa_id', $empresa2Id)->delete();
        Usuario::withoutGlobalScopes()->where('empresa_id', $empresa2Id)->delete();

        Suscripcion::create([
            'id'                  => (string) Str::uuid(),
            'empresa_id'          => $empresa2Id,
            'plan_id'             => $plan->id,
            'estado'              => 'vencida',          // estado explícito: bloqueada
            'fecha_inicio'        => today()->subYear(),
            'fecha_vencimiento'   => today()->subDay(),  // venció ayer
            'fecha_proximo_cobro' => today()->subDay(),
        ]);

        Usuario::create([
            'id'         => (string) Str::uuid(),
            'empresa_id' => $empresa2Id,
            'nombre'     => 'Admin Vencida',
            'email'      => 'vencida@demo.com',
            'password'   => Hash::make('password'),
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        $this->command->info('✓ Empresa vencida creada — email: vencida@demo.com / password: password');

        // ── Empresa 3: suscripción cancelada (bloqueada total) ───────────────
        $empresa3 = Empresa::updateOrCreate(
            ['ruc' => '20111222333'],
            [
                'razon_social'       => 'Cancelada SAC',
                'nombre_comercial'   => 'Cancelada Store',
                'direccion'          => 'Av. Cancelada 111, Lima',
                'ubigeo'             => '150101',
                'regimen_tributario' => 'RG',
            ]
        );
        $empresa3Id = $empresa3->id;

        $usuario3Ids = DB::table('usuarios')->where('empresa_id', $empresa3Id)->pluck('id');
        DB::table('audit_logs')->whereIn('usuario_id', $usuario3Ids)->delete();
        Suscripcion::withoutGlobalScopes()->where('empresa_id', $empresa3Id)->delete();
        Usuario::withoutGlobalScopes()->where('empresa_id', $empresa3Id)->delete();

        Suscripcion::create([
            'id'                  => (string) Str::uuid(),
            'empresa_id'          => $empresa3Id,
            'plan_id'             => $plan->id,
            'estado'              => 'cancelada',         // bloqueada total
            'fecha_inicio'        => today()->subYear(),
            'fecha_vencimiento'   => today()->subDays(10),
            'fecha_proximo_cobro' => today()->subDays(10),
            'fecha_cancelacion'   => today()->subDays(3),
        ]);

        Usuario::create([
            'id'         => (string) Str::uuid(),
            'empresa_id' => $empresa3Id,
            'nombre'     => 'Admin Cancelada',
            'email'      => 'cancelada@demo.com',
            'password'   => Hash::make('password'),
            'rol'        => 'owner',
            'activo'     => true,
        ]);

        $this->command->info('✓ Empresa cancelada creada — email: cancelada@demo.com / password: password');
    }
}
