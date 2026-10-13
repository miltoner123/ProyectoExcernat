<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'estado',
        'categoria_id',
    ];
    use SoftDeletes;

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
