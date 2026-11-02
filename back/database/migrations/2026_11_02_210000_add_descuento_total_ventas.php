<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('subtotal_bruto',12,2)->nullable();
            $table->decimal('descuento_total',12,2)->default(0);
        });
    }
    public function down(): void {
        Schema::table('ventas', fn(Blueprint $table) => $table->dropColumn(['subtotal_bruto','descuento_total']));
    }
};
