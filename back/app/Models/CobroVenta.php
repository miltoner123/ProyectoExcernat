<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CobroVenta extends Model {
    protected $table='cobros_ventas';
    protected $primaryKey='id_cobro';
    protected $fillable=['id_caja','id_venta','id_usuario','monto','metodo_pago','referencia','clave_operacion','observacion','fecha_cobro'];
    protected $casts=['monto'=>'decimal:2','fecha_cobro'=>'datetime'];
    public function venta(){return $this->belongsTo(Venta::class,'id_venta','id_venta');}
    public function usuario(){return $this->belongsTo(User::class,'id_usuario','id');}
}
