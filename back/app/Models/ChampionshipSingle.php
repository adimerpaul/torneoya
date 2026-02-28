<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChampionshipSingle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'logo',
        'cover',
        'sport_id',
        'format_default_id',
        'points_scheme_id',
        'tiebreak_rule_id',
        'start_date',
        'inscription_deadline',
        'color_primary',
        'color_secondary',
        'rules_text',
        'prizes_text',
        'status',
    ];
}
