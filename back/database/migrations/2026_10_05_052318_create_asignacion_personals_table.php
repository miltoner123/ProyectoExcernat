<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_personal', function (Blueprint $table) {
            $table->id('id_asignacion');
            $table->unsignedBigInteger('id_personal');
            $table->unsignedBigInteger('id_ubicacion')->nullable();
            $table->unsignedBigInteger('id_evento')->nullable();
            $table->string('funcion_asignada', 100);
            $table->string('tipo_remuneracion', 30);
            $table->decimal('monto_fijo', 10, 2)->nullable();
            $table->decimal('comision_polvo', 10, 2)->nullable();
            $table->decimal('comision_granola', 10, 2)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('id_personal')->references('id_personal')->on('personal')->onDelete('restrict');
            $table->foreign('id_ubicacion')->references('id_ubicacion')->on('ubicaciones')->onDelete('restrict');
            $table->foreign('id_evento')->references('id_evento')->on('eventos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_personal');
    }
};
