<?php

namespace App\Modules\Core\Empresa\UploadLogo;

use App\Modules\Core\Models\AuditLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadLogoService
{
    public function execute(UploadedFile $file): string
    {
        $empresa   = auth()->user()->empresa;
        $timestamp = now()->timestamp;
        $ext       = $file->getClientOriginalExtension();
        $path      = "logos/{$empresa->id}/{$timestamp}.{$ext}";

        // Eliminar logo anterior si existe
        if ($empresa->logo_url) {
            $oldPath = ltrim(parse_url($empresa->logo_url, PHP_URL_PATH), '/');
            try {
                Storage::disk('r2')->delete($oldPath);
            } catch (\Exception) {
                // No bloquear la subida si el archivo anterior no se puede borrar
            }
        }

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()), 'public');
        $url = Storage::disk('r2')->url($path);

        $empresa->update(['logo_url' => $url]);

        AuditLog::registrar('logo_actualizado', [
            'tabla_afectada' => 'empresas',
            'registro_id'    => $empresa->id,
        ]);

        return $url;
    }
}
