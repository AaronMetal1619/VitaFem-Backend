<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\Cita;
use App\Models\HorarioMedico;
use App\Models\Medico;
use App\Models\Paciente;

class CitaController extends Controller
{
    // GET: /api/medicos
    public function getMedicos()
    {
        try {
            $medicos = Medico::all();

            $data = $medicos->map(function($medico) {
                // Truco infalible: Buscar al usuario manualmente asegurando mayúsculas/minúsculas
                $idUsuario = $medico->ID_USUARIO ?? $medico->id_usuario;
                $user = User::where('ID_USUARIO', $idUsuario)->orWhere('id_usuario', $idUsuario)->first();

                // Obtenemos foto y nombre, cubriendo todas las posibilidades de Oracle
                $fotoPerfil = $user ? ($user->FOTO_PERFIL ?? $user->foto_perfil) : null;
                $nombreUser = $user ? ($user->NOMBRE ?? $user->nombre ?? $user->NOMBRE_COMPLETO ?? $user->nombre_completo) : 'Dr. Sin Nombre';

                return [
                    'id_medico' => $medico->ID_MEDICO ?? $medico->id_medico,
                    'nombre' => $nombreUser,
                    'especialidad' => $medico->ESPECIALIDAD ?? $medico->especialidad,
                    'foto' => $fotoPerfil ? asset('storage/' . $fotoPerfil) : 'https://cdn-icons-png.flaticon.com/512/3774/3774299.png', // Foto genérica de doctor por si no tiene
                    'bio' => $medico->BIO ?? $medico->bio
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
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
    // ==========================================
    // MÓDULO ADMINISTRATIVO / SECRETARIA
    // ==========================================

    // GET: /api/admin/citas
    public function getAllCitas()
    {
        try {
            // Traemos TODAS las citas con los datos del médico y del paciente
            $citas = Cita::with(['medico.usuario', 'paciente'])
                         ->orderBy('FECHA_HORA', 'asc') // Las más próximas primero
                         ->get();

            $data = $citas->map(function($cita) {
                // Fechas
                $fechaHoraStr = $cita->FECHA_HORA ?? $cita->fecha_hora;
                $fechaObj = Carbon::parse($fechaHoraStr);

                // Médico
                $medicoUser = $cita->medico->usuario ?? null;
                $nombreMedico = $medicoUser ? ($medicoUser->NOMBRE ?? $medicoUser->nombre) : 'Doctor';

                // Paciente
                $paciente = $cita->paciente ?? null;
                $nombrePaciente = $paciente ? ($paciente->NOMBRE_COMPLETO ?? $paciente->nombre_completo) : 'Paciente Sin Nombre';

                return [
                    'id_cita' => $cita->ID_CITAS ?? $cita->id_citas,
                    'paciente' => $nombrePaciente,
                    'fecha_formateada' => $fechaObj->format('d/m/Y'),
                    'hora' => $fechaObj->format('H:i'),
                    'medico' => $nombreMedico,
                    'estado' => $cita->ESTADO ?? $cita->estado,
                    'motivo' => $cita->MOTIVO ?? $cita->motivo,
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener todas las citas: ' . $e->getMessage()], 500);
        }
    }

    // PUT: /api/admin/citas/{id}/estado
    public function updateEstado(Request $request, $id)
    {
        try {
            $nuevoEstado = $request->estado; // Esperamos recibir: 'CONFIRMADA', 'CANCELADA', o 'FINALIZADA'

            Cita::where('ID_CITAS', $id)
                ->orWhere('id_citas', $id) // Doble validación por si Oracle lo pasa a minúsculas
                ->update(['ESTADO' => $nuevoEstado]);

            return response()->json(['message' => 'Estado de la cita actualizado a: ' . $nuevoEstado]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cambiar estado: ' . $e->getMessage()], 500);
        }
    }
    // ==========================================
    // MÓDULO DEL MÉDICO
    // ==========================================

    // GET: /api/medico/{id_usuario}/citas
    public function getCitasMedico(Request $request, $id_usuario)
    {
        try {
            // 1. Encontrar al médico (Doble validación para Oracle: mayúsculas y minúsculas)
            $medico = Medico::where('ID_USUARIO', $id_usuario)
                            ->orWhere('id_usuario', $id_usuario)
                            ->first();

            if (!$medico) {
                return response()->json(['error' => 'No eres un médico registrado en el sistema'], 404);
            }

            $idMedicoReal = $medico->ID_MEDICO ?? $medico->id_medico;

            $fechaFiltro = $request->query('fecha'); // Recibimos la fecha de React para mostrar solo las citas de ese día
            $query = Cita::with(['paciente'])
                         ->where(function($q) use ($idMedicoReal) {
                             $q->where('ID_MEDICO', $idMedicoReal)->orWhere('id_medico', $idMedicoReal);
                         });

            if ($fechaFiltro) {
                $query->whereDate('FECHA_HORA', $fechaFiltro); // Buscar por día exacto
            } else {
                $query->whereDate('FECHA_HORA', '>=', Carbon::today()); // Default: Hoy en adelante
            }
            // --------------------------------------------

            $citas = $query->orderBy('FECHA_HORA', 'asc')->get();
            // 2. Traemos SOLO las citas de este médico
            // (Quitamos la validación de fecha estricta para que Oracle no bloquee el "a. m.")
            $citas = Cita::with(['paciente'])
                         ->where('ID_MEDICO', $idMedicoReal)
                         ->orWhere('id_medico', $idMedicoReal)
                         ->orderBy('FECHA_HORA', 'asc')
                         ->get();

            $data = $citas->map(function($cita) {
                // Fechas
                $fechaHoraStr = $cita->FECHA_HORA ?? $cita->fecha_hora;
                $fechaObj = Carbon::parse($fechaHoraStr);

                // Paciente
                $paciente = $cita->paciente ?? null;
                $nombrePaciente = $paciente ? ($paciente->NOMBRE_COMPLETO ?? $paciente->nombre_completo) : 'Paciente Sin Nombre';

                return [
                    'id_cita' => $cita->ID_CITAS ?? $cita->id_citas,
                    'paciente' => $nombrePaciente,
                    'fecha_formateada' => $fechaObj->format('d/m/Y'),
                    'hora' => $fechaObj->format('H:i'),
                    'estado' => $cita->ESTADO ?? $cita->estado,
                    'motivo' => $cita->MOTIVO ?? $cita->motivo,
                    'notas_medicas' => $cita->NOTAS_MEDICAS ?? $cita->notas_medicas // Para ver si ya la atendió
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener citas del médico: ' . $e->getMessage()], 500);
        }
    }

    // PUT: /api/medico/citas/{id}/atender
    public function atenderCita(Request $request, $id)
    {
        try {
            // Validamos que el médico mande sus notas
            $request->validate([
                'notas_medicas' => 'required|string'
            ]);

            // Actualizamos la cita: Guardamos las notas y la marcamos como FINALIZADA
            Cita::where('ID_CITAS', $id)
                ->orWhere('id_citas', $id)
                ->update([
                    'NOTAS_MEDICAS' => $request->notas_medicas,
                    'ESTADO' => 'FINALIZADA'
                ]);

            return response()->json(['message' => 'Cita atendida y finalizada correctamente']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar notas médicas: ' . $e->getMessage()], 500);
        }
    }
    // POST: /api/admin/medicos
    public function crearMedico(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string',
            'correo' => 'required|email|unique:USUARIOS,CORREO', // Evita correos duplicados
            'password' => 'required|min:6',
            'especialidad' => 'required|string',
            'cedula' => 'required|string',
        ]);

        // Usamos una Transacción: Si algo falla, no se guarda nada a medias
        DB::beginTransaction();
        try {
            // 1. Crear Usuario (Login)
            $user = new User();
            $user->NOMBRE = $request->nombre;
            $user->CORREO = $request->correo;
            $user->PASSWORD = Hash::make($request->password); // Encriptado seguro
            $user->ACTIVO = '1';
            $user->save();

            // 2. Asignar Rol de Médico (Asumiendo que el ID_ROLES 2 es Médico)
            DB::table('USUARIO_ROL')->insert([
                'ID_USUARIO' => $user->ID_USUARIO ?? $user->id_usuario,
                'ID_ROLES' => 2
            ]);

            // 3. Crear Perfil Médico
            $medico = new Medico();
            $medico->ID_USUARIO = $user->ID_USUARIO ?? $user->id_usuario;
            $medico->ESPECIALIDAD = $request->especialidad;
            $medico->CEDULA = $request->cedula;
            $medico->BIO = 'Especialista en ' . $request->especialidad . ' de VitaFem.';
            $medico->save();

            // 4. TRUCO DE ORO: Le creamos un horario base (Lunes a Viernes 9 a 5)
            // para que aparezca en la lista de citas automáticamente
            for ($i = 1; $i <= 5; $i++) {
                HorarioMedico::create([
                    'ID_MEDICO' => $medico->ID_MEDICO ?? $medico->id_medico,
                    'DIA_SEMANA' => $i,
                    'HORA_INICIO' => '09:00',
                    'HORA_FIN' => '17:00',
                    'DURACION_CITA' => 60
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Médico registrado exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al registrar médico: ' . $e->getMessage()], 500);
        }
    }
    // GET: /api/medico/{id_usuario}/horarios
    public function getHorarios($id_usuario)
    {
        try {
            $medico = Medico::where('ID_USUARIO', $id_usuario)->orWhere('id_usuario', $id_usuario)->first();
            if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);

            $idMedicoReal = $medico->ID_MEDICO ?? $medico->id_medico;
            $horariosDB = HorarioMedico::where('ID_MEDICO', $idMedicoReal)->orWhere('id_medico', $idMedicoReal)->get();

            // Días de la semana (1=Lunes, 7=Domingo según estándar ISO)
            $diasNombres = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
            $horariosFormateados = [];

            for ($i = 1; $i <= 7; $i++) {
                // Buscamos si el médico tiene registrado este día en Oracle
                $h = $horariosDB->firstWhere('DIA_SEMANA', $i) ?? $horariosDB->firstWhere('dia_semana', $i);

                $horariosFormateados[] = [
                    'dia_semana' => $i,
                    'nombre_dia' => $diasNombres[$i],
                    'activo' => $h ? true : false,
                    'hora_inicio' => $h ? Carbon::parse($h->HORA_INICIO ?? $h->hora_inicio)->format('H:i') : '09:00',
                    'hora_fin' => $h ? Carbon::parse($h->HORA_FIN ?? $h->hora_fin)->format('H:i') : '17:00',
                    'duracion_cita' => $h ? ($h->DURACION_CITA ?? $h->duracion_cita) : 60
                ];
            }

            return response()->json($horariosFormateados);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener horarios: ' . $e->getMessage()], 500);
        }
    }

    // PUT: /api/medico/{id_usuario}/horarios
    public function updateHorarios(Request $request, $id_usuario)
    {
        try {
            $medico = Medico::where('ID_USUARIO', $id_usuario)->orWhere('id_usuario', $id_usuario)->first();
            if (!$medico) return response()->json(['error' => 'Médico no encontrado'], 404);
            $idMedicoReal = $medico->ID_MEDICO ?? $medico->id_medico;

            $horariosRequest = $request->all(); // Recibimos el arreglo desde React

            // Usamos transacción para no dejar datos a medias
            DB::beginTransaction();

            // Truco para Oracle: Borramos todos los horarios de este médico y los volvemos a insertar
            // Esto evita problemas de llaves duplicadas o nulas.
            HorarioMedico::where('ID_MEDICO', $idMedicoReal)->orWhere('id_medico', $idMedicoReal)->delete();

            foreach ($horariosRequest as $h) {
                if ($h['activo']) {
                    $nuevoHorario = new HorarioMedico();
                    $nuevoHorario->ID_MEDICO = $idMedicoReal;
                    $nuevoHorario->DIA_SEMANA = $h['dia_semana'];
                    $nuevoHorario->HORA_INICIO = $h['hora_inicio'];
                    $nuevoHorario->HORA_FIN = $h['hora_fin'];
                    $nuevoHorario->DURACION_CITA = $h['duracion_cita'];
                    $nuevoHorario->save();
                }
            }

            DB::commit();
            return response()->json(['message' => 'Horario actualizado correctamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar horarios: ' . $e->getMessage()], 500);
        }
    }
}
