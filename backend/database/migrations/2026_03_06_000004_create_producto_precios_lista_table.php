<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_precios_lista', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('producto_id');
            $table->enum('lista', ['L1', 'L2', 'L3']);
            $table->string('nombre_lista', 60)->default('Lista');
            $table->decimal('precio', 12, 4);
            $table->timestamps();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->unique(['producto_id', 'lista'], 'unique_precio_lista');
        });

        DB::statement('ALTER TABLE producto_precios_lista ADD CONSTRAINT chk_precio_lista CHECK (precio > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_precios_lista');
    }
};
