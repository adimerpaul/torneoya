<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScorerSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'scope_type',
        'scope_id',
        'player_id',
        'team_id',
        'goals',
        'own_goals',
        'yellow_cards',
        'red_cards',
        'matches_played',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }
}
