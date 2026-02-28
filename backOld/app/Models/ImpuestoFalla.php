<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImpuestoFalla extends Model
{
    use SoftDeletes;

    protected $table = 'impuesto_fallas';

    protected $fillable = [
        'tipo',
        'mensaje',
        'detalle',
        'estado',
        'fecha_evento',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'fecha_evento' => 'datetime',
            'resolved_at' => 'datetime',
            'detalle' => 'array',
        ];
    }
}
