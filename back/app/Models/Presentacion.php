<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\belongsTo;

class Presentacion extends Model
{
    protected $table = 'presentaciones';

    protected $primaryKey = 'id_presentacion';

    protected $fillable = [
        'id_producto',
        'codigo_sku',
        'nombre_presentacion',
        'peso_neto',
        'unidad_medida',
        'tipo_envase',
        'unidades_por_paquete',
        'precio_unitario',
        'precio_paquete',
        'estado',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class,
            'id_producto',
            'id'
        );
    }
    public function lotes()
{
    return $this->hasMany(
        Lote::class,
        'id_presentacion',
        'id_presentacion'
    );
}
}