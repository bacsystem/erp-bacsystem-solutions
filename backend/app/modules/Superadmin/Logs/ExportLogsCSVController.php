<?php
namespace App\Modules\Superadmin\Logs;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ExportLogsCSVController {
    public function __invoke(Request $request): StreamedResponse {
        return (new ExportLogsCSVService())->execute($request);
    }
}
