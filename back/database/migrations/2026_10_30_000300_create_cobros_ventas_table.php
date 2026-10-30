<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cobros_ventas', function (Blueprint $table) {
            $table->id('id_cobro');
            $table->foreignId('id_venta')->constrained('ventas','id_venta')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->decimal('monto',12,2);
            $table->string('metodo_pago',20);
            $table->string('referencia',120)->nullable();
            $table->string('clave_operacion',100)->nullable()->unique();
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_cobro');
            $table->timestamps();
            $table->index(['id_venta','fecha_cobro']);
        });
    }
    public function down(): void { Schema::dropIfExists('cobros_ventas'); }
};
