<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $primaryKey = 'id_pedido';

    protected $fillable = [
        'id_ubicacion_solicitante',
        'tipo',
        'estado',
        'fecha_pedido',
        'observacion',
    ];

    protected $casts = [
        'fecha_pedido' => 'datetime',
    ];

    public function ubicacionSolicitante()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'id_ubicacion_solicitante',
            'id_ubicacion'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            DetallePedido::class,
            'id_pedido',
            'id_pedido'
        );
    }
}