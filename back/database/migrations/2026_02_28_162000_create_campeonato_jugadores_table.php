<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_jugadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_equipo_id')->constrained('campeonato_equipos')->cascadeOnDelete();
            $table->string('nombre', 160);
            $table->string('abreviado', 40)->nullable();
            $table->string('foto')->nullable();
            $table->string('posicion', 80)->nullable();
            $table->string('numero_camiseta', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('documento', 60)->nullable();
            $table->string('celular', 30)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_jugadores');
    }
};

