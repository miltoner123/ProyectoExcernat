<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Venta extends Model {
    protected $table='ventas'; protected $primaryKey='id_venta';
    protected $fillable=['id_ubicacion','id_usuario','id_asignacion_vendedor','fecha_venta','estado','total','subtotal_bruto','descuento_total','observacion','motivo_anulacion','fecha_anulacion','id_usuario_anulacion'];
    protected $casts=['fecha_venta'=>'datetime','fecha_anulacion'=>'datetime','total'=>'decimal:2','subtotal_bruto'=>'decimal:2','descuento_total'=>'decimal:2'];
    public function asignacionVendedor(){return $this->belongsTo(AsignacionPersonal::class,'id_asignacion_vendedor','id_asignacion');}
    public function cobros(){return $this->hasMany(CobroVenta::class,'id_venta','id_venta');}
    public function detalles(){return $this->hasMany(DetalleVenta::class,'id_venta','id_venta');}
    public function ubicacion(){return $this->belongsTo(Ubicacion::class,'id_ubicacion','id_ubicacion');}
    public function usuarioAnulacion(){return $this->belongsTo(User::class,'id_usuario_anulacion','id');}
    public function usuario(){return $this->belongsTo(User::class,'id_usuario','id');}
}
