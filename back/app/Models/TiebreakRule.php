<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiebreakRule extends Model
{
    protected $fillable = [
        'name',
        'ordered_json',
    ];

    protected function casts(): array
    {
        return [
            'ordered_json' => 'array',
        ];
    }
}
