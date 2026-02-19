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
    public $sequence = 'SEQ_USUARIOS';
    public $timestamps = false;

    // En Oracle tus columnas son mayúsculas
    protected $fillable = [
        'NOMBRE',
        'CORREO',
        'PASSWORD', // Ojo: Laravel espera 'password', haremos un truco abajo
        'ACTIVO',
        'FOTO_PERFIL',
    ];

    protected $hidden = [
        'PASSWORD',
    ];

    // Esto le dice a Laravel: "Cuando busques la PASSWORD del usuario,
    // búscala en la columna 'PASSWORD', no en 'password'"
    public function getAuthPassword()
    {
        return $this->password;
    }
}
