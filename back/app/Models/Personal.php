<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personal';
    protected $primaryKey = 'id_personal';

    protected $fillable = [
        'id_persona',
        'id_cargo',
        'codigo_empleado',
        'fecha_ingreso',
        'tipo_contrato',
        'salario_base',
        'estado',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'salario_base' => 'decimal:2',
        'estado' => 'boolean',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona', 'id_persona');
    }

    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo', 'id_cargo');
    }
    public function eventosResponsable()
    {
    return $this->hasMany(Evento::class, 'id_responsable', 'id_personal');
    }
    public function asignaciones()
    {
    return $this->hasMany(AsignacionPersonal::class, 'id_personal', 'id_personal');
    }
    public function asistencias()
    {
    return $this->hasMany(Asistencia::class, 'id_personal', 'id_personal');
    }
}