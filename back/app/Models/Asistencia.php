<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';
    protected $primaryKey = 'id_asistencia';

    protected $fillable = [
        'id_personal',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_personal', 'id_personal');
    }
}