<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaProducto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion'
    ];
    function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
