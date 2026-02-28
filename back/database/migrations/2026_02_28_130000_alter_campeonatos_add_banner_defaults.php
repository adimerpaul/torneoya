<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campeonatos')) {
            return;
        }

        Schema::table('campeonatos', function (Blueprint $table) {
            if (!Schema::hasColumn('campeonatos', 'banner')) {
                $table->string('banner')->default('torneoBanner.jpg')->after('imagen');
            }
        });

        DB::table('campeonatos')
            ->where(function ($q) {
                $q->whereNull('imagen')->orWhere('imagen', 'default.png');
            })
            ->update(['imagen' => 'torneoImagen.jpg']);

        DB::table('campeonatos')
            ->where(function ($q) {
                $q->whereNull('banner')->orWhere('banner', 'default.png');
            })
            ->update(['banner' => 'torneoBanner.jpg']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('campeonatos')) {
            return;
        }

        Schema::table('campeonatos', function (Blueprint $table) {
            if (Schema::hasColumn('campeonatos', 'banner')) {
                $table->dropColumn('banner');
            }
        });
    }
};
