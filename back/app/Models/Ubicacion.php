<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Ubicacion extends Model
{
    protected $table = 'ubicaciones';

    protected $primaryKey = 'id_ubicacion';

    protected $fillable = [
        'id_evento',
        'nombre',
        'tipo',
        'propiedad',
        'departamento',
        'direccion',
        'estado',
    ];


    public function evento(){
        return $this->belongsTo(Evento::class,
            'id_evento',
            'id_evento'
        );
    }
    public function inventarios(){
    return $this->hasMany(Inventario::class,
        'id_ubicacion',
        'id_ubicacion'
    );
    }
    public function movimientosOrigen()
    {
        return $this->hasMany(
            MovimientoInventario::class,
            'id_ubicacion_origen',
            'id_ubicacion'
        );
    }

    public function movimientosDestino()
    {
        return $this->hasMany(
            MovimientoInventario::class,
            'id_ubicacion_destino',
            'id_ubicacion'
        );
    }
    
}

