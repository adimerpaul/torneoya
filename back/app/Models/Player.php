<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_type',
        'document_number',
        'birthdate',
        'photo',
        'dominant_foot',
        'position_default',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }
}
