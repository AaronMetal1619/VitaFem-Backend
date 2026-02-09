<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialMedico extends Model
{
    use HasFactory;
    protected $table = 'HISTORIAL_MEDICO';
    protected $primaryKey = 'ID_HISTORIAL';
    public $timestamps = false;
    protected $fillable = ['ID_PACIENTE', 'TIPO_ANTECEDENTE', 'DESCRIPCION'];
}
