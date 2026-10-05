<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionPersonal extends Model
{
    use HasFactory;

    protected $table = 'asignaciones_personal';
    protected $primaryKey = 'id_asignacion';

    protected $fillable = [
        'id_personal',
        'id_ubicacion',
        'id_evento',
        'funcion_asignada',
        'tipo_remuneracion',
        'monto_fijo',
        'comision_polvo',
        'comision_granola',
        'fecha_inicio',
        'fecha_fin',
        'estado',
    ];

    protected $casts = [
        'monto_fijo' => 'decimal:2',
        'comision_polvo' => 'decimal:2',
        'comision_granola' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'estado' => 'boolean',
    ];

    public function personal()
    {
        return $this->belongsTo(Personal::class, 'id_personal', 'id_personal');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion', 'id_ubicacion');
    }

    public function evento()
    {
        return $this->belongsTo(Evento::class, 'id_evento', 'id_evento');
    }
}