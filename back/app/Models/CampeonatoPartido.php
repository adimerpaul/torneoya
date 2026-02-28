<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoPartido extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_partidos';

    protected $fillable = [
        'campeonato_id',
        'campeonato_fase_id',
        'campeonato_fecha_id',
        'programado_at',
        'local_equipo_id',
        'visita_equipo_id',
        'grupo_nombre',
        'goles_local',
        'goles_visita',
        'estado',
    ];

    protected $casts = [
        'programado_at' => 'datetime',
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

    public function goles()
    {
        return $this->hasMany(CampeonatoPartidoGol::class, 'campeonato_partido_id')->orderBy('minuto');
    }

    public function tarjetasAmarillas()
    {
        return $this->hasMany(CampeonatoPartidoTarjetaAmarilla::class, 'campeonato_partido_id')->orderBy('minuto');
    }

    public function tarjetasRojas()
    {
        return $this->hasMany(CampeonatoPartidoTarjetaRoja::class, 'campeonato_partido_id')->orderBy('minuto');
    }

    public function faltas()
    {
        return $this->hasMany(CampeonatoPartidoFalta::class, 'campeonato_partido_id')->orderBy('minuto');
    }

    public function sustituciones()
    {
        return $this->hasMany(CampeonatoPartidoSustitucion::class, 'campeonato_partido_id')->orderBy('minuto');
    }

    public function porteros()
    {
        return $this->hasMany(CampeonatoPartidoPortero::class, 'campeonato_partido_id');
    }
}
