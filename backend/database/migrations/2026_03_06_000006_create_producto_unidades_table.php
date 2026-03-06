<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_unidades', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('producto_id');
            $table->string('unidad_medida', 20);
            $table->decimal('factor_conversion', 12, 6);
            $table->decimal('precio_venta', 12, 4)->nullable();
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->unique(['producto_id', 'unidad_medida'], 'unique_unidad_producto');
        });

        DB::statement('ALTER TABLE producto_unidades ADD CONSTRAINT chk_factor_conversion CHECK (factor_conversion > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_unidades');
    }
};
