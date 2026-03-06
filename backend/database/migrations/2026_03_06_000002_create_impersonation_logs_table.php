<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('superadmin_id')->constrained('superadmins');
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->string('token_hash', 64);       // SHA-256 del token temporal — nunca el token completo
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('ip', 45);               // soporta IPv6
            $table->index('superadmin_id');
            $table->index('empresa_id');
            $table->index('started_at');
        });

        // Índice único parcial: previene que el mismo superadmin tenga
        // 2 sesiones activas de impersonación para la misma empresa.
        // Blueprint no soporta WHERE nativo — se usa DB::statement.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                CREATE UNIQUE INDEX uq_impersonation_activa
                ON impersonation_logs (empresa_id, superadmin_id)
                WHERE ended_at IS NULL
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS uq_impersonation_activa');
        }
        Schema::dropIfExists('impersonation_logs');
    }
};
