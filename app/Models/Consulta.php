<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;

    protected $table = 'CONSULTAS';
    protected $primaryKey = 'ID_CONSULTA';
    public $timestamps = false;

    // Secuencia vital para Oracle
    public $sequence = 'SEQ_CONSULTAS';

    protected $fillable = [
        'ID_CITAS',
        'ID_PACIENTE',
        'PESO',
        'ALTURA',
        'TEMPERATURA',
        'PRESION_ARTERIAL',
        'SINTOMAS_SUBJETIVOS',
        'EXPLORACION_FISICA',
        'DIAGNOSTICO',
        'TRATAMIENTO_INDICACIONES'
    ];

    // Relaciones
    public function cita()
    {
        return $this->belongsTo(Cita::class, 'ID_CITAS', 'ID_CITAS');
    }

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'ID_PACIENTE', 'ID_PACIENTE');
    }

    public function receta()
    {
        return $this->hasOne(Receta::class, 'ID_CONSULTA', 'ID_CONSULTA');
    }
}
