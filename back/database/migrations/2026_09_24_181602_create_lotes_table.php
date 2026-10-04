<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {

            $table->id('id_lote');
            $table->unsignedBigInteger('id_presentacion');
            $table->string('codigo_lote', 100)->unique();
            $table->date('fecha_produccion');
            $table->date('fecha_vencimiento');
            $table->integer('cantidad_producida');
            $table->boolean('estado')->default(true);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->foreign('id_presentacion')->references('id_presentacion')->on('presentaciones')->onDelete('restrict');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};