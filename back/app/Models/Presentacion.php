<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentacion extends Model
{
    use SoftDeletes;

    protected $table = 'presentaciones';
    protected $primaryKey = 'id_presentacion';

    protected $fillable = [
        'id_producto',
        'codigo_sku',
        'nombre_presentacion',
        'imagen',
        'peso_neto',
        'unidad_medida',
        'tipo_envase',
        'unidades_por_paquete',
        'precio_unitario',
        'precio_paquete',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    // Cada presentación pertenece a un producto.
    public function producto()
    {
        return $this->belongsTo(
            Producto::class,
            'id_producto',
            'id'
        );
    }

    // Una presentación puede tener varios lotes.
    public function lotes()
    {
        return $this->hasMany(
            Lote::class,
            'id_presentacion',
            'id_presentacion'
        );
    }
}
