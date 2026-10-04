<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $primaryKey = 'id_pedido';

    protected $fillable = [
        'tipo',
        'estado',
        'fecha_pedido',
        'referencia',
        'observacion',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(
            DetallePedido::class,
            'id_pedido',
            'id_pedido'
        );
    }
}
