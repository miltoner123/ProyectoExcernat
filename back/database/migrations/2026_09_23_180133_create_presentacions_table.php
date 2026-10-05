<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentaciones', function (Blueprint $table) {

            $table->id('id_presentacion');
            $table->string('codigo_sku')->unique();
            $table->string('nombre_presentacion');
            $table->decimal('peso_neto', 10, 2);
            $table->string('unidad_medida');
            $table->string('tipo_envase');
            $table->integer('unidades_por_paquete');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('precio_paquete', 10, 2)->nullable();
            $table->boolean('estado')->default(true);
            $table->foreignId('id_producto')->constrained('productos', 'id')->onDelete('restrict');
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentaciones');
    }
};