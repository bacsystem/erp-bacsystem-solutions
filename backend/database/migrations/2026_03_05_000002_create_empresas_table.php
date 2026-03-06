<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ruc', 11)->unique();
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200);
            $table->text('direccion')->nullable();
            $table->string('ubigeo', 6)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('regimen_tributario', 3);        // RER|RG|RMT
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};
