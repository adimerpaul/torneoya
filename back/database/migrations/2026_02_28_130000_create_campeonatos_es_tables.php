<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deportes', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('icono')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('campeonatos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nombre');
            $table->enum('tipo', ['unico', 'categorias']);
            $table->string('fase_formato')->nullable();
            $table->foreignId('deporte_id')->nullable()->constrained('deportes')->nullOnDelete();
            $table->text('descripcion')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_inscripcion')->nullable();
            $table->string('color_primario', 20)->nullable();
            $table->string('color_secundario', 20)->nullable();
            $table->string('logo')->nullable();
            $table->string('portada')->nullable();
            $table->enum('estado', ['borrador', 'publicado', 'en_juego', 'finalizado', 'cancelado'])->default('borrador');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categoria_campeonatos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campeonato_id')->constrained('campeonatos')->cascadeOnDelete();
            $table->foreignId('deporte_id')->constrained('deportes')->restrictOnDelete();
            $table->string('nombre');
            $table->string('formato')->nullable();
            $table->enum('estado', ['borrador', 'publicado', 'en_juego', 'finalizado', 'cancelado'])->default('borrador');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_campeonatos');
        Schema::dropIfExists('campeonatos');
        Schema::dropIfExists('deportes');
    }
};
