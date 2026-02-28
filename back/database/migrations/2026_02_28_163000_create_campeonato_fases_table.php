<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_fases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->string('tipo', 40)->default('liga');
            $table->unsignedInteger('orden')->default(1);
            $table->boolean('agrupar_por_grupo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_fases');
    }
};

