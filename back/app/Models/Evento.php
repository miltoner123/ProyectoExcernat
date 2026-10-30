<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    protected $table = 'eventos';
    protected $primaryKey = 'id_evento';

    protected $fillable = [
        'nombre',
        'tipo',
        'departamento',
        'lugar',
        'fecha_inicio',
        'fecha_fin',
        'id_responsable',
        'presupuesto_estimado',
        'estado',
        'estado_operativo',
        'fecha_finalizacion',
        'fecha_cierre',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'presupuesto_estimado' => 'decimal:2',
        'estado' => 'boolean',
        'fecha_finalizacion' => 'datetime',
        'fecha_cierre' => 'datetime',
    ];

    public function responsable()
    {
        return $this->belongsTo(Personal::class, 'id_responsable', 'id_personal');
    }

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class, 'id_evento', 'id_evento');
    }
    public function asignacionesPersonal()
    {
    return $this->hasMany(AsignacionPersonal::class, 'id_evento', 'id_evento');
    }
}