<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategoriaCampeonato extends Model
{
    use SoftDeletes;

    protected $table = 'categoria_campeonatos';

    protected $fillable = [
        'campeonato_id',
        'deporte_id',
        'nombre',
        'formato',
        'estado',
    ];

    public function campeonato(): BelongsTo
    {
        return $this->belongsTo(Campeonato::class);
    }

    public function deporte(): BelongsTo
    {
        return $this->belongsTo(Deporte::class);
    }
}
