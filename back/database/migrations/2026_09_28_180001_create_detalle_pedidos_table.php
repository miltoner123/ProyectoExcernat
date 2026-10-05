<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {

            $table->id('id_detalle_pedido');

            $table->unsignedBigInteger(
                'id_pedido'
            );

            $table->unsignedBigInteger(
                'id_presentacion'
            );

            $table->integer(
                'cantidad_paquetes'
            )->default(0);

            $table->integer(
                'cantidad_unidades'
            )->default(0);

            $table->timestamps();


            $table->foreign('id_pedido')
                ->references('id_pedido')
                ->on('pedidos')
                ->onDelete('cascade');


            $table->foreign('id_presentacion')
                ->references('id_presentacion')
                ->on('presentaciones')
                ->onDelete('restrict');


            $table->unique(
                ['id_pedido', 'id_presentacion'],
                'detalle_pedido_presentacion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};