<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoFase extends Model
{
    use HasFactory;

    protected $table = 'campeonato_fases';

    protected $fillable = [
        'campeonato_id',
        'nombre',
        'tipo',
        'orden',
        'agrupar_por_grupo',
    ];

    protected $casts = [
        'agrupar_por_grupo' => 'boolean',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function fechas()
    {
        return $this->hasMany(CampeonatoFecha::class, 'campeonato_fase_id')->orderBy('orden');
    }

    public function partidos()
    {
        return $this->hasMany(CampeonatoPartido::class, 'campeonato_fase_id')->orderBy('id');
    }
}

