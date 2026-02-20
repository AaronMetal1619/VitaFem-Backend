<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    // 1. Nombre exacto de la tabla en Oracle
    protected $table = 'CITAS';

    // 2. Nombre de tu Clave Primaria (Primary Key)
    protected $primaryKey = 'ID_CITAS';


    public $timestamps = false;
    public $sequence = 'SEQ_CITAS';

    // 4. Campos que se pueden llenar (Mass Assignment)
    protected $fillable = [
        'ID_PACIENTE',
        'ID_MEDICO',
        'FECHA_HORA_INICIO',
        'FECHA_HORA_FIN',
        'ESTADO',
        'MOTIVO_VISITA'
    ];
}
