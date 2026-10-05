<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';

    protected $primaryKey = 'id_detalle_pedido';

    protected $fillable = [
        'id_pedido',
        'id_presentacion',
        'cantidad_paquetes',
        'cantidad_unidades',
    ];

    public function pedido()
    {
        return $this->belongsTo(
            Pedido::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function presentacion()
    {
        return $this->belongsTo(
            Presentacion::class,
            'id_presentacion',
            'id_presentacion'
        );
    }
}