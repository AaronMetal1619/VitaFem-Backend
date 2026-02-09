<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    use HasFactory;

    protected $table = 'MEDICO'; // Nota: En tu script le pusiste MEDICO (singular)
    protected $primaryKey = 'ID_MEDICO';
    public $timestamps = false;

    protected $fillable = [
        'CEDULA_PROFESIONAL',
        'ID_USUARIO'
    ];
}
