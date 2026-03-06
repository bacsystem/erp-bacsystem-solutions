<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // RLS only applies to PostgreSQL — skip gracefully on SQLite (local dev)
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // empresas filtra por su propia PK (id = empresa_id del contexto)
        // WITH CHECK true → permite INSERT sin empresa_id en contexto (registro inicial)
        DB::statement("ALTER TABLE empresas ENABLE ROW LEVEL SECURITY");
        DB::statement("ALTER TABLE empresas FORCE ROW LEVEL SECURITY");
        DB::statement("
            CREATE POLICY tenant_isolation ON empresas
            AS PERMISSIVE FOR ALL
            USING (
                CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                     THEN true
                     ELSE id = current_setting('app.empresa_id', true)::uuid
                END
            )
            WITH CHECK (true)
        ");

        // Las demás tablas llevan empresa_id como FK
        $tables = ['suscripciones', 'usuarios', 'invitaciones_usuario', 'audit_logs'];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                AS PERMISSIVE FOR ALL
                USING (
                    CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                         THEN true
                         ELSE empresa_id = current_setting('app.empresa_id', true)::uuid
                    END
                )
                WITH CHECK (
                    CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                         THEN true
                         ELSE empresa_id = current_setting('app.empresa_id', true)::uuid
                    END
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tables = ['empresas', 'suscripciones', 'usuarios', 'invitaciones_usuario', 'audit_logs'];
        foreach ($tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
