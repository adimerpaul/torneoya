<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable as AuditableTrait;
class Campeonato extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, AuditableTrait;

    protected $fillable = [
        'user_id',
        'parent_id',
        'nombre',
        'tipo',
        'deporte',
        'codigo',
        'imagen',
        'banner',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $appends = ['deporte_icono'];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function categorias()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('id', 'desc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mensajes()
    {
        return $this->hasMany(CampeonatoMensaje::class)->latest();
    }

    public function getDeporteIconoAttribute(): ?string
    {
        return Campeonato::deportesCatalogo()[$this->deporte]['icono'] ?? 'emoji_events';
    }

    public static function deportesCatalogo(): array
    {
        return [
            'futsal' => ['nombre' => 'Futsala', 'icono' => 'sports_soccer'],
            'futbol' => ['nombre' => 'Futbol', 'icono' => 'sports_soccer'],
            'futbol_7' => ['nombre' => 'Futbol 7', 'icono' => 'sports_soccer'],
            'voleibol' => ['nombre' => 'Voleibol', 'icono' => 'sports_volleyball'],
            'tenis' => ['nombre' => 'Tenis', 'icono' => 'sports_tennis'],
            'ajedrez' => ['nombre' => 'Ajedrez', 'icono' => 'grid_view'],
            'atletismo' => ['nombre' => 'Atletismo', 'icono' => 'directions_run'],
            'basquetbol' => ['nombre' => 'Basquetbol', 'icono' => 'sports_basketball'],
        ];
    }
}
