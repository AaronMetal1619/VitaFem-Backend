<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;
    protected $table = 'RECETAS';
    protected $primaryKey = 'ID_RECETA';
    public $sequence = 'SEQ_RECETAS';
    public $timestamps = false;
    protected $fillable = ['ID_CONSULTA', 'FECHA'];
}
