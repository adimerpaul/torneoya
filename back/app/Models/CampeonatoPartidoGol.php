<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoPartidoGol extends Model
{
    use HasFactory;

    protected $table = 'campeonato_partido_goles';

    protected $fillable = [
        'campeonato_partido_id',
        'equipo_id',
        'jugador_id',
        'minuto',
        'detalle',
    ];

    public function partido()
    {
        return $this->belongsTo(CampeonatoPartido::class, 'campeonato_partido_id');
    }

    public function equipo()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'equipo_id');
    }

    public function jugador()
    {
        return $this->belongsTo(CampeonatoJugador::class, 'jugador_id');
    }
}
