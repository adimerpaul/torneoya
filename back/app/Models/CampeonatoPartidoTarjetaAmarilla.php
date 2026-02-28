<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoPartidoTarjetaAmarilla extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_partido_tarjetas_amarillas';

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
