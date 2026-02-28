<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChampionshipCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'championship_multi_id',
        'sport_id',
        'name',
        'format_default_id',
        'points_scheme_id',
        'tiebreak_rule_id',
        'status',
    ];
}
