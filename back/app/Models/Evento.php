<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    protected $table = 'eventos';

    protected $primaryKey = 'id_evento';

    protected $fillable = [
        'nombre',
        'tipo',
        'departamento',
        'lugar',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'observacion',
    ];

    public function ubicaciones()
    {
        return $this->hasMany(
            Ubicacion::class,
            'id_evento',
            'id_evento'
        );
    }
}