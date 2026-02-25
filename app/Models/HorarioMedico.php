<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioMedico extends Model
{
    use HasFactory;

    protected $table = 'HORARIOS_MEDICO';
    protected $primaryKey = 'ID_HORARIO';
    public $timestamps = false;
    public $sequence = 'SEQ_HORARIOS_MEDICO';

    protected $fillable = [
        'ID_MEDICO',
        'DIA_SEMANA', // 1=Lunes, 7=Domingo
        'HORA_INICIO',
        'HORA_FIN',
        'DURACION_CITA'
    ];

    // Relación: Un Horario pertenece a un Médico
    public function medico()
    {
        return $this->belongsTo(Medico::class, 'ID_MEDICO', 'ID_MEDICO');
    }
}
