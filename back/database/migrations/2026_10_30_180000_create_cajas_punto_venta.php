<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('cajas_punto_venta',function(Blueprint $t){
   $t->id('id_caja');$t->foreignId('id_ubicacion')->constrained('ubicaciones','id_ubicacion')->restrictOnDelete();
   $t->foreignId('id_usuario_apertura')->constrained('users')->restrictOnDelete();
   $t->foreignId('id_usuario_cierre')->nullable()->constrained('users')->restrictOnDelete();
   $t->timestamp('fecha_apertura');$t->timestamp('fecha_cierre')->nullable();
   $t->decimal('monto_inicial',12,2);$t->decimal('efectivo_contado',12,2)->nullable();
   $t->decimal('efectivo_esperado',12,2)->nullable();$t->decimal('diferencia',12,2)->nullable();
   $t->string('estado',20)->default('ABIERTA');$t->text('observacion')->nullable();$t->timestamps();
   $t->index(['id_ubicacion','estado']);
  });
  Schema::table('cobros_ventas',function(Blueprint $t){$t->foreignId('id_caja')->nullable()->constrained('cajas_punto_venta','id_caja')->restrictOnDelete();});
 }
 public function down(): void {Schema::table('cobros_ventas',fn(Blueprint $t)=>$t->dropConstrainedForeignId('id_caja'));Schema::dropIfExists('cajas_punto_venta');}
};
