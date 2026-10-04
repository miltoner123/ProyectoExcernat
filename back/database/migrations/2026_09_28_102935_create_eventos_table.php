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
            $table->string('tipo')->nullable();
            $table->string('departamento');
            $table->string('lugar')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado')->default('PLANIFICADO');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};