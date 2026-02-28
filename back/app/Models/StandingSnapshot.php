<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandingSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'group_id',
        'team_id',
        'played',
        'won',
        'draw',
        'lost',
        'goals_for',
        'goals_against',
        'goal_diff',
        'points',
        'percentage',
        'tied_matches',
        'fair_play_points',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'float',
            'updated_at' => 'datetime',
        ];
    }
}
