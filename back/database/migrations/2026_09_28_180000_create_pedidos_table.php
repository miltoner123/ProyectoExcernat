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

            $table->string('tipo', 30);

            $table->string('estado', 30)->default('PENDIENTE');

            $table->timestamp('fecha_pedido');

            $table->string('referencia', 100)->nullable();

            $table->text('observacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
