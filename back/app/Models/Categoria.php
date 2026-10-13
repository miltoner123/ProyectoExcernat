<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion'
    ];
    use SoftDeletes;
    function productos()
    {
        return $this->hasMany(Producto::class);
    }
}
