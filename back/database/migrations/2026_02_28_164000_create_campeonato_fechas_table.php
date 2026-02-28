<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_fechas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_fase_id')->constrained('campeonato_fases')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_fechas');
    }
};

