<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChampionshipMulti extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'slug',
        'name',
        'description',
        'logo',
        'cover',
        'start_date',
        'inscription_deadline',
        'color_primary',
        'color_secondary',
        'rules_text',
        'prizes_text',
        'status',
    ];
}
