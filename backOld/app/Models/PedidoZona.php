<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoZona extends Model
{
    protected $fillable = [
        'nombre',
        'color',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden' => 'integer',
    ];
}

