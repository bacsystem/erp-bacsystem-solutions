<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE categorias ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE productos ENABLE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY tenant_isolation ON categorias
            USING (empresa_id::text = current_setting('app.empresa_id', true))
        ");

        DB::statement("
            CREATE POLICY tenant_isolation ON productos
            USING (empresa_id::text = current_setting('app.empresa_id', true))
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON categorias');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON productos');
        DB::statement('ALTER TABLE categorias DISABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE productos DISABLE ROW LEVEL SECURITY');
    }
};
