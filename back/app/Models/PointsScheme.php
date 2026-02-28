<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointsScheme extends Model
{
    protected $fillable = [
        'name',
        'points_win',
        'points_draw',
        'points_loss',
        'points_walkover_win',
        'points_walkover_loss',
        'enabled',
    ];
}
