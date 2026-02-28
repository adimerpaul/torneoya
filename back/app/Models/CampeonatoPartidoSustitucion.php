<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoPartidoSustitucion extends Model
{
    use HasFactory;

    protected $table = 'campeonato_partido_sustituciones';

    protected $fillable = [
        'campeonato_partido_id',
        'equipo_id',
        'jugador_sale_id',
        'jugador_entra_id',
        'minuto',
    ];

    public function equipo()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'equipo_id');
    }

    public function jugadorSale()
    {
        return $this->belongsTo(CampeonatoJugador::class, 'jugador_sale_id');
    }

    public function jugadorEntra()
    {
        return $this->belongsTo(CampeonatoJugador::class, 'jugador_entra_id');
    }
}
