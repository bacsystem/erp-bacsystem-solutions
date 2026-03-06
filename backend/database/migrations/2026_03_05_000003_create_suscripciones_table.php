<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('empresa_id')->constrained('empresas');
            $table->foreignUuid('plan_id')->constrained('planes');
            $table->foreignUuid('downgrade_plan_id')->nullable()->constrained('planes');
            $table->string('estado', 10);                   // trial|activa|vencida|cancelada
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->date('fecha_proximo_cobro')->nullable();
            $table->date('fecha_cancelacion')->nullable();
            $table->string('culqi_subscription_id', 100)->nullable();
            $table->string('culqi_customer_id', 100)->nullable();   // ID cliente en Culqi
            $table->string('culqi_card_id', 100)->nullable();       // ID card token guardado en Culqi
            $table->string('card_last4', 4)->nullable();            // últimos 4 dígitos
            $table->string('card_brand', 20)->nullable();           // Visa, Mastercard, etc.
            $table->timestamps();
            $table->index('empresa_id');
            $table->index('estado');
            $table->index('fecha_vencimiento');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
