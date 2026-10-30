<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('detalle_movimiento', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_recibida')->nullable();
            $table->text('observacion_recepcion')->nullable();
        });
    }
    public function down(): void {
        Schema::table('detalle_movimiento', function (Blueprint $table) {
            $table->dropColumn(['cantidad_recibida','observacion_recepcion']);
        });
    }
};
