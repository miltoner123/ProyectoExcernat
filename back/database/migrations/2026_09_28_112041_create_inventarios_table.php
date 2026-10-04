
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {

            $table->id('id_inventario');
            $table->unsignedBigInteger('id_ubicacion');
            $table->unsignedBigInteger('id_lote');
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->timestamps();

            // RELACIÓN CON UBICACIONES

            $table->foreign('id_ubicacion')
                ->references('id_ubicacion')
                ->on('ubicaciones')
                ->onDelete('restrict');


            // RELACIÓN CON LOTES

            $table->foreign('id_lote')
                ->references('id_lote')
                ->on('lotes')
                ->onDelete('restrict');


            // EVITAR INVENTARIOS DUPLICADOS

            $table->unique(
                ['id_ubicacion', 'id_lote'],
                'inventarios_ubicacion_lote_unique'
            );

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
