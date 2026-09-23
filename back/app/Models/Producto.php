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
        'activo',
        'categoria_id',
        
    ];
    public function categoria(){
        return $this->belongsTo(Categoria::class);
    }
}
