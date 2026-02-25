<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\User;
use App\Models\Cita;
use App\Models\HorarioMedico;
use App\Models\Medico;

class CitaController extends Controller
{
    // GET: /api/medicos
    public function getMedicos()
    {
        try {
            // Traemos médicos con su usuario
            $medicos = Medico::with('usuario')->get();

            // Formateamos la respuesta de forma segura
            $data = $medicos->map(function($medico) {
                $user = $medico->usuario;

                // Truco para leer mayúsculas o minúsculas sin que PHP explote
                $fotoPerfil = $user ? ($user->FOTO_PERFIL ?? $user->foto_perfil) : null;
                $nombreUser = $user ? ($user->NOMBRE ?? $user->nombre) : 'Dr. Sin Nombre';

                return [
                    'id_medico' => $medico->ID_MEDICO ?? $medico->id_medico,
                    'nombre' => $nombreUser,
                    'especialidad' => $medico->ESPECIALIDAD ?? $medico->especialidad,
                    'foto' => $fotoPerfil ? asset('storage/' . $fotoPerfil) : null,
                    'bio' => $medico->BIO ?? $medico->bio
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            // Si algo falla, Laravel nos dirá exactamente QUÉ falló en lugar de un error 500 genérico
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // GET: /api/horarios-disponibles?id_medico=1&fecha=2026-03-15
    public function getHorariosDisponibles(Request $request)
    {
        try {
            $idMedico = $request->query('id_medico');
            $fechaString = $request->query('fecha');

            if (!$idMedico || !$fechaString) {
                return response()->json(['error' => 'Faltan parámetros'], 400);
            }

            // 1. Obtener día de la semana
            $fecha = Carbon::parse($fechaString);
            $diaSemana = $fecha->dayOfWeekIso;

            // 2. Buscar horario
            $horario = HorarioMedico::where('ID_MEDICO', $idMedico)
                                    ->where('DIA_SEMANA', $diaSemana)
                                    ->first();

            if (!$horario) {
                return response()->json([]); // No trabaja
            }

            // 3. Generar Slots (Aseguramos mayúsculas/minúsculas para Oracle)
            $slots = [];
            // Oracle suele devolver los atributos en minúscula, usamos el ?? para cubrir ambos casos
            $horaInicioAttr = $horario->HORA_INICIO ?? $horario->hora_inicio;
            $horaFinAttr = $horario->HORA_FIN ?? $horario->hora_fin;
            $duracionAttr = $horario->DURACION_CITA ?? $horario->duracion_cita;

            $horaInicio = Carbon::parse($horaInicioAttr);
            $horaFin = Carbon::parse($horaFinAttr);
            $duracionMinutos = $duracionAttr;

            while ($horaInicio < $horaFin) {
                $slots[] = $horaInicio->format('H:i');
                $horaInicio->addMinutes($duracionMinutos);
            }

            // 4. Buscar citas ocupadas
            $citasOcupadas = Cita::where('ID_MEDICO', $idMedico)
                                 ->whereDate('FECHA_HORA', $fechaString)
                                 ->where('ESTADO', '!=', 'CANCELADA')
                                 ->get();

            $horasOcupadas = $citasOcupadas->map(function ($cita) {
                return Carbon::parse($cita->FECHA_HORA ?? $cita->fecha_hora)->format('H:i');
            })->toArray();

            // 5. Filtrar
            $horasDisponibles = array_diff($slots, $horasOcupadas);

            return response()->json(array_values($horasDisponibles));

        } catch (\Exception $e) {
            // AQUÍ ESTÁ LA MAGIA: Si algo explota, nos dirá el porqué exacto
            return response()->json(['error' => 'Error interno: ' . $e->getMessage(), 'linea' => $e->getLine()], 500);
        }
    }
    // POST: /api/citas
    public function store(Request $request)
    {
        // 1. Validar que React nos mandó todo lo necesario
        $request->validate([
            'ID_PACIENTE' => 'required',
            'ID_MEDICO' => 'required',
            'FECHA' => 'required|date',
            'HORA' => 'required', // Ej: "09:00"
        ]);

        try {
            // 2. Unir la fecha y la hora para Oracle (Que usa tipo DATE con hora incluida)
            // Transformamos "2026-03-15" y "09:00" en "2026-03-15 09:00:00"
            $fechaHoraString = $request->FECHA . ' ' . $request->HORA . ':00';
            $fechaHora = Carbon::parse($fechaHoraString);

            // 3. Crear la Cita
            $cita = new Cita();
            $cita->ID_PACIENTE = $request->ID_PACIENTE;
            $cita->ID_MEDICO = $request->ID_MEDICO;
            $cita->ID_USUARIO_REGISTRO = $request->ID_USUARIO_REGISTRO; // Quien la agendó
            $cita->FECHA_HORA = $fechaHora;
            $cita->MOTIVO = $request->MOTIVO;
            $cita->ESTADO = 'PENDIENTE'; // Todas nacen pendientes

            $cita->save();

            return response()->json([
                'message' => 'Cita guardada correctamente',
                'cita' => $cita
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar en BD: ' . $e->getMessage()], 500);
        }
    }
    // GET: /api/pacientes/{id_usuario}/citas
    public function getMisCitas($id_usuario)
    {
        try {
            // Buscamos las citas de este paciente, trayendo también la info del médico
            $citas = Cita::with(['medico.usuario'])
                         ->where('ID_PACIENTE', $id_usuario)
                         ->orderBy('FECHA_HORA', 'asc') // Ordenar por fecha, las más próximas primero
                         ->get();

            $data = $citas->map(function($cita) {
                // Truco para las mayúsculas/minúsculas de Oracle
                $fechaHoraStr = $cita->FECHA_HORA ?? $cita->fecha_hora;
                $fechaObj = Carbon::parse($fechaHoraStr);

                $medicoUser = $cita->medico->usuario ?? null;
                $nombreMedico = $medicoUser ? ($medicoUser->NOMBRE ?? $medicoUser->nombre) : 'Especialista';

                return [
                    'id_cita' => $cita->ID_CITAS ?? $cita->id_citas,
                    'fecha' => $fechaObj->format('Y-m-d'), // Para validaciones si quieres
                    'fecha_formateada' => $fechaObj->format('d/m/Y'), // "25/03/2026"
                    'hora' => $fechaObj->format('H:i'), // "09:00"
                    'medico' => $nombreMedico,
                    'estado' => $cita->ESTADO ?? $cita->estado,
                    'motivo' => $cita->MOTIVO ?? $cita->motivo,
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener citas: ' . $e->getMessage()], 500);
        }
    }
}
