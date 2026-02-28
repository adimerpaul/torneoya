<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonatos', 'mensaje')) {
                $table->text('mensaje')->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('campeonatos', 'fecha_inicio')) {
                $table->date('fecha_inicio')->nullable()->after('mensaje');
            }
            if (!Schema::hasColumn('campeonatos', 'fecha_fin')) {
                $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campeonatos', function (Blueprint $table) {
            if (Schema::hasColumn('campeonatos', 'fecha_fin')) {
                $table->dropColumn('fecha_fin');
            }
            if (Schema::hasColumn('campeonatos', 'fecha_inicio')) {
                $table->dropColumn('fecha_inicio');
            }
            if (Schema::hasColumn('campeonatos', 'mensaje')) {
                $table->dropColumn('mensaje');
            }
        });
    }
};

