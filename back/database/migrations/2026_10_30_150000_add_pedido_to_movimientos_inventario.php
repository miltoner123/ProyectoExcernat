<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('id_pedido')->nullable()->constrained('pedidos', 'id_pedido')->restrictOnDelete();
            $table->index(['id_pedido', 'tipo']);
        });
    }
    public function down(): void {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['id_pedido']);
            $table->dropIndex(['id_pedido', 'tipo']);
            $table->dropColumn('id_pedido');
        });
    }
};
