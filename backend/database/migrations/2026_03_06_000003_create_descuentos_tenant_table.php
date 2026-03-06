<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('descuentos_tenant', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->foreignUuid('superadmin_id')->constrained('superadmins');
            $table->string('tipo', 15);             // porcentaje | monto_fijo
            $table->decimal('valor', 8, 2);         // % o S/. según tipo
            $table->string('motivo', 255);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['empresa_id', 'activo']);
            $table->index('superadmin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('descuentos_tenant');
    }
};
