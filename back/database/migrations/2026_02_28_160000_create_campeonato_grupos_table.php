<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->timestamps();

            $table->unique(['campeonato_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_grupos');
    }
};

