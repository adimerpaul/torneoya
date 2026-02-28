<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('campeonato_fase_id')->constrained('campeonato_fases')->cascadeOnDelete();
            $table->foreignId('campeonato_fecha_id')->nullable()->constrained('campeonato_fechas')->nullOnDelete();
            $table->foreignId('local_equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
            $table->foreignId('visita_equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
            $table->string('grupo_nombre', 120)->nullable();
            $table->unsignedTinyInteger('goles_local')->nullable();
            $table->unsignedTinyInteger('goles_visita')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente | jugado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_partidos');
    }
};

