<?php

namespace Database\Seeders;

use App\Modules\Superadmin\Models\Superadmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        Superadmin::firstOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'admin@operaai.com')],
            [
                'nombre'   => env('SUPERADMIN_NOMBRE', 'Admin OperaAI'),
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'changeme')),
                'activo'   => true,
            ]
        );
    }
}
