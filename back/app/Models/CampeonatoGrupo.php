<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoGrupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'campeonato_id',
        'nombre',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function equipos()
    {
        return $this->hasMany(CampeonatoEquipo::class)->orderBy('nombre');
    }
}

