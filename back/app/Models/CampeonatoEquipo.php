<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampeonatoEquipo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'campeonato_id',
        'campeonato_grupo_id',
        'nombre',
        'imagen',
        'entrenador',
    ];

    protected $appends = ['grupo_nombre'];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function grupo()
    {
        return $this->belongsTo(CampeonatoGrupo::class, 'campeonato_grupo_id');
    }

    public function jugadores()
    {
        return $this->hasMany(CampeonatoJugador::class, 'campeonato_equipo_id')->orderBy('nombre');
    }

    public function getGrupoNombreAttribute(): ?string
    {
        return $this->grupo?->nombre;
    }
}

