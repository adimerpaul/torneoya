<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonato_mensajes', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_mensajes', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_grupos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_grupos', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Permite reutilizar nombres de grupo despues de soft delete.
        Schema::table('campeonato_grupos', function (Blueprint $table) {
            try {
                $table->index('campeonato_id', 'campeonato_grupos_campeonato_id_idx');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropUnique('campeonato_grupos_campeonato_id_nombre_unique');
            } catch (\Throwable $e) {
            }
            try {
                $table->unique(['campeonato_id', 'nombre', 'deleted_at'], 'campeonato_grupos_unique_active');
            } catch (\Throwable $e) {
            }
        });

        Schema::table('campeonato_jugadores', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_jugadores', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_fases', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_fases', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_fechas', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_fechas', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partidos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partidos', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_goles', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_goles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_tarjetas_amarillas', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_tarjetas_amarillas', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_tarjetas_rojas', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_tarjetas_rojas', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_faltas', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_faltas', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_sustituciones', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_sustituciones', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('campeonato_partido_porteros', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partido_porteros', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonato_partido_porteros', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_porteros', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partido_sustituciones', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_sustituciones', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partido_faltas', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_faltas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partido_tarjetas_rojas', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_tarjetas_rojas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partido_tarjetas_amarillas', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_tarjetas_amarillas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partido_goles', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partido_goles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_partidos', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partidos', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_fechas', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_fechas', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_fases', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_fases', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_jugadores', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_jugadores', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
        Schema::table('campeonato_grupos', function (Blueprint $table) {
            try {
                $table->dropUnique('campeonato_grupos_unique_active');
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex('campeonato_grupos_campeonato_id_idx');
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('campeonato_grupos', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            try {
                $table->unique(['campeonato_id', 'nombre'], 'campeonato_grupos_campeonato_id_nombre_unique');
            } catch (\Throwable $e) {
            }
        });
        Schema::table('campeonato_mensajes', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_mensajes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
