<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductoGrupo extends Model{
    use SoftDeletes;
    protected $fillable = ['nombre', 'codigo', 'producto_grupo_padre_id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    function productoGrupoPadre(){
        return $this->belongsTo(ProductoGrupoPadre::class);
    }
}
