<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('movimientos_inventario', function(Blueprint $table) {
   $table->string('estado_recepcion', 25)->nullable(); // NULL = movimiento histórico
   $table->foreignId('id_usuario_receptor')->nullable()->constrained('users')->restrictOnDelete();
   $table->timestamp('fecha_recepcion')->nullable();
  });
 }
 public function down(): void { Schema::table('movimientos_inventario', function(Blueprint $table) {
  $table->dropConstrainedForeignId('id_usuario_receptor');
  $table->dropColumn(['estado_recepcion','fecha_recepcion']);
 }); }
};
