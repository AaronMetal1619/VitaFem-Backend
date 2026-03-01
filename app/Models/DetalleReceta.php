<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleReceta extends Model
{
    use HasFactory;

    protected $table = 'DETALLE_RECETAS';
    protected $primaryKey = 'ID_DETALLE';
    public $timestamps = false;

    // Secuencia para Oracle
    public $sequence = 'SEQ_DETALLE_RECETAS';

    protected $fillable = [
        'ID_RECETA',
        'MEDICAMENTO',
        'DOSIS',
        'FRECUENCIA',
        'DURACION'
    ];

    // Relaciones
    public function receta()
    {
        return $this->belongsTo(Receta::class, 'ID_RECETA', 'ID_RECETA');
    }
}
