<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    use HasFactory;

    protected $table = 'RECETAS';
    protected $primaryKey = 'ID_RECETA';
    public $timestamps = false;

    // Secuencia para Oracle
    public $sequence = 'SEQ_RECETAS';

    protected $fillable = [
        'ID_CONSULTA',
        'FECHA'
    ];

    // Relaciones
    public function consulta()
    {
        return $this->belongsTo(Consulta::class, 'ID_CONSULTA', 'ID_CONSULTA');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleReceta::class, 'ID_RECETA', 'ID_RECETA');
    }
}
