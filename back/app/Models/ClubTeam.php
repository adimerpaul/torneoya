<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClubTeam extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'owner_user_id',
        'name',
        'short_name',
        'logo',
        'coach_name',
        'staff_json',
    ];

    protected function casts(): array
    {
        return [
            'staff_json' => 'array',
        ];
    }
}
