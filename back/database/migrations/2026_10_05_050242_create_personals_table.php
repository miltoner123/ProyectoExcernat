<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id('id_personal');
            $table->unsignedBigInteger('id_persona')->unique();
            $table->unsignedBigInteger('id_cargo');
            $table->string('codigo_empleado', 50)->unique();
            $table->date('fecha_ingreso');
            $table->string('tipo_contrato', 50)->nullable();
            $table->decimal('salario_base', 10, 2)->default(0);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_persona')->references('id_persona')->on('personas')->onDelete('restrict');
            $table->foreign('id_cargo')->references('id_cargo')->on('cargos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
