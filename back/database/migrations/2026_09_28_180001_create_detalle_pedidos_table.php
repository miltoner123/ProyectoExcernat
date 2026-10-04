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

            $table->unsignedBigInteger('id_pedido');

            $table->unsignedBigInteger('id_lote');

            $table->integer('cantidad');

            $table->decimal('precio_unitario', 10, 2)->default(0);

            $table->timestamps();

            $table->foreign('id_pedido')
                ->references('id_pedido')
                ->on('pedidos')
                ->onDelete('cascade');

            $table->foreign('id_lote')
                ->references('id_lote')
                ->on('lotes')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};
