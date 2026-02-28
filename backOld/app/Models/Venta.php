<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Venta extends Model{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'cliente_id',
        'pedido_id',
        'fecha',
        'hora',
        'ci',
        'nombre',
        'estado',
        'tipo_comprobante',
        'total',
//        'tipo_venta',
        'tipo_pago',
        'considerar_en_cobranza',
        'facturado',
        'factura_estado',
        'factura_error',
        'agencia',
        'cuf',
        'leyenda',
        'online',
        'cufd'
//        'pagado_interno'
    ];
    protected $casts = [
        'facturado' => 'boolean',
        'online' => 'boolean',
        'considerar_en_cobranza' => 'boolean',
    ];
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];
    function user(){
        return $this->belongsTo(User::class);
    }
    function cliente(){
        return $this->belongsTo(Cliente::class);
    }
    function pedido(){
        return $this->belongsTo(Pedido::class);
    }
    function ventaDetalles(){
        return $this->hasMany(VentaDetalle::class);
    }
    function pagos(){
        return $this->hasMany(Pago::class);
    }
    protected $appends = ['detailsText'];
    function getDetailsTextAttribute(){
        $detailsText = '';
        foreach ($this->ventaDetalles as $ventaDetalle) {
            $detailsText .= $ventaDetalle->cantidad . ' ' . $ventaDetalle->producto->nombre . ',';
        }
        $detailsText = substr($detailsText, 0, -1);
        return $detailsText;
    }
}
