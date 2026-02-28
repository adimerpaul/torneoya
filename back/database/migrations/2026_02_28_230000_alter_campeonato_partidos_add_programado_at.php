<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonato_partidos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonato_partidos', 'programado_at')) {
                $table->dateTime('programado_at')->nullable()->after('campeonato_fecha_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonato_partidos', function (Blueprint $table) {
            if (Schema::hasColumn('campeonato_partidos', 'programado_at')) {
                $table->dropColumn('programado_at');
            }
        });
    }
};

