<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoGrupo extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $table = 'campeonato_grupos';

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
