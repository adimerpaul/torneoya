<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductoGrupoPadre extends Model{
    use SoftDeletes;
    protected $fillable = ['nombre', 'codigo'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];
}
