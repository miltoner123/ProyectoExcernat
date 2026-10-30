<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleMovimiento extends Model
{
    protected $table = 'detalle_movimiento';

    protected $primaryKey = 'id_detalle_movimiento';

    protected $fillable = [
        'id_movimiento',
        'id_lote',
        'cantidad',
    ];

    public function movimiento()
    {
        return $this->belongsTo(
            MovimientoInventario::class,
            'id_movimiento',
            'id_movimiento'
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
