<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 20)->unique();         // starter|pyme|enterprise
            $table->string('nombre_display', 50);
            $table->decimal('precio_mensual', 8, 2);
            $table->unsignedInteger('max_usuarios')->nullable(); // null = ilimitado
            $table->jsonb('modulos');                        // ["facturacion","clientes",...]
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes');
    }
};
