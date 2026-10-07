
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {

            $table->id('id_movimiento');

            $table->string('tipo', 30);

            $table->unsignedBigInteger('id_ubicacion_origen')->nullable();

            $table->unsignedBigInteger('id_ubicacion_destino')->nullable();

            $table->timestamp('fecha_movimiento');

            $table->string('referencia', 100)->nullable();

            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->foreign('id_ubicacion_origen')
                ->references('id_ubicacion')
                ->on('ubicaciones')
                ->onDelete('restrict');

            $table->foreign('id_ubicacion_destino')
                ->references('id_ubicacion')
                ->on('ubicaciones')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};

