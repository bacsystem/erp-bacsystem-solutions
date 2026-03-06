<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precio_historial', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('producto_id');
            $table->decimal('precio_anterior', 12, 4);
            $table->decimal('precio_nuevo', 12, 4);
            $table->uuid('usuario_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->nullOnDelete();
            $table->index(['producto_id', 'created_at'], 'idx_historial_producto');
            $table->index('producto_id', 'idx_historial_empresa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('precio_historial');
    }
};
