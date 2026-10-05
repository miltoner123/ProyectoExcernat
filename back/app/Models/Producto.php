<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'estado',
        'categoria_id',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function presentaciones()
    {
        return $this->hasMany(
            Presentacion::class,
            'id_producto',
            'id'
        );
    }
}
