<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('ubicaciones', 'ubicacion_especifica')) {
            Schema::table('ubicaciones', function (Blueprint $table) {
                $table->string('ubicacion_especifica', 255)->nullable();
            });
        }
    }
    public function down(): void {
        if (Schema::hasColumn('ubicaciones', 'ubicacion_especifica')) {
            Schema::table('ubicaciones', fn(Blueprint $table) => $table->dropColumn('ubicacion_especifica'));
        }
    }
};
