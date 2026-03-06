<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->foreignUuid('usuario_id')->nullable()->constrained('usuarios');
            $table->string('accion', 50);
            $table->string('tabla_afectada', 50)->nullable();
            $table->uuid('registro_id')->nullable();
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();
            $table->string('ip', 45);
            $table->timestamp('created_at');
            $table->index('empresa_id');
            $table->index('usuario_id');
            $table->index('accion');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
