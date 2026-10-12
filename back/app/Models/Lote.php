<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lote extends Model
{
    use SoftDeletes;

    protected $table = 'lotes';

    protected $primaryKey = 'id_lote';

    protected $fillable = [
        'id_presentacion',
        'codigo_lote',
        'fecha_produccion',
        'vida_util_meses',
        'fecha_vencimiento',
        'cantidad_producida',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_produccion' => 'date:Y-m-d',
        'fecha_vencimiento' => 'date:Y-m-d',
        'vida_util_meses' => 'integer',
        'cantidad_producida' => 'integer',
        'estado' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class, 'id_presentacion', 'id_presentacion');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventario::class, 'id_lote', 'id_lote');
    }

    public function detallesMovimiento()
    {
        return $this->hasMany(DetalleMovimiento::class, 'id_lote', 'id_lote');
    }
}