<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('ventas', function(Blueprint $table) {
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('fecha_anulacion')->nullable();
            $table->foreignId('id_usuario_anulacion')->nullable()->constrained('users')->restrictOnDelete();
        });
    }
    public function down(): void {
        Schema::table('ventas', function(Blueprint $table) {
            $table->dropForeign(['id_usuario_anulacion']);
            $table->dropColumn(['motivo_anulacion','fecha_anulacion','id_usuario_anulacion']);
        });
    }
};
