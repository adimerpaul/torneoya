<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoJugador extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_jugadores';

    protected $fillable = [
        'campeonato_equipo_id',
        'nombre',
        'abreviado',
        'foto',
        'posicion',
        'numero_camiseta',
        'fecha_nacimiento',
        'documento',
        'celular',
    ];

    public function equipo()
    {
        return $this->belongsTo(CampeonatoEquipo::class, 'campeonato_equipo_id');
    }
}
