<?php
namespace App\Modules\Core\Producto\GetQR;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
class GetQRController { public function __invoke(Request $r, string $id): Response { return response('stub'); } }
