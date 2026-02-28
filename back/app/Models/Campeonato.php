<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campeonato extends Model
{
    use SoftDeletes;

    protected $table = 'campeonatos';

    protected $fillable = [
        'user_id',
        'nombre',
        'tipo',
        'fase_formato',
        'deporte_id',
        'descripcion',
        'fecha_inicio',
        'fecha_inscripcion',
        'color_primario',
        'color_secundario',
        'logo',
        'portada',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_inscripcion' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deporte(): BelongsTo
    {
        return $this->belongsTo(Deporte::class);
    }

    public function categorias(): HasMany
    {
        return $this->hasMany(CategoriaCampeonato::class);
    }
}
