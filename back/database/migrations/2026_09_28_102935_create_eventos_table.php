<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id('id_evento');
            $table->string('nombre');
            $table->string('tipo', 50);
            $table->string('departamento', 100);
            $table->string('lugar');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedBigInteger('id_responsable')->nullable();
            $table->decimal('presupuesto_estimado', 12, 2)->nullable();
            $table->boolean('estado')->default(true);
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->foreign('id_responsable')->references('id_personal')->on('personal')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};