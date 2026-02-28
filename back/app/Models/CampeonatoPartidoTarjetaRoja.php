<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoPartidoTarjetaRoja extends Model
{
    use HasFactory;

    protected $table = 'campeonato_partido_tarjetas_rojas';

    protected $fillable = [
        'campeonato_partido_id',
        'equipo_id',
        'jugador_id',
        'minuto',
        'detalle',
    ];

    public function equipo()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'equipo_id');
    }

    public function jugador()
    {
        return $this->belongsTo(CampeonatoJugador::class, 'jugador_id');
    }
}
