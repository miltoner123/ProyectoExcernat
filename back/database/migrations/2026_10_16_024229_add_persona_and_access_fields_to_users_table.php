<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id_persona')->nullable()->unique();
            $table->boolean('estado')->default(true);
            $table->timestamp('ultimo_acceso')->nullable();

            $table->foreign('id_persona')
                ->references('id_persona')
                ->on('personas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['id_persona']);
            $table->dropColumn([
                'id_persona',
                'estado',
                'ultimo_acceso'
            ]);
        });
    }
};