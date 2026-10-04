<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $primaryKey = 'id_movimiento';

    protected $fillable = [
        'tipo',
        'id_ubicacion_origen',
        'id_ubicacion_destino',
        'fecha_movimiento',
        'referencia',
        'observacion',
    ];

    public function origen()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'id_ubicacion_origen',
            'id_ubicacion'
        );
    }

    public function destino()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'id_ubicacion_destino',
            'id_ubicacion'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            DetalleMovimiento::class,
            'id_movimiento',
            'id_movimiento'
        );
    }
}
