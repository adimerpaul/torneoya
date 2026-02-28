<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campeonatos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('campeonatos')->cascadeOnDelete();
            $table->string('nombre', 180);
            $table->enum('tipo', ['unico', 'categorias', 'categoria_item'])->default('unico');
            $table->string('deporte', 40)->nullable();
            $table->string('codigo', 8)->unique();
            $table->string('imagen')->default('torneoImagen.jpg');
            $table->string('banner')->default('torneoBanner.jpg');
            $table->text('descripcion')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeonatos');
    }
};
