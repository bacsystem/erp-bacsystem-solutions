<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_componentes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('producto_id');
            $table->uuid('componente_id');
            $table->decimal('cantidad', 12, 4);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('producto_id')->references('id')->on('productos')->cascadeOnDelete();
            $table->foreign('componente_id')->references('id')->on('productos');
            $table->unique(['producto_id', 'componente_id'], 'unique_componente');
        });

        DB::statement('ALTER TABLE producto_componentes ADD CONSTRAINT chk_no_self_ref CHECK (producto_id != componente_id)');
        DB::statement('ALTER TABLE producto_componentes ADD CONSTRAINT chk_cantidad CHECK (cantidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_componentes');
    }
};
