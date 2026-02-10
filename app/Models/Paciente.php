<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $table = 'PACIENTES';
    protected $primaryKey = 'ID_PACIENTE';
    public $sequence = 'SEQ_PACIENTES';
    public $timestamps = false;

    protected $fillable = [
        'NOMBRE_COMPLETO',
        'FECHA_NACIMIENTO',
        'SEXO',
        'ALERGIAS_PRINCIPALES',
        'CORREO',
        'TELEFONO',
        'TIPO_SANGRE',
        'ID_USUARIO'
    ];
}
