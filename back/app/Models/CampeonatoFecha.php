<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoFecha extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_fechas';

    protected $fillable = [
        'campeonato_fase_id',
        'nombre',
        'orden',
    ];

    public function fase()
    {
        return $this->belongsTo(CampeonatoFase::class, 'campeonato_fase_id');
    }

    public function partidos()
    {
        return $this->hasMany(CampeonatoPartido::class, 'campeonato_fecha_id');
    }
}
