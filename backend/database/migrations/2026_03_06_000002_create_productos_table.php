<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('empresa_id');
            $table->uuid('categoria_id');
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('sku', 100);
            $table->string('codigo_barras', 50)->nullable();
            $table->enum('tipo', ['simple', 'compuesto', 'servicio'])->default('simple');
            $table->string('unidad_medida_principal', 20);
            $table->decimal('precio_compra', 12, 4)->nullable();
            $table->decimal('precio_venta', 12, 4);
            $table->enum('igv_tipo', ['gravado', 'exonerado', 'inafecto'])->default('gravado');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('empresa_id')->references('id')->on('empresas')->cascadeOnDelete();
            $table->foreign('categoria_id')->references('id')->on('categorias');

            $table->unique(['empresa_id', 'sku'], 'unique_sku_empresa');
            $table->index('empresa_id', 'idx_productos_empresa');
            $table->index('categoria_id', 'idx_productos_categoria');
            $table->index(['empresa_id', 'activo'], 'idx_productos_activo');
            $table->index(['empresa_id', 'codigo_barras'], 'idx_productos_codigo_barras');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_precio_venta CHECK (precio_venta > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
