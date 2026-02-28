<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoPartido extends Model
{
    use HasFactory;

    protected $table = 'campeonato_partidos';

    protected $fillable = [
        'campeonato_id',
        'campeonato_fase_id',
        'campeonato_fecha_id',
        'local_equipo_id',
        'visita_equipo_id',
        'grupo_nombre',
        'goles_local',
        'goles_visita',
        'estado',
    ];

    public function fase()
    {
        return $this->belongsTo(CampeonatoFase::class, 'campeonato_fase_id');
    }

    public function fecha()
    {
        return $this->belongsTo(CampeonatoFecha::class, 'campeonato_fecha_id');
    }

    public function local()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'local_equipo_id');
    }

    public function visita()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'visita_equipo_id');
    }
}

