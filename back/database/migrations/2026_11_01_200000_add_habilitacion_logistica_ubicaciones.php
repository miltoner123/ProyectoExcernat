<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up(): void {
  Schema::table('ubicaciones',function(Blueprint $t){
   $t->string('estado_inventario',24)->default('PENDIENTE_VALIDACION');
   $t->unsignedBigInteger('habilitado_por')->nullable();
   $t->timestamp('habilitado_en')->nullable();
   $t->text('observacion_habilitacion')->nullable();
  });
  // Mantener operativas todas las ubicaciones preexistentes y su historial.
  DB::table('ubicaciones')->update(['estado_inventario'=>'HABILITADO']);
 }
 public function down(): void {Schema::table('ubicaciones',fn(Blueprint $t)=>$t->dropColumn(['estado_inventario','habilitado_por','habilitado_en','observacion_habilitacion']));}
};
