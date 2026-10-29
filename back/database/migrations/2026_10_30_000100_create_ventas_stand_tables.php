<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('id_venta');
            $table->foreignId('id_ubicacion')->constrained('ubicaciones','id_ubicacion')->restrictOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_venta');
            $table->string('estado',20)->default('CONFIRMADA');
            $table->decimal('total',12,2);
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->index(['id_ubicacion','fecha_venta']);
        });
        Schema::create('detalle_ventas', function (Blueprint $table) {
            $table->id('id_detalle_venta');
            $table->foreignId('id_venta')->constrained('ventas','id_venta')->restrictOnDelete();
            $table->foreignId('id_lote')->constrained('lotes','id_lote')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario',12,2);
            $table->decimal('subtotal',12,2);
            $table->timestamps();
            $table->unique(['id_venta','id_lote']);
        });
    }
    public function down(): void { Schema::dropIfExists('detalle_ventas'); Schema::dropIfExists('ventas'); }
};
