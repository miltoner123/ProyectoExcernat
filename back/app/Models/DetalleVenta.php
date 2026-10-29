<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class DetalleVenta extends Model {
    protected $table='detalle_ventas'; protected $primaryKey='id_detalle_venta';
    protected $fillable=['id_venta','id_lote','cantidad','precio_unitario','subtotal'];
    public function lote(){return $this->belongsTo(Lote::class,'id_lote','id_lote');}
}
