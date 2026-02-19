<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PacienteController extends Controller
{
    // GET: /api/pacientes
    // Esta función se la daremos a React para llenar la tabla de pacientes
    public function index()
    {
        // SELECT * FROM PACIENTES
        $pacientes = Paciente::all();
        return response()->json($pacientes);
    }

    // POST: /api/pacientes
    // Esta función recibirá los datos del formulario de React
    public function store(Request $request)
    {
        // 1. Validación básica (Buenas prácticas)
        $request->validate([
            'NOMBRE_COMPLETO' => 'required|string|max:255',
            'CORREO' => 'required|email|max:100',
            // Agrega más validaciones según necesites
        ]);

        // 2. Crear el objeto
        $paciente = new Paciente();
        $paciente->NOMBRE_COMPLETO = $request->NOMBRE_COMPLETO;
        $paciente->CORREO = $request->CORREO;
        $paciente->SEXO = $request->SEXO;
        $paciente->TELEFONO = $request->TELEFONO;
        $paciente->ALERGIAS_PRINCIPALES = $request->ALERGIAS_PRINCIPALES;
        $paciente->TIPO_SANGRE = $request->TIPO_SANGRE;

        // Asignamos el ID de usuario si viene en el request (opcional)
        if ($request->has('ID_USUARIO')) {
            $paciente->ID_USUARIO = $request->ID_USUARIO;
        }

        // 3. Guardar en Oracle (La secuencia SEQ_PACIENTES hará el trabajo sucio del ID)
        $paciente->save();

        // 4. Responder a React que todo salió bien
        return response()->json([
            'message' => 'Paciente registrado correctamente',
            'paciente' => $paciente
        ], 201);
    }
    // POST: /api/pacientes/perfil
    public function updateProfile(Request $request)
    {
        // 1. Validar datos (Médicos + Foto)
        $request->validate([
            'ID_USUARIO' => 'required|exists:USUARIOS,ID_USUARIO', // Obligatorio para vincular
            'NOMBRE_COMPLETO' => 'required|string|max:255',
            'TELEFONO' => 'nullable|numeric',
            'FOTO_PERFIL' => 'nullable|image|max:2048', // Máx 2MB, solo imágenes
            // Datos médicos
            'FECHA_NACIMIENTO' => 'nullable|date',
            'TIPO_SANGRE' => 'nullable|string|max:10',
            'ALERGIAS_PRINCIPALES' => 'nullable|string',
            'SEXO' => 'nullable|string|max:1',
        ]);

        try {
            // 2. Manejo de la FOTO (Si se subió una)
            $rutaFoto = null;
            if ($request->hasFile('FOTO_PERFIL')) {
                Log::info('¡Archivo detectado! Procesando subida...');// Debug log para verificar que el archivo se está recibiendo
                // Guardar en: storage/app/public/avatars
                // El método store devuelve la ruta: "avatars/nombre_random.jpg"
                $rutaFoto = $request->file('FOTO_PERFIL')->store('avatars', 'public');
                Log::info('Archivo guardado en disco en: ' . $rutaFoto);// Debug log para verificar que el archivo se está guardando correctamente

                // Actualizar tabla USUARIOS con la ruta
                User::where('ID_USUARIO', $request->ID_USUARIO)
                    ->update(['FOTO_PERFIL' => $rutaFoto]);

                Log::info('Foto actualizada en BD para usuario: ' . $request->ID_USUARIO);
            }

            // 3. Crear o Actualizar la Ficha en PACIENTES
            // Buscamos si este usuario ya tiene ficha médica
            $paciente = Paciente::where('ID_USUARIO', $request->ID_USUARIO)->first();

            if (!$paciente) {
                // Si no existe, creamos uno nuevo
                $paciente = new Paciente();
                $paciente->ID_USUARIO = $request->ID_USUARIO;
                $paciente->CORREO = $user->CORREO ?? 'sin_correo@vita.fem'; // Fallback
            }

            // Actualizamos los datos
            $paciente->NOMBRE_COMPLETO = $request->NOMBRE_COMPLETO;
            $paciente->TELEFONO = $request->TELEFONO;
            $paciente->FECHA_NACIMIENTO = $request->FECHA_NACIMIENTO;
            $paciente->TIPO_SANGRE = $request->TIPO_SANGRE;
            $paciente->ALERGIAS_PRINCIPALES = $request->ALERGIAS_PRINCIPALES;
            $paciente->SEXO = $request->SEXO;

            $paciente->save();

            return response()->json([
                'message' => 'Perfil actualizado correctamente',
                'paciente' => $paciente,
                'foto_url' => $rutaFoto ? asset('storage/' . $rutaFoto) : null // Devolvemos la URL completa para React
            ]);

        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return response()->json(['message' => 'Error en el servidor: ' . $e->getMessage()], 500);
        }
    }
    // GET: /api/pacientes/perfil/{id_usuario}
    public function getProfile($id_usuario)
    {
        // 1. Buscar usuario
        $user = User::where('ID_USUARIO', $id_usuario)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // 2. Buscar paciente
        $paciente = Paciente::where('ID_USUARIO', $id_usuario)->first();

        // 3. Mezclar todo (USANDO MINÚSCULAS PARA LEER EL MODELO)
        // Nota: El driver de Oracle (Yajra) suele devolver los atributos en minúscula.

        return response()->json([
            // Clave JSON (Mayúscula) => Valor del Modelo (Minúscula)
            'ID_USUARIO' => $user->id_usuario,

            // Si existe paciente usamos su nombre, si no, el del usuario
            'NOMBRE_COMPLETO' => $paciente ? $paciente->nombre_completo : $user->nombre,

            'CORREO' => $user->correo,

            // Datos médicos (leemos en minúscula del objeto $paciente)
            'TELEFONO' => $paciente ? $paciente->telefono : '',
            'FECHA_NACIMIENTO' => $paciente ? $paciente->fecha_nacimiento : '',
            'TIPO_SANGRE' => $paciente ? $paciente->tipo_sangre : '',
            'ALERGIAS_PRINCIPALES' => $paciente ? $paciente->alergias_principales : '',
            'SEXO' => $paciente ? $paciente->sexo : 'F',

            // Foto
            'FOTO_PERFIL' => $user->foto_perfil ? asset('storage/' . $user->foto_perfil) : null
        ]);
    }
}
