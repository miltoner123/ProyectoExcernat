<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->id('id_persona');
            $table->string('tipo_documento', 30)->nullable();
            $table->string('numero_documento', 30)->nullable()->unique();
            $table->string('nombres_razon_social');
            $table->string('apellidos')->nullable();
            $table->string('correo')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('sexo', 20)->nullable();
            $table->string('tipo_persona', 30);
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};