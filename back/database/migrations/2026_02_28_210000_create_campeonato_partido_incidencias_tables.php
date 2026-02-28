<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campeonato_partido_goles')) {
            Schema::create('campeonato_partido_goles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->string('detalle', 180)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campeonato_partido_tarjetas_amarillas')) {
            Schema::create('campeonato_partido_tarjetas_amarillas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->string('detalle', 180)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campeonato_partido_tarjetas_rojas')) {
            Schema::create('campeonato_partido_tarjetas_rojas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->string('detalle', 180)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campeonato_partido_faltas')) {
            Schema::create('campeonato_partido_faltas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->string('detalle', 180)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campeonato_partido_sustituciones')) {
            Schema::create('campeonato_partido_sustituciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_sale_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->foreignId('jugador_entra_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->unsignedSmallInteger('minuto')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('campeonato_partido_porteros')) {
            Schema::create('campeonato_partido_porteros', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campeonato_partido_id')->constrained('campeonato_partidos')->cascadeOnDelete();
                $table->foreignId('equipo_id')->nullable()->constrained('campeonato_equipos')->nullOnDelete();
                $table->foreignId('jugador_id')->nullable()->constrained('campeonato_jugadores')->nullOnDelete();
                $table->string('nombre_portero', 180)->nullable();
                $table->boolean('titular')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_partido_porteros');
        Schema::dropIfExists('campeonato_partido_sustituciones');
        Schema::dropIfExists('campeonato_partido_faltas');
        Schema::dropIfExists('campeonato_partido_tarjetas_rojas');
        Schema::dropIfExists('campeonato_partido_tarjetas_amarillas');
        Schema::dropIfExists('campeonato_partido_goles');
    }
};
