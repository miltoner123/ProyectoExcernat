<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'categoria_producto_id'
    ];
    public function categoriaProducto()
    {
        return $this->belongsTo(CategoriaProducto::class);
    }
}
