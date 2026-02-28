<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonato_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('autor_nombre', 120)->nullable();
            $table->text('mensaje');
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['campeonato_id', 'visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonato_mensajes');
    }
};

