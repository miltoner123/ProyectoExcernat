<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';
    protected $primaryKey = 'id_persona';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres_razon_social',
        'apellidos',
        'correo',
        'telefono',
        'sexo',
        'tipo_persona',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];
        public function personal()
{
    return $this->hasOne(Personal::class, 'id_persona', 'id_persona');
}

}