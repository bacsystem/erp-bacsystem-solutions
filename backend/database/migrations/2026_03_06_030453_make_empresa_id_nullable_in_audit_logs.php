<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignUuid('empresa_id')->nullable()->change();
            });
            return;
        }

        // PostgreSQL: drop RLS policy, alter column, recreate policy
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON audit_logs');

        DB::statement('ALTER TABLE audit_logs ALTER COLUMN empresa_id DROP NOT NULL');

        // Recreate: allow NULL empresa_id (system-level events with no tenant)
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
                $table->foreignUuid('empresa_id')->nullable(false)->change();
            });
            return;
        }

        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON audit_logs');
        DB::statement('ALTER TABLE audit_logs ALTER COLUMN empresa_id SET NOT NULL');
        DB::statement("
            CREATE POLICY tenant_isolation ON audit_logs
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
};
