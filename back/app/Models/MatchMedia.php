<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchMedia extends Model
{
    protected $fillable = [
        'match_id',
        'file_path',
        'media_type',
        'caption',
        'sort_order',
        'uploaded_by',
    ];
}
