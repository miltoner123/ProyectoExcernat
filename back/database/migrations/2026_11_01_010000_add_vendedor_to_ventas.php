<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('id_asignacion_vendedor')->nullable()
                ->constrained('asignaciones_personal', 'id_asignacion')->restrictOnDelete();
        });
    }
    public function down(): void {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_asignacion_vendedor');
        });
    }
};
