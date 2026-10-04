<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $primaryKey = 'id_inventario';

    protected $fillable = [
        'id_ubicacion',
        'id_lote',
        'stock_actual',
        'stock_minimo',
    ];

    public function ubicacion()
    {
        return $this->belongsTo(
            Ubicacion::class,
            'id_ubicacion',
            'id_ubicacion'
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
