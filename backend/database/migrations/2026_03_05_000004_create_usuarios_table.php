<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->string('nombre', 150);
            $table->string('email', 255)->unique();         // UNIQUE global — Opción A confirmada
            $table->string('password', 255);
            $table->string('rol', 10);                      // owner|admin|empleado|contador
            $table->boolean('activo')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            $table->index('empresa_id');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
