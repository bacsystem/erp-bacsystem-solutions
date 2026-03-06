<?php

namespace App\Modules\Core\Producto\GetQR;

use App\Modules\Core\Producto\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class GetQRController
{
    public function __invoke(Request $request, string $producto): Response
    {
        $productoModel = Producto::findOrFail($producto);

        $data = json_encode([
            'sku'    => $productoModel->sku,
            'nombre' => $productoModel->nombre,
            'id'     => $productoModel->id,
        ]);

        $qr = QrCode::format('svg')->size(200)->generate($data);

        return response($qr, 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
}
