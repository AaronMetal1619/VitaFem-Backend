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

    // --- ESTA ES LA LÍNEA MÁGICA QUE FALTABA ---
    public $sequence = 'SEQ_MEDICOS';
    // ------------------------------------------

    protected $fillable = [
        'ID_USUARIO',
        'ESPECIALIDAD',
        'CEDULA',
        'BIO'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'ID_USUARIO', 'ID_USUARIO');
    }

    public function horarios()
    {
        return $this->hasMany(HorarioMedico::class, 'ID_MEDICO', 'ID_MEDICO');
    }
}
