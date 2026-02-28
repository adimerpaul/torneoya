<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoJugador extends Model
{
    use HasFactory;

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

