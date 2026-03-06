<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('producto_id');
            $table->text('url');
            $table->text('path_r2')->nullable();
            $table->smallInteger('orden')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->index('producto_id', 'idx_imagenes_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_imagenes');
    }
};
