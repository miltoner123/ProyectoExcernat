<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('pedidos', function (Blueprint $table) {

    $table->id('id_pedido');

    $table->unsignedBigInteger('id_ubicacion_solicitante');

    $table->string('tipo', 30);

    $table->string('estado', 30)
        ->default('PENDIENTE');

    $table->timestamp('fecha_pedido');

    $table->text('observacion')
        ->nullable();

    $table->timestamps();

    $table->foreign('id_ubicacion_solicitante')
        ->references('id_ubicacion')
        ->on('ubicaciones')
        ->onDelete('restrict');
});
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};