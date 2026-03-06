<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('empresa_id');
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->uuid('categoria_padre_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('categoria_padre_id')->references('id')->on('categorias')->nullOnDelete();

            $table->index('empresa_id', 'idx_categorias_empresa');
            $table->index('categoria_padre_id', 'idx_categorias_padre');
            $table->unique(['empresa_id', 'nombre', 'categoria_padre_id'], 'unique_categoria_nombre_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
