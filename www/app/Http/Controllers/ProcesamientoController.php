<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Adjunto;
use App\Models\EntidadDetectada;
use App\Models\AsignacionTranscripcion;
use App\Models\AsignacionAnonimizacion;
use App\Models\Entrevistador;
use App\Services\ProcesamientoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcesamientoController extends Controller
{
    protected $procesamientoService;

    public function __construct(ProcesamientoService $procesamientoService)
    {
        $this->middleware('auth');
        $this->procesamientoService = $procesamientoService;
    }

    /**
     * Vista principal de procesamientos
     */
    public function index()
    {
        // Estadisticas generales
        $totalEntrevistas = Entrevista::where('id_activo', 1)->count();

        // Contar entrevistas con audio o video
        $conAudio = Adjunto::where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->distinct('id_e_ind_fvt')
            ->count('id_e_ind_fvt');

        // Estadísticas reales de procesamiento
        $transcritas = Entrevista::where('id_activo', 1)
            ->whereNotNull('transcripcion_completada_at')
            ->count();

        $conEntidades = Entrevista::where('id_activo', 1)
            ->whereNotNull('entidades_detectadas_at')
            ->count();

        $anonimizadas = Entrevista::where('id_activo', 1)
            ->whereNotNull('anonimizacion_completada_at')
            ->count();

        // Trabajos en cola
        $trabajosEnCola = DB::table('esclarecimiento.trabajo_procesamiento')
            ->where('estado', 'pendiente')
            ->count();

        $trabajosProcesando = DB::table('esclarecimiento.trabajo_procesamiento')
            ->where('estado', 'procesando')
            ->count();

        $stats = [
            'total_entrevistas' => $totalEntrevistas,
            'con_audio' => $conAudio,
            'transcritas' => $transcritas,
            'con_entidades' => $conEntidades,
            'anonimizadas' => $anonimizadas,
            'trabajos_en_cola' => $trabajosEnCola,
            'trabajos_procesando' => $trabajosProcesando,
        ];

        // Estado de los servicios
        $servicios = $this->procesamientoService->getServicesInfo();

        return view('procesamientos.index', compact('stats', 'servicios'));
    }

    /**
     * Estado de los servicios (AJAX)
     */
    public function serviciosStatus()
    {
        return response()->json($this->procesamientoService->getServicesInfo());
    }

    /**
     * Transcripcion automatizada
     */
    public function transcripcion()
    {
        // Entrevistas con audio o video
        $entrevistas = Entrevista::where('id_activo', 1)
            ->whereHas('rel_adjuntos', function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->with(['rel_adjuntos' => function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $enProceso = collect();

        // Estado del servicio
        $servicioStatus = $this->procesamientoService->transcriptionStatus();

        return view('procesamientos.transcripcion', compact('entrevistas', 'enProceso', 'servicioStatus'));
    }

    /**
     * Iniciar transcripcion de una entrevista (todos los audios)
     */
    public function iniciarTranscripcion(Request $request, $id)
    {
        $entrevista = Entrevista::findOrFail($id);

        // Obtener archivos de audio o video
        $audios = Adjunto::where('id_e_ind_fvt', $id)
            ->where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->orderBy('id_adjunto')
            ->get();

        if ($audios->isEmpty()) {
            return response()->json(['error' => 'No hay archivos de audio o video'], 400);
        }

        // Opciones de transcripcion
        $withDiarization = $request->input('diarizar', true);
        $hfToken = $request->input('hf_token', '');

        $transcripcionesCompletas = [];
        $totalCaracteres = 0;
        $totalHablantes = 0;
        $errores = [];

        // Transcribir cada archivo de audio
        foreach ($audios as $audio) {
            $audioPath = Storage::disk('public')->path($audio->ubicacion);

            if (!file_exists($audioPath)) {
                $errores[] = "Archivo no encontrado: {$audio->nombre_original}";
                continue;
            }

            // Llamar al servicio de transcripcion
            $result = $this->procesamientoService->transcribe($audioPath, $withDiarization, $hfToken);

            if ($result['success']) {
                $texto = trim($result['text'] ?? '');

                // Validar que el texto no esté vacío
                if (empty($texto)) {
                    $errores[] = "Sin texto en {$audio->nombre_original}: El audio no contiene voz detectable o está vacío";
                    continue;
                }

                // Guardar transcripcion en el adjunto
                $audio->texto_extraido = $texto;
                $audio->texto_extraido_at = now();
                $audio->save();

                // Acumular para el resumen
                $transcripcionesCompletas[] = [
                    'archivo' => $audio->nombre_original,
                    'texto' => $texto,
                    'caracteres' => strlen($texto),
                    'hablantes' => $result['speakers_count'] ?? 0,
                    'diarization_error' => $result['diarization_error'] ?? null
                ];

                $totalCaracteres += strlen($texto);
                $totalHablantes = max($totalHablantes, $result['speakers_count'] ?? 0);
            } else {
                $errores[] = "Error en {$audio->nombre_original}: " . ($result['error'] ?? 'Error desconocido');
            }
        }

        if (empty($transcripcionesCompletas)) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo transcribir ningun archivo. ' . implode('; ', $errores)
            ], 500);
        }

        // Concatenar todas las transcripciones
        $textoCompleto = '';
        foreach ($transcripcionesCompletas as $t) {
            if (count($transcripcionesCompletas) > 1) {
                $textoCompleto .= "\n\n=== {$t['archivo']} ===\n\n";
            }
            $textoCompleto .= $t['texto'];
        }

        // Guardar como adjunto de tipo "Transcripción Automatizada"
        $entrevista->guardarTranscripcionAutomatizada(trim($textoCompleto));

        // Recopilar errores de diarizacion
        $diarizacionErrors = array_filter(array_column($transcripcionesCompletas, 'diarization_error'));
        $diarizacionError = !empty($diarizacionErrors) ? $diarizacionErrors[0] : null;

        return response()->json([
            'success' => true,
            'message' => 'Transcripcion completada',
            'entrevista_id' => $id,
            'archivos_procesados' => count($transcripcionesCompletas),
            'archivos_total' => $audios->count(),
            'text_length' => $totalCaracteres,
            'text' => $textoCompleto,
            'speakers' => $totalHablantes,
            'errores' => $errores,
            'diarization_error' => $diarizacionError
        ]);
    }

    /**
     * Transcribir un adjunto individual
     */
    public function transcribirAdjunto(Request $request, $idAdjunto)
    {
        $adjunto = Adjunto::findOrFail($idAdjunto);
        $entrevista = Entrevista::findOrFail($adjunto->id_e_ind_fvt);

        // Verificar que sea audio o video
        $tipo = $adjunto->tipo_mime ?? '';
        if (strpos($tipo, 'audio') === false && strpos($tipo, 'video') === false) {
            return response()->json(['error' => 'El archivo no es audio ni video'], 400);
        }

        $audioPath = Storage::disk('public')->path($adjunto->ubicacion);

        if (!file_exists($audioPath)) {
            return response()->json(['error' => 'Archivo no encontrado: ' . $adjunto->nombre_original], 400);
        }

        $withDiarization = $request->input('diarizar', true);

        // Llamar al servicio de transcripcion
        $result = $this->procesamientoService->transcribe($audioPath, $withDiarization);

        if ($result['success']) {
            $texto = trim($result['text'] ?? '');

            // Validar que el texto no esté vacío
            if (empty($texto)) {
                return response()->json([
                    'success' => false,
                    'error' => 'El audio no contiene voz detectable o está vacío'
                ], 400);
            }

            // Guardar transcripcion en el adjunto
            $adjunto->texto_extraido = $texto;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();

            // Regenerar la transcripcion completa
            $this->regenerarTranscripcionCompleta($entrevista);

            return response()->json([
                'success' => true,
                'message' => 'Transcripcion completada',
                'id_adjunto' => $idAdjunto,
                'nombre' => $adjunto->nombre_original,
                'text_length' => strlen($texto),
                'text' => $texto,
                'speakers' => $result['speakers_count'] ?? 0
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Error desconocido'
        ], 500);
    }

    /**
     * Procesar transcripciones en lote (Server-Sent Events)
     */
    public function transcripcionLote(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        $withDiarization = $request->input('diarizar', true);
        $hfToken = $request->input('hf_token', '');

        return response()->stream(function() use ($ids, $withDiarization, $hfToken) {
            // Desactivar buffer de salida
            if (ob_get_level()) ob_end_clean();

            $total = count($ids);
            $procesados = 0;
            $exitosos = 0;
            $errores = 0;

            // Enviar inicio
            $this->sendSSE('inicio', [
                'total' => $total,
                'mensaje' => "Iniciando procesamiento de {$total} entrevista(s)..."
            ]);

            foreach ($ids as $id) {
                $procesados++;

                try {
                    $entrevista = Entrevista::find($id);

                    if (!$entrevista) {
                        $this->sendSSE('error', [
                            'id' => $id,
                            'procesados' => $procesados,
                            'total' => $total,
                            'mensaje' => "Entrevista {$id} no encontrada"
                        ]);
                        $errores++;
                        continue;
                    }

                    $codigo = $entrevista->entrevista_codigo;

                    // Notificar que estamos procesando
                    $this->sendSSE('procesando', [
                        'id' => $id,
                        'codigo' => $codigo,
                        'procesados' => $procesados,
                        'total' => $total,
                        'mensaje' => "Transcribiendo: {$codigo} ({$procesados}/{$total})"
                    ]);

                    // Obtener archivos de audio
                    $audios = Adjunto::where('id_e_ind_fvt', $id)
                        ->where(function($q) {
                            $q->where('tipo_mime', 'like', '%audio%')
                              ->orWhere('tipo_mime', 'like', '%video%');
                        })
                        ->get();

                    if ($audios->isEmpty()) {
                        $this->sendSSE('error', [
                            'id' => $id,
                            'codigo' => $codigo,
                            'procesados' => $procesados,
                            'total' => $total,
                            'mensaje' => "Sin archivos de audio: {$codigo}"
                        ]);
                        $errores++;
                        continue;
                    }

                    // Obtener ruta del audio
                    $primerAudio = $audios->first();
                    $audioPath = Storage::disk('public')->path($primerAudio->ubicacion);

                    if (!file_exists($audioPath)) {
                        $this->sendSSE('error', [
                            'id' => $id,
                            'codigo' => $codigo,
                            'procesados' => $procesados,
                            'total' => $total,
                            'mensaje' => "Archivo no encontrado: {$codigo}"
                        ]);
                        $errores++;
                        continue;
                    }

                    // Llamar al servicio de transcripcion
                    $result = $this->procesamientoService->transcribe($audioPath, $withDiarization, $hfToken);

                    if ($result['success']) {
                        $texto = trim($result['text'] ?? '');

                        // Validar que el texto no esté vacío
                        if (empty($texto)) {
                            $errores++;
                            $this->sendSSE('error', [
                                'id' => $id,
                                'codigo' => $codigo,
                                'procesados' => $procesados,
                                'total' => $total,
                                'mensaje' => "Sin texto en {$codigo}: El audio no contiene voz detectable"
                            ]);
                            continue;
                        }

                        // Guardar como adjunto de tipo "Transcripción Automatizada"
                        $entrevista->guardarTranscripcionAutomatizada($texto);

                        $exitosos++;

                        $this->sendSSE('exito', [
                            'id' => $id,
                            'codigo' => $codigo,
                            'procesados' => $procesados,
                            'total' => $total,
                            'caracteres' => strlen($texto),
                            'hablantes' => $result['speakers_count'] ?? 0,
                            'mensaje' => "Completado: {$codigo} (" . number_format(strlen($texto)) . " caracteres)"
                        ]);
                    } else {
                        $errores++;
                        $this->sendSSE('error', [
                            'id' => $id,
                            'codigo' => $codigo,
                            'procesados' => $procesados,
                            'total' => $total,
                            'mensaje' => "Error en {$codigo}: " . ($result['error'] ?? 'Error desconocido')
                        ]);
                    }

                } catch (\Exception $e) {
                    $errores++;
                    $this->sendSSE('error', [
                        'id' => $id,
                        'procesados' => $procesados,
                        'total' => $total,
                        'mensaje' => "Excepcion: " . $e->getMessage()
                    ]);
                }
            }

            // Enviar fin
            $this->sendSSE('fin', [
                'total' => $total,
                'exitosos' => $exitosos,
                'errores' => $errores,
                'mensaje' => "Procesamiento completado: {$exitosos} exitosos, {$errores} errores"
            ]);

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Enviar evento SSE
     */
    private function sendSSE($event, $data)
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";

        if (ob_get_level()) ob_flush();
        flush();
    }

    /**
     * Edicion de transcripciones
     */
    public function edicion()
    {
        $user = Auth::user();
        $nivel = $user->id_nivel;

        // Transcriptor (nivel 4): solo ve sus asignaciones
        if ($nivel == 4) {
            // Incluir aprobadas para que vea su trabajo finalizado
            $asignaciones = AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                ->with(['rel_entrevista', 'rel_entrevista.rel_adjuntos' => function($q) {
                    $q->where('tipo_mime', 'like', '%audio%')
                      ->orWhere('tipo_mime', 'like', '%video%');
                }])
                ->orderByRaw("CASE estado
                    WHEN 'rechazada' THEN 1
                    WHEN 'asignada' THEN 2
                    WHEN 'en_edicion' THEN 3
                    WHEN 'enviada_revision' THEN 4
                    WHEN 'aprobada' THEN 5
                    ELSE 6 END")
                ->orderBy('fecha_asignacion', 'desc')
                ->paginate(20);

            $stats = [
                'asignadas' => AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                    ->where('estado', AsignacionTranscripcion::ESTADO_ASIGNADA)->count(),
                'en_edicion' => AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                    ->where('estado', AsignacionTranscripcion::ESTADO_EN_EDICION)->count(),
                'enviadas' => AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                    ->where('estado', AsignacionTranscripcion::ESTADO_ENVIADA_REVISION)->count(),
                'rechazadas' => AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                    ->where('estado', AsignacionTranscripcion::ESTADO_RECHAZADA)->count(),
                'aprobadas' => AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                    ->where('estado', AsignacionTranscripcion::ESTADO_APROBADA)->count(),
            ];

            return view('procesamientos.edicion-transcriptor', compact('asignaciones', 'stats'));
        }

        // Admin/Líder: ve todas las entrevistas y asignaciones
        $pendientes = Entrevista::where('id_activo', 1)
            ->whereHas('rel_adjuntos', function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->with(['rel_adjuntos' => function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Asignaciones pendientes de revisión
        $pendientesRevision = AsignacionTranscripcion::where('estado', AsignacionTranscripcion::ESTADO_ENVIADA_REVISION)
            ->with(['rel_entrevista', 'rel_transcriptor.rel_usuario'])
            ->orderBy('fecha_envio_revision', 'asc')
            ->get();

        // Transcriptores disponibles para asignar (nivel 4)
        $transcriptores = Entrevistador::where('id_nivel', 4)
            ->with('rel_usuario')
            ->get();

        // Cargar todas las asignaciones (incluyendo aprobadas) indexadas por id_e_ind_fvt
        // Para cada entrevista, obtener la asignación más reciente
        $asignacionesActivas = AsignacionTranscripcion::whereIn('estado', [
                AsignacionTranscripcion::ESTADO_ASIGNADA,
                AsignacionTranscripcion::ESTADO_EN_EDICION,
                AsignacionTranscripcion::ESTADO_ENVIADA_REVISION,
                AsignacionTranscripcion::ESTADO_RECHAZADA,
                AsignacionTranscripcion::ESTADO_APROBADA,
            ])
            ->with(['rel_transcriptor.rel_usuario', 'rel_revisor'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique('id_e_ind_fvt')
            ->keyBy('id_e_ind_fvt');

        // Contar solo asignaciones en proceso (no aprobadas)
        $enProceso = $asignacionesActivas->filter(function($a) {
            return $a->estado !== AsignacionTranscripcion::ESTADO_APROBADA;
        })->count();

        $stats = [
            'pendientes' => $pendientes->total(),
            'en_revision' => $pendientesRevision->count(),
            'asignadas' => $enProceso,
            'aprobadas' => AsignacionTranscripcion::where('estado', AsignacionTranscripcion::ESTADO_APROBADA)->count(),
        ];

        return view('procesamientos.edicion', compact('pendientes', 'pendientesRevision', 'transcriptores', 'asignacionesActivas', 'stats'));
    }

    /**
     * Editar transcripcion especifica
     */
    public function editarTranscripcion($id)
    {
        $entrevista = Entrevista::with(['rel_adjuntos' => function($q) {
            $q->where('tipo_mime', 'like', '%audio%')
              ->orWhere('tipo_mime', 'like', '%video%');
        }])->findOrFail($id);

        return view('procesamientos.editar-transcripcion', compact('entrevista'));
    }

    /**
     * Guardar transcripcion editada
     */
    public function guardarTranscripcion(Request $request, $id)
    {
        $request->validate([
            'transcripcion' => 'required|string',
            'id_adjunto' => 'nullable|integer',
        ]);

        $entrevista = Entrevista::findOrFail($id);
        $idAdjunto = $request->input('id_adjunto');

        if ($idAdjunto) {
            // Guardar transcripcion de un adjunto especifico
            $adjunto = Adjunto::where('id_adjunto', $idAdjunto)
                ->where('id_e_ind_fvt', $id)
                ->firstOrFail();

            $adjunto->texto_extraido = $request->transcripcion;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();

            // Regenerar la transcripcion completa concatenando todos los adjuntos
            $this->regenerarTranscripcionCompleta($entrevista);

            flash('Transcripcion del archivo guardada correctamente')->success();
        } else {
            // Guardar transcripcion completa como adjunto
            $entrevista->guardarTranscripcionAutomatizada($request->transcripcion);

            flash('Transcripcion guardada correctamente')->success();
        }

        return redirect()->back();
    }

    /**
     * Regenerar transcripcion completa concatenando todos los adjuntos de audio/video
     */
    private function regenerarTranscripcionCompleta(Entrevista $entrevista)
    {
        $adjuntos = Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->whereNotNull('texto_extraido')
            ->orderBy('id_adjunto')
            ->get();

        if ($adjuntos->count() > 1) {
            $textoCompleto = '';
            foreach ($adjuntos as $adjunto) {
                $textoCompleto .= "\n\n=== {$adjunto->nombre_original} ===\n\n";
                $textoCompleto .= $adjunto->texto_extraido;
            }
            // Guardar como adjunto de transcripción automatizada
            $entrevista->guardarTranscripcionAutomatizada(trim($textoCompleto));
        } elseif ($adjuntos->count() == 1) {
            $entrevista->guardarTranscripcionAutomatizada($adjuntos->first()->texto_extraido);
        }
    }

    /**
     * Aprobar transcripcion (solo Admin y Líder)
     * Guarda la transcripción actual como "Transcripción Final"
     */
    public function aprobarTranscripcion($id)
    {
        $user = auth()->user();

        // Solo Admin (1) y Líder (2) pueden aprobar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para aprobar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $entrevista = Entrevista::findOrFail($id);

        // Obtener el texto de la transcripción actual
        $textoTranscripcion = $entrevista->getTextoParaProcesamiento();

        if (empty($textoTranscripcion)) {
            flash('La entrevista no tiene transcripción para aprobar.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        // Guardar como Transcripción Final (adjunto tipo 306)
        $entrevista->guardarTranscripcionFinal($textoTranscripcion, $user->id);

        // Actualizar fecha de aprobación
        $entrevista->transcripcion_aprobada_at = now();
        $entrevista->transcripcion_aprobada_por = $user->id;
        $entrevista->save();

        flash('Transcripción aprobada y guardada como Transcripción Final.')->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Deteccion de entidades
     */
    public function entidades()
    {
        // Buscar entrevistas con transcripción (adjunto tipo 312 o anotaciones legacy)
        $pendientes = Entrevista::where('id_activo', 1)
            ->where(function($q) {
                // Tiene adjunto de transcripción automatizada
                $q->whereHas('rel_adjuntos', function($qa) {
                    $qa->where('id_tipo', Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
                       ->whereNotNull('texto_extraido')
                       ->where('texto_extraido', '!=', '');
                })
                // O tiene anotaciones legacy
                ->orWhere(function($ql) {
                    $ql->whereNotNull('anotaciones')
                       ->where('anotaciones', '!=', '');
                });
            })
            // Cargar adjuntos de audio/video para mostrar el conteo correcto
            ->with(['rel_adjuntos' => function($q) {
                $q->where(function($qa) {
                    $qa->where('tipo_mime', 'like', '%audio%')
                       ->orWhere('tipo_mime', 'like', '%video%');
                });
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $tiposEntidades = [
            'PER' => 'Personas',
            'LOC' => 'Lugares',
            'ORG' => 'Organizaciones',
            'MISC' => 'Miscelaneos',
            'DATE' => 'Fechas',
            'EVENT' => 'Eventos',
            'GUN' => 'Armas',
        ];

        return view('procesamientos.entidades', compact('pendientes', 'tiposEntidades'));
    }

    /**
     * Detectar entidades en una entrevista
     */
    public function detectarEntidades(Request $request, $id)
    {
        $entrevista = Entrevista::findOrFail($id);

        $textoTranscripcion = $entrevista->getTextoParaProcesamiento();
        if (empty($textoTranscripcion)) {
            return response()->json(['error' => 'La entrevista no tiene transcripcion'], 400);
        }

        // Obtener tipos a detectar (por defecto todos excepto MISC)
        $tiposPermitidos = ['PER', 'LOC', 'ORG', 'DATE', 'EVENT', 'GUN', 'MISC'];
        $tiposADetectar = $tiposPermitidos; // Por defecto todos

        if ($request->has('tipos') && !empty($request->tipos)) {
            $tiposADetectar = array_intersect(
                explode(',', $request->tipos),
                $tiposPermitidos
            );
        }

        // Llamar al servicio NER
        $result = $this->procesamientoService->detectEntities($textoTranscripcion);

        if ($result['success']) {
            // Eliminar entidades anteriores
            EntidadDetectada::where('id_e_ind_fvt', $id)->delete();

            // Guardar entidades en la base de datos (solo los tipos seleccionados)
            $contador = [];
            $entidadesGuardadas = 0;

            foreach ($result['entities'] as $entidad) {
                $tipo = $entidad['type'] ?? $entidad['label'] ?? 'MISC';

                // Solo guardar si el tipo está en los seleccionados
                if (!in_array($tipo, $tiposADetectar)) {
                    continue;
                }

                // Contador para texto_anonimizado
                if (!isset($contador[$tipo])) {
                    $contador[$tipo] = 0;
                }
                $contador[$tipo]++;

                EntidadDetectada::create([
                    'id_e_ind_fvt' => $id,
                    'tipo' => $tipo,
                    'texto' => $entidad['text'] ?? '',
                    'texto_anonimizado' => "[{$tipo}_{$contador[$tipo]}]",
                    'posicion_inicio' => $entidad['start'] ?? null,
                    'posicion_fin' => $entidad['end'] ?? null,
                    'confianza' => $entidad['score'] ?? null,
                ]);
                $entidadesGuardadas++;
            }

            // Actualizar fecha de detección de entidades
            $entrevista->entidades_detectadas_at = now();
            $entrevista->save();

            return response()->json([
                'success' => true,
                'message' => 'Entidades detectadas y guardadas',
                'entrevista_id' => $id,
                'tipos_detectados' => $tiposADetectar,
                'total_entidades' => $entidadesGuardadas,
                'por_tipo' => $contador
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Error desconocido'
        ], 500);
    }

    /**
     * Ver entidades de una entrevista
     */
    public function verEntidades($id)
    {
        $entrevista = Entrevista::findOrFail($id);

        // Obtener entidades de la base de datos
        $entidadesDB = EntidadDetectada::where('id_e_ind_fvt', $id)
            ->orderBy('posicion_inicio')
            ->get();

        // Convertir a formato esperado por la vista
        $entidades = $entidadesDB->map(function($e) {
            return [
                'text' => $e->texto,
                'type' => $e->tipo,
                'label' => $e->tipo,
                'start' => $e->posicion_inicio,
                'end' => $e->posicion_fin,
                'score' => $e->confianza,
                'id' => $e->id_entidad,
                'verificado' => $e->verificado,
                'excluir' => $e->excluir_anonimizacion,
            ];
        })->toArray();

        // Estadísticas por tipo
        $porTipo = $entidadesDB->groupBy('tipo')->map->count();

        return view('procesamientos.ver-entidades', compact('entrevista', 'entidades', 'porTipo'));
    }

    /**
     * Anonimizacion
     */
    public function anonimizacion()
    {
        $user = Auth::user();
        $nivel = $user->id_nivel;

        // Transcriptor (nivel 4): solo ve sus asignaciones de anonimizacion
        if ($nivel == 4) {
            // Incluir aprobadas para que vea su trabajo finalizado
            $asignaciones = AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                ->with(['rel_entrevista'])
                ->orderByRaw("CASE estado
                    WHEN 'rechazada' THEN 1
                    WHEN 'asignada' THEN 2
                    WHEN 'en_edicion' THEN 3
                    WHEN 'enviada_revision' THEN 4
                    WHEN 'aprobada' THEN 5
                    ELSE 6 END")
                ->orderBy('fecha_asignacion', 'desc')
                ->paginate(20);

            $stats = [
                'asignadas' => AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                    ->where('estado', AsignacionAnonimizacion::ESTADO_ASIGNADA)->count(),
                'en_edicion' => AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                    ->where('estado', AsignacionAnonimizacion::ESTADO_EN_EDICION)->count(),
                'enviadas' => AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                    ->where('estado', AsignacionAnonimizacion::ESTADO_ENVIADA_REVISION)->count(),
                'rechazadas' => AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                    ->where('estado', AsignacionAnonimizacion::ESTADO_RECHAZADA)->count(),
                'aprobadas' => AsignacionAnonimizacion::where('id_anonimizador', $user->id_entrevistador)
                    ->where('estado', AsignacionAnonimizacion::ESTADO_APROBADA)->count(),
            ];

            return view('procesamientos.anonimizacion-anonimizador', compact('asignaciones', 'stats'));
        }

        // Admin/Lider: ve todas las entrevistas y asignaciones
        // Buscar entrevistas con transcripción (adjunto tipo 312 o anotaciones legacy)
        $pendientes = Entrevista::where('id_activo', 1)
            ->with('rel_adjuntos')
            ->where(function($q) {
                $q->whereHas('rel_adjuntos', function($qa) {
                    $qa->where('id_tipo', Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
                       ->whereNotNull('texto_extraido')
                       ->where('texto_extraido', '!=', '');
                })
                ->orWhere(function($ql) {
                    $ql->whereNotNull('anotaciones')
                       ->where('anotaciones', '!=', '');
                });
            })
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Asignaciones pendientes de revision
        $pendientesRevision = AsignacionAnonimizacion::where('estado', AsignacionAnonimizacion::ESTADO_ENVIADA_REVISION)
            ->with(['rel_entrevista', 'rel_anonimizador.rel_usuario'])
            ->orderBy('fecha_envio_revision', 'asc')
            ->get();

        // Anonimizadores disponibles (nivel 4)
        $anonimizadores = Entrevistador::where('id_nivel', 4)
            ->with('rel_usuario')
            ->get();

        // Cargar asignaciones indexadas por id_e_ind_fvt (incluir aprobadas)
        $asignacionesActivas = AsignacionAnonimizacion::whereIn('estado', [
                AsignacionAnonimizacion::ESTADO_ASIGNADA,
                AsignacionAnonimizacion::ESTADO_EN_EDICION,
                AsignacionAnonimizacion::ESTADO_ENVIADA_REVISION,
                AsignacionAnonimizacion::ESTADO_RECHAZADA,
                AsignacionAnonimizacion::ESTADO_APROBADA,
            ])
            ->with(['rel_anonimizador.rel_usuario'])
            ->get()
            ->keyBy('id_e_ind_fvt');

        $stats = [
            'pendientes' => $pendientes->total(),
            'en_revision' => $pendientesRevision->count(),
            'asignadas' => $asignacionesActivas->whereNotIn('estado', ['aprobada'])->count(),
            'aprobadas' => AsignacionAnonimizacion::where('estado', AsignacionAnonimizacion::ESTADO_APROBADA)->count(),
        ];

        return view('procesamientos.anonimizacion', compact(
            'pendientes', 'pendientesRevision', 'anonimizadores',
            'asignacionesActivas', 'stats'
        ));
    }

    /**
     * Generar version anonimizada
     */
    public function generarAnonimizacion(Request $request, $id)
    {
        $entrevista = Entrevista::findOrFail($id);

        $textoTranscripcion = $entrevista->getTextoParaProcesamiento();
        if (empty($textoTranscripcion)) {
            return response()->json(['error' => 'La entrevista no tiene transcripcion'], 400);
        }

        // Tipos de entidades a anonimizar
        $tipos = $request->input('tipos', 'PER,LOC');
        $tiposArray = is_array($tipos) ? $tipos : explode(',', $tipos);
        $formato = $request->input('formato', 'brackets');

        // Llamar al servicio de anonimizacion
        $result = $this->procesamientoService->anonymize(
            $textoTranscripcion,
            $tiposArray,
            $formato
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Anonimizacion completada',
                'entrevista_id' => $id,
                'original_length' => strlen($textoTranscripcion),
                'anonymized_length' => strlen($result['anonymized_text'] ?? ''),
                'replacements' => $result['stats']['total_replaced'] ?? 0,
                'anonymized_text' => $result['anonymized_text'] ?? ''
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Error desconocido'
        ], 500);
    }

    /**
     * Anonimizar audio/video mediante distorsion con ffmpeg
     */
    public function anonimizarAudio($id)
    {
        $entrevista = Entrevista::with('rel_adjuntos')->findOrFail($id);

        // Filtrar adjuntos de audio/video con archivo fisico
        $adjuntosAV = $entrevista->rel_adjuntos->filter(function($a) {
            return ($a->es_audio || $a->es_video) && !empty($a->ubicacion);
        });

        if ($adjuntosAV->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No se encontraron adjuntos de audio/video'], 400);
        }

        $procesados = 0;
        $errores = [];

        foreach ($adjuntosAV as $adjunto) {
            $rutaEntrada = storage_path('app/public/' . $adjunto->ubicacion);

            if (!file_exists($rutaEntrada)) {
                $errores[] = "Archivo no encontrado: {$adjunto->nombre_original}";
                continue;
            }

            $directorio = dirname($adjunto->ubicacion);
            $extension = pathinfo($adjunto->ubicacion, PATHINFO_EXTENSION);
            $nombreSalida = 'anonimizado_' . pathinfo($adjunto->ubicacion, PATHINFO_FILENAME) . '.' . $extension;
            $ubicacionSalida = $directorio . '/' . $nombreSalida;
            $rutaSalida = storage_path('app/public/' . $ubicacionSalida);

            // Ejecutar ffmpeg con filtro de distorsion de voz
            $cmd = sprintf(
                'ffmpeg -i %s -af "asetrate=48000*0.85,aresample=48000,equalizer=f=1000:width_type=h:width=200:g=-10" -y %s 2>&1',
                escapeshellarg($rutaEntrada),
                escapeshellarg($rutaSalida)
            );

            exec($cmd, $output, $returnCode);

            if ($returnCode !== 0) {
                $errores[] = "Error ffmpeg en {$adjunto->nombre_original}: " . implode("\n", array_slice($output, -5));
                continue;
            }

            // Crear registro de adjunto anonimizado
            Adjunto::create([
                'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                'ubicacion' => $ubicacionSalida,
                'nombre_original' => '[Anonimizado] ' . $adjunto->nombre_original,
                'tipo_mime' => $adjunto->tipo_mime,
                'id_tipo' => $adjunto->id_tipo,
                'tamano' => file_exists($rutaSalida) ? filesize($rutaSalida) : null,
                'duracion' => $adjunto->duracion,
                'existe_archivo' => file_exists($rutaSalida) ? 1 : 0,
            ]);

            $procesados++;
        }

        if ($procesados === 0 && !empty($errores)) {
            return response()->json([
                'success' => false,
                'error' => implode('; ', $errores),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'procesados' => $procesados,
            'errores' => $errores,
        ]);
    }

    /**
     * Vista previa de anonimizacion
     */
    public function previsualizarAnonimizacion($id)
    {
        $entrevista = Entrevista::findOrFail($id);

        // Obtener entidades de la BD (todas, incluyendo excluidas)
        $entidadesDB = EntidadDetectada::where('id_e_ind_fvt', $id)
            ->orderBy('posicion_inicio')
            ->get();

        // Convertir a formato esperado
        $entidades = $entidadesDB->map(function($e) {
            return [
                'text' => $e->texto,
                'type' => $e->tipo,
                'replacement' => $e->texto_anonimizado,
                'start' => $e->posicion_inicio,
                'end' => $e->posicion_fin,
                'manual' => (bool) $e->manual,
                'excluir' => (bool) $e->excluir_anonimizacion,
            ];
        })->toArray();

        return view('procesamientos.previsualizar-anonimizacion', compact('entrevista', 'entidades'));
    }

    /**
     * Actualizar estado de una entidad (verificar/excluir)
     */
    public function actualizarEntidad(Request $request, $id)
    {
        $entidad = EntidadDetectada::findOrFail($id);

        if ($request->has('verificado')) {
            $entidad->verificado = (bool) $request->verificado;
        }

        if ($request->has('excluir')) {
            $entidad->excluir_anonimizacion = (bool) $request->excluir;
        }

        $entidad->save();

        return response()->json(['success' => true]);
    }

    // =============================================
    // ASIGNACIÓN Y REVISIÓN DE TRANSCRIPCIONES
    // =============================================

    /**
     * Asignar transcripción a un transcriptor (Admin/Líder)
     */
    public function asignarTranscripcion(Request $request)
    {
        $user = Auth::user();

        // Solo Admin y Líder pueden asignar
        if ($user->id_nivel > 2) {
            return response()->json(['error' => 'No tiene permisos para asignar'], 403);
        }

        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'id_transcriptor' => 'required|integer',
        ]);

        // Verificar que no exista una asignación activa para esta entrevista
        $existente = AsignacionTranscripcion::where('id_e_ind_fvt', $request->id_e_ind_fvt)
            ->whereIn('estado', [
                AsignacionTranscripcion::ESTADO_ASIGNADA,
                AsignacionTranscripcion::ESTADO_EN_EDICION,
                AsignacionTranscripcion::ESTADO_ENVIADA_REVISION,
            ])
            ->first();

        if ($existente) {
            return response()->json([
                'error' => 'Esta entrevista ya tiene una asignación activa'
            ], 400);
        }

        $asignacion = AsignacionTranscripcion::create([
            'id_e_ind_fvt' => $request->id_e_ind_fvt,
            'id_transcriptor' => $request->id_transcriptor,
            'id_asignado_por' => $user->id,
            'estado' => AsignacionTranscripcion::ESTADO_ASIGNADA,
            'fecha_asignacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transcripción asignada correctamente',
            'asignacion' => $asignacion->id_asignacion
        ]);
    }

    /**
     * Editar transcripción asignada (Transcriptor)
     */
    public function editarTranscripcionAsignada($id)
    {
        $user = Auth::user();
        $asignacion = AsignacionTranscripcion::with(['rel_entrevista', 'rel_entrevista.rel_adjuntos' => function($q) {
            $q->where('tipo_mime', 'like', '%audio%')
              ->orWhere('tipo_mime', 'like', '%video%');
        }])->findOrFail($id);

        // Verificar que sea el transcriptor asignado o Admin/Líder
        if ($user->id_nivel > 2 && $asignacion->id_transcriptor != $user->id_entrevistador) {
            flash('No tiene permisos para editar esta transcripción.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        // Marcar como en edicion si estaba asignada o rechazada
        if (in_array($asignacion->estado, [
            AsignacionTranscripcion::ESTADO_ASIGNADA,
            AsignacionTranscripcion::ESTADO_RECHAZADA
        ])) {
            $asignacion->estado = AsignacionTranscripcion::ESTADO_EN_EDICION;
            if (!$asignacion->fecha_inicio_edicion) {
                $asignacion->fecha_inicio_edicion = now();
            }
            $asignacion->save();
        }

        $entrevista = $asignacion->rel_entrevista;

        return view('procesamientos.editar-transcripcion-asignada', compact('asignacion', 'entrevista'));
    }

    /**
     * Guardar transcripción editada (Transcriptor)
     */
    public function guardarTranscripcionAsignada(Request $request, $id)
    {
        $user = Auth::user();
        $asignacion = AsignacionTranscripcion::findOrFail($id);

        // Verificar permisos
        if ($user->id_nivel > 2 && $asignacion->id_transcriptor != $user->id_entrevistador) {
            return response()->json(['error' => 'No tiene permisos'], 403);
        }

        $request->validate([
            'transcripcion' => 'required|string',
        ]);

        $asignacion->transcripcion_editada = $request->transcripcion;
        $asignacion->updated_at = now();
        $asignacion->save();

        flash('Transcripción guardada correctamente.')->success();
        return redirect()->back();
    }

    /**
     * Enviar transcripción a revisión (Transcriptor)
     */
    public function enviarARevision($id)
    {
        $user = Auth::user();
        $asignacion = AsignacionTranscripcion::findOrFail($id);

        // Verificar que sea el transcriptor asignado
        if ($user->id_nivel > 2 && $asignacion->id_transcriptor != $user->id_entrevistador) {
            flash('No tiene permisos para enviar esta transcripción.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        // Verificar que tenga contenido
        if (empty($asignacion->transcripcion_editada)) {
            flash('Debe editar la transcripción antes de enviarla a revisión.')->error();
            return redirect()->back();
        }

        $asignacion->estado = AsignacionTranscripcion::ESTADO_ENVIADA_REVISION;
        $asignacion->fecha_envio_revision = now();
        $asignacion->save();

        flash('Transcripción enviada a revisión.')->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Ver transcripción para revisión (Admin/Líder)
     */
    public function verRevision($id)
    {
        $user = Auth::user();

        // Solo Admin y Líder pueden revisar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para revisar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $asignacion = AsignacionTranscripcion::with([
            'rel_entrevista',
            'rel_entrevista.rel_adjuntos' => function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            },
            'rel_transcriptor.rel_usuario',
            'rel_asignado_por'
        ])->findOrFail($id);

        $entrevista = $asignacion->rel_entrevista;

        return view('procesamientos.revisar-transcripcion', compact('asignacion', 'entrevista'));
    }

    /**
     * Aprobar transcripción (Admin/Líder)
     */
    public function aprobarTranscripcionAsignada(Request $request, $id)
    {
        $user = Auth::user();

        // Solo Admin y Líder pueden aprobar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para aprobar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $asignacion = AsignacionTranscripcion::findOrFail($id);
        $entrevista = Entrevista::findOrFail($asignacion->id_e_ind_fvt);

        // Guardar transcripción final como adjunto (tipo 306)
        $entrevista->guardarTranscripcionFinal(
            $asignacion->transcripcion_editada,
            $user->id
        );

        // Actualizar asignación
        $asignacion->estado = AsignacionTranscripcion::ESTADO_APROBADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $asignacion->save();

        flash('Transcripción aprobada y guardada como adjunto de Transcripción Final.')->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Rechazar transcripción (Admin/Líder)
     */
    public function rechazarTranscripcion(Request $request, $id)
    {
        $user = Auth::user();

        // Solo Admin y Líder pueden rechazar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para rechazar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $request->validate([
            'comentario' => 'required|string|min:10',
        ], [
            'comentario.required' => 'Debe indicar el motivo del rechazo',
            'comentario.min' => 'El comentario debe tener al menos 10 caracteres',
        ]);

        $asignacion = AsignacionTranscripcion::findOrFail($id);

        $asignacion->estado = AsignacionTranscripcion::ESTADO_RECHAZADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $asignacion->save();

        flash('Transcripción rechazada. El transcriptor ha sido notificado.')->warning();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Obtener estado de asignación de una entrevista (AJAX)
     */
    public function estadoAsignacion($id)
    {
        $asignacion = AsignacionTranscripcion::where('id_e_ind_fvt', $id)
            ->with(['rel_transcriptor.rel_usuario'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$asignacion) {
            return response()->json(['asignada' => false]);
        }

        return response()->json([
            'asignada' => true,
            'estado' => $asignacion->estado,
            'fmt_estado' => $asignacion->fmt_estado,
            'transcriptor' => $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A',
            'fecha_asignacion' => $asignacion->fecha_asignacion->format('d/m/Y H:i'),
        ]);
    }

    // =============================================
    // ASIGNACION Y REVISION DE ANONIMIZACION
    // =============================================

    /**
     * Asignar anonimizacion a un anonimizador (Admin/Lider)
     */
    public function asignarAnonimizacion(Request $request)
    {
        $user = Auth::user();

        // Solo Admin y Lider pueden asignar
        if ($user->id_nivel > 2) {
            return response()->json(['error' => 'No tiene permisos para asignar'], 403);
        }

        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'id_anonimizador' => 'required|integer',
            'tipos_anonimizar' => 'nullable|string',
            'formato_reemplazo' => 'nullable|string',
        ]);

        // Verificar que no exista una asignacion activa para esta entrevista
        $existente = AsignacionAnonimizacion::where('id_e_ind_fvt', $request->id_e_ind_fvt)
            ->whereIn('estado', [
                AsignacionAnonimizacion::ESTADO_ASIGNADA,
                AsignacionAnonimizacion::ESTADO_EN_EDICION,
                AsignacionAnonimizacion::ESTADO_ENVIADA_REVISION,
            ])
            ->first();

        if ($existente) {
            return response()->json([
                'error' => 'Esta entrevista ya tiene una asignacion de anonimizacion activa'
            ], 400);
        }

        $asignacion = AsignacionAnonimizacion::create([
            'id_e_ind_fvt' => $request->id_e_ind_fvt,
            'id_anonimizador' => $request->id_anonimizador,
            'id_asignado_por' => $user->id,
            'estado' => AsignacionAnonimizacion::ESTADO_ASIGNADA,
            'fecha_asignacion' => now(),
            'tipos_anonimizar' => $request->tipos_anonimizar ?? 'PER,LOC',
            'formato_reemplazo' => $request->formato_reemplazo ?? 'brackets',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anonimizacion asignada correctamente',
            'asignacion' => $asignacion->id_asignacion
        ]);
    }

    /**
     * Editar anonimizacion asignada (Anonimizador)
     */
    public function editarAnonimizacionAsignada($id)
    {
        $user = Auth::user();
        $asignacion = AsignacionAnonimizacion::with(['rel_entrevista'])->findOrFail($id);

        // Verificar que sea el anonimizador asignado o Admin/Lider
        if ($user->id_nivel > 2 && $asignacion->id_anonimizador != $user->id_entrevistador) {
            flash('No tiene permisos para editar esta anonimizacion.')->error();
            return redirect()->route('procesamientos.anonimizacion');
        }

        // Marcar como en edicion si estaba asignada o rechazada
        if (in_array($asignacion->estado, [
            AsignacionAnonimizacion::ESTADO_ASIGNADA,
            AsignacionAnonimizacion::ESTADO_RECHAZADA
        ])) {
            $asignacion->estado = AsignacionAnonimizacion::ESTADO_EN_EDICION;
            if (!$asignacion->fecha_inicio_edicion) {
                $asignacion->fecha_inicio_edicion = now();
            }
            $asignacion->save();
        }

        $entrevista = $asignacion->rel_entrevista;

        // Obtener entidades detectadas (incluir todas, el estado excluir se usa para determinar cubierta/descubierta)
        $entidadesDB = EntidadDetectada::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->orderBy('posicion_inicio')
            ->get();

        $entidades = $entidadesDB->map(function($e) {
            return [
                'text' => $e->texto,
                'type' => $e->tipo,
                'replacement' => $e->texto_anonimizado,
                'start' => $e->posicion_inicio,
                'end' => $e->posicion_fin,
                'id' => $e->id_entidad,
                'manual' => (bool) $e->manual,
                'excluir' => (bool) $e->excluir_anonimizacion,
            ];
        })->toArray();

        return view('procesamientos.editar-anonimizacion-asignada', compact('asignacion', 'entrevista', 'entidades'));
    }

    /**
     * Guardar anonimizacion editada (Anonimizador)
     */
    public function guardarAnonimizacionAsignada(Request $request, $id)
    {
        $user = Auth::user();
        $asignacion = AsignacionAnonimizacion::with('rel_entrevista')->findOrFail($id);

        // Verificar permisos
        if ($user->id_nivel > 2 && $asignacion->id_anonimizador != $user->id_entrevistador) {
            return response()->json(['error' => 'No tiene permisos'], 403);
        }

        $request->validate([
            'texto_anonimizado' => 'required|string',
        ]);

        $asignacion->texto_anonimizado = $request->texto_anonimizado;
        if ($request->has('tipos_anonimizar')) {
            $asignacion->tipos_anonimizar = $request->tipos_anonimizar;
        }
        if ($request->has('formato_reemplazo')) {
            $asignacion->formato_reemplazo = $request->formato_reemplazo;
        }
        $asignacion->updated_at = now();
        $asignacion->save();

        // Guardar entidades manuales si se enviaron
        if ($request->has('entidades_manuales')) {
            $entidadesManuales = json_decode($request->entidades_manuales, true);
            if (is_array($entidadesManuales) && count($entidadesManuales) > 0) {
                $idEntrevista = $asignacion->id_e_ind_fvt;

                foreach ($entidadesManuales as $ent) {
                    // Verificar si ya existe una entidad manual con el mismo texto y posicion
                    $existente = EntidadDetectada::where('id_e_ind_fvt', $idEntrevista)
                        ->where('texto', $ent['text'])
                        ->where('posicion_inicio', $ent['start'])
                        ->where('manual', true)
                        ->first();

                    if (!$existente) {
                        EntidadDetectada::create([
                            'id_e_ind_fvt' => $idEntrevista,
                            'tipo' => $ent['type'],
                            'texto' => $ent['text'],
                            'texto_anonimizado' => $ent['reemplazo'] ?? null,
                            'posicion_inicio' => $ent['start'],
                            'posicion_fin' => $ent['end'],
                            'confianza' => 1.0, // Manual = confianza maxima
                            'verificado' => true,
                            'manual' => true,
                        ]);
                    }
                }
            }
        }

        // Actualizar estado de entidades (cubierta/descubierta)
        if ($request->has('estado_entidades')) {
            $estadoEntidades = json_decode($request->estado_entidades, true);
            if (is_array($estadoEntidades)) {
                $idEntrevista = $asignacion->id_e_ind_fvt;

                foreach ($estadoEntidades as $ent) {
                    // Buscar entidad por texto y posicion
                    $entidadDB = EntidadDetectada::where('id_e_ind_fvt', $idEntrevista)
                        ->where('texto', $ent['text'])
                        ->where('posicion_inicio', $ent['start'])
                        ->first();

                    if ($entidadDB) {
                        // excluir_anonimizacion = true significa que NO se cubre (descubierta)
                        $entidadDB->excluir_anonimizacion = !$ent['cubierta'];
                        $entidadDB->save();
                    }
                }
            }
        }

        flash('Anonimizacion guardada correctamente.')->success();
        return redirect()->back();
    }

    /**
     * Enviar anonimizacion a revision (Anonimizador)
     */
    public function enviarAnonimizacionARevision($id)
    {
        $user = Auth::user();
        $asignacion = AsignacionAnonimizacion::findOrFail($id);

        // Verificar que sea el anonimizador asignado
        if ($user->id_nivel > 2 && $asignacion->id_anonimizador != $user->id_entrevistador) {
            flash('No tiene permisos para enviar esta anonimizacion.')->error();
            return redirect()->route('procesamientos.anonimizacion');
        }

        // Verificar que tenga contenido
        if (empty($asignacion->texto_anonimizado)) {
            flash('Debe editar la anonimizacion antes de enviarla a revision.')->error();
            return redirect()->back();
        }

        $asignacion->estado = AsignacionAnonimizacion::ESTADO_ENVIADA_REVISION;
        $asignacion->fecha_envio_revision = now();
        $asignacion->save();

        flash('Anonimizacion enviada a revision.')->success();
        return redirect()->route('procesamientos.anonimizacion');
    }

    /**
     * Ver anonimizacion para revision (Admin/Lider)
     */
    public function verRevisionAnonimizacion($id)
    {
        $user = Auth::user();

        // Solo Admin y Lider pueden revisar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para revisar anonimizaciones.')->error();
            return redirect()->route('procesamientos.anonimizacion');
        }

        $asignacion = AsignacionAnonimizacion::with([
            'rel_entrevista',
            'rel_anonimizador.rel_usuario',
            'rel_asignado_por'
        ])->findOrFail($id);

        $entrevista = $asignacion->rel_entrevista;

        // Obtener entidades para referencia
        $entidadesDB = EntidadDetectada::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->orderBy('posicion_inicio')
            ->get();

        $entidades = $entidadesDB->map(function($e) {
            return [
                'text' => $e->texto,
                'type' => $e->tipo,
                'replacement' => $e->texto_anonimizado,
                'start' => $e->posicion_inicio,
                'end' => $e->posicion_fin,
                'manual' => (bool) $e->manual,
                'excluir' => (bool) $e->excluir_anonimizacion,
            ];
        })->toArray();

        return view('procesamientos.revisar-anonimizacion', compact('asignacion', 'entrevista', 'entidades'));
    }

    /**
     * Aprobar anonimizacion (Admin/Lider)
     */
    public function aprobarAnonimizacionAsignada(Request $request, $id)
    {
        $user = Auth::user();

        // Solo Admin y Lider pueden aprobar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para aprobar anonimizaciones.')->error();
            return redirect()->route('procesamientos.anonimizacion');
        }

        $asignacion = AsignacionAnonimizacion::findOrFail($id);
        $entrevista = Entrevista::findOrFail($asignacion->id_e_ind_fvt);

        // Guardar anonimizacion final en la entrevista
        $entrevista->anonimizacion_final = $asignacion->texto_anonimizado;
        $entrevista->anonimizacion_final_at = now();
        $entrevista->anonimizacion_final_por = $user->id;
        $entrevista->save();

        // Actualizar asignacion
        $asignacion->estado = AsignacionAnonimizacion::ESTADO_APROBADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $asignacion->save();

        flash('Anonimizacion aprobada y guardada como version final.')->success();
        return redirect()->route('procesamientos.anonimizacion');
    }

    /**
     * Rechazar anonimizacion (Admin/Lider)
     */
    public function rechazarAnonimizacion(Request $request, $id)
    {
        \Log::info('rechazarAnonimizacion llamado', ['id' => $id, 'data' => $request->all()]);

        $user = Auth::user();

        // Solo Admin y Lider pueden rechazar
        if ($user->id_nivel > 2) {
            flash('No tiene permisos para rechazar anonimizaciones.')->error();
            return redirect()->route('procesamientos.anonimizacion');
        }

        $request->validate([
            'comentario' => 'required|string|min:10',
        ], [
            'comentario.required' => 'Debe indicar el motivo del rechazo',
            'comentario.min' => 'El comentario debe tener al menos 10 caracteres',
        ]);

        $asignacion = AsignacionAnonimizacion::findOrFail($id);

        \Log::info('Estado anterior', ['estado' => $asignacion->estado]);

        $asignacion->estado = AsignacionAnonimizacion::ESTADO_RECHAZADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $asignacion->save();

        \Log::info('Estado nuevo', ['estado' => $asignacion->estado]);

        flash('Anonimizacion rechazada. El anonimizador ha sido notificado.')->warning();
        return redirect()->route('procesamientos.anonimizacion');
    }
}
