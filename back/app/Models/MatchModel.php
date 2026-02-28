<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatchModel extends Model
{
    use SoftDeletes;

    protected $table = 'matches';

    protected $fillable = [
        'scope_type',
        'scope_id',
        'stage_id',
        'group_id',
        'home_team_id',
        'away_team_id',
        'scheduled_at',
        'venue',
        'referee_main',
        'referee_assistants_json',
        'status',
        'home_score',
        'away_score',
        'home_points_extra',
        'away_points_extra',
        'comments_public',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'referee_assistants_json' => 'array',
        ];
    }
}
