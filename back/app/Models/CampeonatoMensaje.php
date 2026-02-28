<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;

class CampeonatoMensaje extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'campeonato_id',
        'user_id',
        'autor_nombre',
        'mensaje',
        'visible',
    ];

    protected $casts = [
        'visible' => 'boolean',
    ];

    public function campeonato()
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
