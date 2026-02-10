<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // REGISTRO DE USUARIOS
    public function register(Request $request)
    {
        // 1. Validar lo que viene de React
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:100|unique:USUARIOS,CORREO',
            'password' => 'required|string|min:6',
        ]);

        // CHIVATO 1: Ver qué llega
        Log::info('Intento de Login:', ['email' => $request->email]);

        // 2. Crear el Usuario en Oracle
        // Mapeamos: React (minúsculas) -> Oracle (Mayúsculas)
        $user = new User();
        $user->NOMBRE = $request->name;
        $user->CORREO = $request->email;
        $user->PASSWORD = Hash::make($request->password);
        $user->ACTIVO = '1'; // Por defecto activo
        $user->save();

        // 3. Crear Token (Simulado o Sanctum)
        // Por simplicidad devolvemos el usuario, luego configuraremos Sanctum si lo necesitas
        return response()->json([
            'message' => 'Usuario registrado',
            'user' => $user,
            'token' => 'token-demo-123' // Aquí iría el token real de Sanctum
        ], 201);
    }

    // LOGIN DE USUARIOS
    // LOGIN DE USUARIOS
    public function login(Request $request)
    {
        // ... validaciones ...

        // 1. Buscar usuario
        // Aquí SÍ usamos mayúsculas en el WHERE porque es SQL directo hacia Oracle
        $user = User::where('CORREO', $request->email)->first();

        if (!$user) {
            // ... error ...
        }

        // 2. Verificar contraseña
        // CAMBIO AQUÍ: Usamos ->password (minúscula) porque así vino del driver
        if (!Hash::check($request->password, $user->password)) {
            Log::info('Fallo Login: Hash no coincide.');
            return response()->json(['message' => 'Credenciales incorrectas'], 401);
        }

        // 3. Login exitoso
        // Ocultamos ambos por si acaso
        $user->makeHidden(['password', 'PASSWORD', 'CONTRASEÑA']);

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => 'token-demo-123'
        ]);
    }
}
