<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'MEDICO'; // Nota: En tu script le pusiste MEDICO (singular)
    protected $primaryKey = 'ID_MEDICO';
    public $sequence = 'SEQ_MEDICOS'; // Si usas secuencia para el ID, indícala aquí
    public $timestamps = false;

    protected $fillable = [
        'CEDULA_PROFESIONAL',
        'ID_USUARIO'
    ];
}
