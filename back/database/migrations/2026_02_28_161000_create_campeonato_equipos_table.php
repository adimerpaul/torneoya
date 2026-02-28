<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('campeonato_grupo_id')->nullable()->constrained('campeonato_grupos')->nullOnDelete();
            $table->string('nombre', 160);
            $table->string('imagen')->nullable();
            $table->string('entrenador', 160)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_equipos');
    }
};

