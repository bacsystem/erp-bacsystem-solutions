<?php

namespace App\Modules\Core\Producto\ImportarCSV;

use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportarProductosService
{
    private const REQUIRED_COLS = ['nombre', 'sku', 'unidad_medida_principal', 'precio_venta', 'igv_tipo', 'tipo', 'categoria_id'];

    public function preview(UploadedFile $file, string $empresaId): array
    {
        $rows  = $this->parseCsv($file);
        $filas = [];

        foreach ($rows as $i => $row) {
            $fila   = $i + 2; // line number (1-indexed + header)
            $errores = $this->validateRow($row, $empresaId);
            $filas[] = [
                'fila'    => $fila,
                'datos'   => $row,
                'errores' => $errores,
                'valido'  => empty($errores),
            ];
        }

        $token = (string) Str::uuid();
        Cache::put("import:{$token}", ['empresa_id' => $empresaId, 'filas' => $filas], now()->addMinutes(10));

        $validos  = count(array_filter($filas, fn($f) => $f['valido']));
        $errores  = count($filas) - $validos;

        return [
            'import_token' => $token,
            'total'        => count($filas),
            'validos'      => $validos,
            'errores'      => $errores,
            'filas'        => $filas,
        ];
    }

    public function confirmar(string $token, string $empresaId, ?string $usuarioId): array
    {
        $cached = Cache::get("import:{$token}");

        if (! $cached || $cached['empresa_id'] !== $empresaId) {
            throw ValidationException::withMessages([
                'import_token' => ['Token de importación inválido o expirado.'],
            ]);
        }

        Cache::forget("import:{$token}");

        $filasValidas = array_filter($cached['filas'], fn($f) => $f['valido']);
        $creados      = 0;

        DB::transaction(function () use ($filasValidas, $empresaId, &$creados) {
            foreach ($filasValidas as $fila) {
                $d = $fila['datos'];
                Producto::create([
                    'empresa_id'             => $empresaId,
                    'categoria_id'           => $d['categoria_id'],
                    'nombre'                 => $d['nombre'],
                    'sku'                    => $d['sku'],
                    'tipo'                   => $d['tipo'],
                    'unidad_medida_principal'=> $d['unidad_medida_principal'],
                    'precio_venta'           => $d['precio_venta'],
                    'igv_tipo'               => $d['igv_tipo'],
                    'descripcion'            => $d['descripcion'] ?? null,
                    'codigo_barras'          => $d['codigo_barras'] ?? null,
                    'precio_compra'          => $d['precio_compra'] ?? null,
                ]);
                $creados++;
            }
        });

        return ['creados' => $creados];
    }

    private function parseCsv(UploadedFile $file): array
    {
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = fgetcsv($handle);

        // Normalize headers
        $headers = array_map(fn($h) => trim(strtolower($h)), $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) continue;
            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);
        return $rows;
    }

    private function validateRow(array $row, string $empresaId): array
    {
        $validator = Validator::make($row, [
            'nombre'                  => 'required|string|max:255',
            'sku'                     => 'required|string|max:100',
            'unidad_medida_principal' => 'required|string|max:20',
            'precio_venta'            => 'required|numeric|min:0.01',
            'igv_tipo'                => 'required|in:gravado,exonerado,inafecto',
            'tipo'                    => 'required|in:simple,compuesto,servicio',
            'categoria_id'            => 'required|uuid|exists:categorias,id',
        ]);

        if ($validator->fails()) {
            return $validator->errors()->all();
        }

        // Check SKU uniqueness in empresa
        $exists = Producto::withoutGlobalScope('empresa')
            ->where('empresa_id', $empresaId)
            ->where('sku', $row['sku'])
            ->exists();

        if ($exists) {
            return ["El SKU '{$row['sku']}' ya existe en tu empresa."];
        }

        return [];
    }
}
