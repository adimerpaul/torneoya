<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoPartidoPortero extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_partido_porteros';

    protected $fillable = [
        'campeonato_partido_id',
        'equipo_id',
        'jugador_id',
        'nombre_portero',
        'titular',
    ];

    protected $casts = [
        'titular' => 'boolean',
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
