<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampeonatoMensaje extends Model
{
    use HasFactory;

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

