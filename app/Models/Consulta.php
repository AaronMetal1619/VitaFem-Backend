<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;
    protected $table = 'CONSULTAS';
    protected $primaryKey = 'ID_CONSULTA';
    public $sequence = 'SEQ_CONSULTAS';
    public $timestamps = false;
    protected $fillable = [
        'ID_CITAS', 'ID_PACIENTE', 'PESO', 'ALTURA',
        'TEMPERATURA', 'PRESION_ARTERIAL', 'SINTOMAS_SUBJETIVOS',
        'EXPLORACION_FISICA', 'DIAGNOSTICO', 'TRATAMIENTO_INDICACIONES'
    ];
}
