<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'USUARIOS';
    protected $primaryKey = 'ID_USUARIO';
    public $timestamps = false;

    // En Oracle tus columnas son mayúsculas
    protected $fillable = [
        'NOMBRE',
        'CORREO',
        'CONTRASEÑA', // Ojo: Laravel espera 'password', haremos un truco abajo
        'ACTIVO',
    ];

    protected $hidden = [
        'CONTRASEÑA',
    ];

    // Esto le dice a Laravel: "Cuando busques la contraseña del usuario,
    // búscala en la columna 'CONTRASEÑA', no en 'password'"
    public function getAuthPassword()
    {
        return $this->CONTRASEÑA;
    }
}
