<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('auditoria_seguridad', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('usuario_afectado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 60);
            $table->string('entidad', 50);
            $table->string('entidad_id', 50)->nullable();
            $table->jsonb('anterior')->nullable();
            $table->jsonb('nuevo')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['entidad', 'entidad_id']);
            $table->index(['accion', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('auditoria_seguridad'); }
};
