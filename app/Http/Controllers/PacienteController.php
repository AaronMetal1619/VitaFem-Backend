<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

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
}
