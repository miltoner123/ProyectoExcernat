<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';

    protected $primaryKey = 'id_detalle_pedido';

    protected $fillable = [
        'id_pedido',
        'id_lote',
        'cantidad',
        'precio_unitario',
    ];

    protected $casts = [
        'precio_unitario' => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(
            Pedido::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function lote()
    {
        return $this->belongsTo(
            Lote::class,
            'id_lote',
            'id_lote'
        );
    }
}
