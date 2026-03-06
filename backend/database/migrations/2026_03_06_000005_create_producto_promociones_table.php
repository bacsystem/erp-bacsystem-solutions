<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_promociones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('producto_id');
            $table->string('nombre', 120);
            $table->enum('tipo', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor', 12, 4);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->index(['producto_id', 'activo'], 'idx_promociones_producto');
            $table->index(['producto_id', 'fecha_inicio', 'fecha_fin'], 'idx_promociones_vigencia');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE producto_promociones ADD CONSTRAINT chk_promo_valor CHECK (valor > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_promociones');
    }
};
