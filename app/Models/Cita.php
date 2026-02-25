<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cita extends Model
{
    use HasFactory;

    protected $table = 'CITAS';
    protected $primaryKey = 'ID_CITAS'; // Con "S" al final
    public $timestamps = false;
    public $sequence = 'SEQ_CITAS';

    protected $casts = [
        'FECHA_HORA' => 'datetime',
    ];

    protected $fillable = [
        'ID_PACIENTE',
        'ID_MEDICO',
        'ID_USUARIO_REGISTRO',
        'FECHA_HORA',
        'ESTADO',
        'MOTIVO',
        'NOTAS_MEDICAS'
    ];

    // --- RELACIONES IMPORTANTES ---

    // La cita pertenece a un médico
    public function medico()
    {
        return $this->belongsTo(Medico::class, 'ID_MEDICO', 'ID_MEDICO');
    }

    // La cita pertenece a un paciente (Lo vinculamos con la tabla de Pacientes)
    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'ID_PACIENTE', 'ID_PACIENTE');
    }
}
