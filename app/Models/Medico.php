<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'MEDICO';
    protected $primaryKey = 'ID_MEDICO';
    public $timestamps = false;
    // Si usaste una secuencia para médicos, agrégala (opcional pero recomendado)
    // public $sequence = 'SEQ_MEDICOS';

    protected $fillable = [
        'ID_USUARIO',
        'ESPECIALIDAD',
        'CEDULA',
        'BIO'
    ];

    // --- ¡ESTA ES LA MAGIA QUE FALTABA! ---
    // Le dice a Laravel que un Médico tiene un Usuario (Login) asociado
    public function usuario()
    {
        return $this->belongsTo(User::class, 'ID_USUARIO', 'ID_USUARIO');
    }

    // Le dice a Laravel que un Médico tiene muchos Horarios
    public function horarios()
    {
        return $this->hasMany(HorarioMedico::class, 'ID_MEDICO', 'ID_MEDICO');
    }
}
