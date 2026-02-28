<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchResultRevision extends Model
{
    protected $fillable = [
        'match_id',
        'old_home_score',
        'old_away_score',
        'new_home_score',
        'new_away_score',
        'reason',
        'changed_by',
    ];
}
