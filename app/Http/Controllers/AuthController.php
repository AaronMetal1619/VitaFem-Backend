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

        return response()->json([
            'message' => 'Usuario registrado',
            'usuario' => $user,
            'access_token' => 'token-demo-123' // Aquí iría el token real de Sanctum
        ], 201);
    }

    // LOGIN DE USUARIOS
    public function login(Request $request)
    {
        // 0. Recibimos el correo (Soporta si el Front envía 'correo' o 'email')
        $correoRecibido = $request->correo ?? $request->email;

        // 1. Buscar usuario (Soportando mayúsculas y minúsculas de Oracle)
        $user = User::where('CORREO', $correoRecibido)
                    ->orWhere('correo', $correoRecibido)
                    ->first();

        // 2. ¡EL ESCUDO! Si no existe, DEBEMOS detener la ejecución con un return
        if (!$user) {
            Log::warning('Fallo Login: Correo no encontrado - ' . $correoRecibido);
            return response()->json(['message' => 'Correo no encontrado en el sistema'], 401);
        }

        // 3. Verificar contraseña (Cubriendo Oracle)
        $passwordDB = $user->PASSWORD ?? $user->password;

        if (!Hash::check($request->password, $passwordDB)) {
            Log::info('Fallo Login: Hash no coincide para - ' . $correoRecibido);
            return response()->json(['message' => 'Contraseña incorrecta'], 401);
        }

        // 4. Login exitoso
        // Ocultamos las contraseñas para que no viajen al celular por seguridad
        $user->makeHidden(['password', 'PASSWORD', 'CONTRASEÑA']);

        // IMPORTANTE: Devolvemos 'usuario' y 'access_token' porque así los espera React Native
        return response()->json([
            'message' => 'Login exitoso',
            'usuario' => $user,
            'access_token' => 'token-demo-123'
        ]);
    }
}
