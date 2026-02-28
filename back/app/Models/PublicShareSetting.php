<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicShareSetting extends Model
{
    protected $fillable = [
        'scope_type',
        'scope_id',
        'is_public',
        'public_slug',
        'show_comments',
        'show_media',
        'show_player_stats',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'show_comments' => 'boolean',
            'show_media' => 'boolean',
            'show_player_stats' => 'boolean',
        ];
    }
}
