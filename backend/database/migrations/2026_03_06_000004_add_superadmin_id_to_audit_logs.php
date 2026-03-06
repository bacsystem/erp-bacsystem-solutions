<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignUuid('superadmin_id')->nullable()->constrained('superadmins')->after('usuario_id');
                $table->index('superadmin_id');
            });
            return;
        }

        // PostgreSQL: la policy RLS referencia la tabla audit_logs.
        // Debemos drop + alter + recrear para agregar la columna sin conflicto.
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON audit_logs');

        DB::statement('
            ALTER TABLE audit_logs
            ADD COLUMN superadmin_id uuid NULL
            REFERENCES superadmins(id)
        ');

        DB::statement('CREATE INDEX audit_logs_superadmin_id_index ON audit_logs (superadmin_id)');

        // Recrear la policy con la misma lógica — superadmin_id no afecta el filtro de tenant
        DB::statement("
            CREATE POLICY tenant_isolation ON audit_logs
            AS PERMISSIVE FOR ALL
            USING (
                CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                     THEN true
                     ELSE empresa_id IS NULL
                       OR empresa_id = current_setting('app.empresa_id', true)::uuid
                END
            )
            WITH CHECK (
                CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                     THEN true
                     ELSE empresa_id IS NULL
                       OR empresa_id = current_setting('app.empresa_id', true)::uuid
                END
            )
        ");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropForeign(['superadmin_id']);
                $table->dropColumn('superadmin_id');
            });
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON audit_logs');
        DB::statement('DROP INDEX IF EXISTS audit_logs_superadmin_id_index');
        DB::statement('ALTER TABLE audit_logs DROP COLUMN IF EXISTS superadmin_id');

        DB::statement("
            CREATE POLICY tenant_isolation ON audit_logs
            AS PERMISSIVE FOR ALL
            USING (
                CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                     THEN true
                     ELSE empresa_id IS NULL
                       OR empresa_id = current_setting('app.empresa_id', true)::uuid
                END
            )
            WITH CHECK (
                CASE WHEN coalesce(current_setting('app.empresa_id', true), '') = ''
                     THEN true
                     ELSE empresa_id IS NULL
                       OR empresa_id = current_setting('app.empresa_id', true)::uuid
                END
            )
        ");
    }
};
