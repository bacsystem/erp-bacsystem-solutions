<?php

namespace Tests\Feature\Superadmin\Helpers;

use App\Modules\Superadmin\Models\Superadmin;

trait SuperadminHelper
{
    protected function actingAsSuperadmin(): array
    {
        $superadmin = Superadmin::factory()->create();
        $token = $superadmin->createToken('superadmin', ['*'], now()->addHours(4))->plainTextToken;

        return [$superadmin, $token];
    }
}
