<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ubicaciones', function (Blueprint $table) {

            $table->id('id_ubicacion');
            $table->unsignedBigInteger('id_evento')->nullable();
            $table->string('nombre');
            $table->string('tipo', 30);
            $table->string('propiedad', 30);
            $table->string('departamento', 100);
            $table->string('direccion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_evento')
                ->references('id_evento')
                ->on('eventos')
                ->onDelete('restrict');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};