<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $primaryKey = 'id_lote';

    protected $fillable = [
        'id_presentacion',
        'codigo_lote',
        'fecha_produccion',
        'fecha_vencimiento',
        'cantidad_producida',
        'estado',
        'observacion',
    ];


    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class,'id_presentacion','id_presentacion');
    }
    public function inventarios(){
    return $this->hasMany(Inventario::class,
        'id_lote',
        'id_lote'
    );
    }
    public function detallesMovimiento()
    {
        return $this->hasMany(
            DetalleMovimiento::class,
            'id_lote',
            'id_lote'
        );
    }
}