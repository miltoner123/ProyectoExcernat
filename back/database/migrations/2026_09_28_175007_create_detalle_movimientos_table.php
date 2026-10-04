
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_movimiento', function (Blueprint $table) {

            $table->id('id_detalle_movimiento');

            $table->unsignedBigInteger('id_movimiento');

            $table->unsignedBigInteger('id_lote');

            // Positivo normalmente; negativo solo para ajustes.
            $table->integer('cantidad');

            $table->timestamps();

            $table->foreign('id_movimiento')
                ->references('id_movimiento')
                ->on('movimientos_inventario')
                ->onDelete('restrict');

            $table->foreign('id_lote')
                ->references('id_lote')
                ->on('lotes')
                ->onDelete('restrict');

            $table->unique(
                ['id_movimiento', 'id_lote'],
                'detalle_movimiento_lote_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_movimiento');
    }
};
