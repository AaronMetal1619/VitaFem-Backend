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

    // 3. ¿Tu tabla tiene columnas created_at y updated_at?
    // En tu script SQL NO las vi, así que pon esto en false para que no de error.
    public $timestamps = false;

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
