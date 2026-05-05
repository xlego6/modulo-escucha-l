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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\TrazaActividad;
use App\Models\RolModuloPermiso;
use App\Models\CatItem;
use App\Exports\AsignacionesTranscripcionExport;
use Maatwebsite\Excel\Facades\Excel;

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
    public function index(Request $request)
    {
        $tipo = $request->get('tipo', 'transcripcion');

        // Stats globales
        $statsTranscripcion = $this->calcStatsGlobales('transcripcion');
        $statsAnonimizacion = $this->calcStatsGlobales('anonimizacion');

        // Transcriptores para el filtro: todos los Líderes (2) y Transcriptores (4)
        $transcriptores = DB::table('esclarecimiento.entrevistador as e')
            ->join('users as u', 'u.id', '=', 'e.id_usuario')
            ->whereIn('e.id_nivel', [2, 4])
            ->select('e.id_entrevistador', 'u.name', 'e.id_dependencia_origen')
            ->orderBy('u.name')
            ->get();

        // Anonimizadores para el filtro
        $anonimizadores = DB::table('esclarecimiento.asignacion_anonimizacion as aa')
            ->join('esclarecimiento.entrevistador as e', 'e.id_entrevistador', '=', 'aa.id_anonimizador')
            ->join('users as u', 'u.id', '=', 'e.id_usuario')
            ->select('e.id_entrevistador', 'u.name', 'e.id_dependencia_origen')
            ->distinct()
            ->orderBy('u.name')
            ->get();

        // Dependencias disponibles
        $dependencias = CatItem::where('id_cat', 4)->where('habilitado', 1)->orderBy('orden')->get();

        // Filtros aplicados
        $filtroIds = array_filter((array)$request->get('ids', []));
        $filtroDependencia = $request->get('dependencia');
        $filtroEstado = $request->get('estado');
        $filtroFechaDesde = $request->get('fecha_desde');
        $filtroFechaHasta = $request->get('fecha_hasta');
        $verTodos = $request->boolean('ver_todos');

        // Stats y listado detalle si hay filtro activo o se pidió ver todos
        $detalleStats = null;
        $detalleAsignaciones = collect();

        if ($verTodos || !empty($filtroIds) || !empty($filtroDependencia) || !empty($filtroEstado) || !empty($filtroFechaDesde) || !empty($filtroFechaHasta)) {
            $detalleStats = $this->calcDetalleStats($tipo, $filtroIds, $filtroDependencia);
            $detalleAsignaciones = $this->calcDetalleAsignaciones($tipo, $filtroIds, $filtroDependencia, $filtroEstado, $filtroFechaDesde, $filtroFechaHasta);
        }

        // Trabajos en cola
        $trabajosEnCola = DB::table('esclarecimiento.trabajo_procesamiento')->where('estado', 'pendiente')->count();
        $trabajosProcesando = DB::table('esclarecimiento.trabajo_procesamiento')->where('estado', 'procesando')->count();

        // Estado de los servicios
        $servicios = $this->procesamientoService->getServicesInfo();

        return view('procesamientos.index', compact(
            'tipo',
            'statsTranscripcion', 'statsAnonimizacion',
            'transcriptores', 'anonimizadores', 'dependencias',
            'filtroIds', 'filtroDependencia', 'filtroEstado', 'filtroFechaDesde', 'filtroFechaHasta', 'verTodos',
            'detalleStats', 'detalleAsignaciones',
            'trabajosEnCola', 'trabajosProcesando',
            'servicios'
        ));
    }

    // ─── Helpers estadísticos ────────────────────────────────────────────────

    private function audioStatsForIds($ids)
    {
        $idsArray = array_unique(is_array($ids) ? $ids : $ids->toArray());
        if (empty($idsArray)) {
            return ['cantidad_entrevistas' => 0, 'cantidad_audios' => 0, 'duracion_total' => 0];
        }

        $result = DB::table('esclarecimiento.adjunto')
            ->whereIn('id_e_ind_fvt', $idsArray)
            ->where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->selectRaw('COUNT(*) as cantidad_audios, COALESCE(SUM(duracion), 0) as duracion_total')
            ->first();

        return [
            'cantidad_entrevistas' => count($idsArray),
            'cantidad_audios' => (int)($result->cantidad_audios ?? 0),
            'duracion_total' => (int)($result->duracion_total ?? 0),
        ];
    }

    private function calcStatsTranscriptor($transcriptorId)
    {
        $estados = ['asignada', 'en_edicion', 'enviada_revision', 'rechazada', 'aprobada'];
        $stats = [];
        foreach ($estados as $estado) {
            $ids = DB::table('esclarecimiento.asignacion_transcripcion')
                ->where('id_transcriptor', $transcriptorId)
                ->where('estado', $estado)
                ->distinct()->pluck('id_e_ind_fvt');
            $stats[$estado] = $this->audioStatsForIds($ids);
        }
        return $stats;
    }

    private function calcStatsGlobales($tipo)
    {
        $table = $tipo === 'transcripcion'
            ? 'esclarecimiento.asignacion_transcripcion'
            : 'esclarecimiento.asignacion_anonimizacion';

        $estados = ['asignada', 'en_edicion', 'enviada_revision', 'rechazada', 'aprobada'];
        $stats = [];

        foreach ($estados as $estado) {
            $ids = DB::table($table)->where('estado', $estado)->distinct()->pluck('id_e_ind_fvt');
            $stats[$estado] = $this->audioStatsForIds($ids);
        }

        // Procesadas (solo transcripción: con transcripcion_completada_at)
        if ($tipo === 'transcripcion') {
            $ids = DB::table('esclarecimiento.e_ind_fvt')
                ->where('id_activo', 1)
                ->whereNotNull('transcripcion_completada_at')
                ->pluck('id_e_ind_fvt');
            $stats['procesadas'] = $this->audioStatsForIds($ids);
        }

        // Totales: todas las entrevistas activas del sistema (independiente de asignaciones)
        $idsTotales = DB::table('esclarecimiento.e_ind_fvt')
            ->where('id_activo', 1)
            ->pluck('id_e_ind_fvt');
        $stats['totales'] = $this->audioStatsForIds($idsTotales);

        return $stats;
    }

    private function getEntrevistaIdsFiltradas($tipo, $filtroIds, $filtroDependencia, $estado = null)
    {
        $table = $tipo === 'transcripcion'
            ? 'esclarecimiento.asignacion_transcripcion'
            : 'esclarecimiento.asignacion_anonimizacion';
        $personaCol = $tipo === 'transcripcion' ? 'id_transcriptor' : 'id_anonimizador';

        $query = DB::table($table . ' as at')
            ->join('esclarecimiento.entrevistador as e', 'e.id_entrevistador', '=', 'at.' . $personaCol);

        if (!empty($filtroIds)) {
            $query->whereIn('at.' . $personaCol, (array)$filtroIds);
        } elseif (!empty($filtroDependencia)) {
            $query->where('e.id_dependencia_origen', $filtroDependencia);
        }

        if ($estado) {
            $query->where('at.estado', $estado);
        }

        return $query->distinct()->pluck('at.id_e_ind_fvt');
    }

    private function calcDetalleStats($tipo, $filtroIds, $filtroDependencia)
    {
        $table = $tipo === 'transcripcion'
            ? 'esclarecimiento.asignacion_transcripcion'
            : 'esclarecimiento.asignacion_anonimizacion';
        $personaCol = $tipo === 'transcripcion' ? 'id_transcriptor' : 'id_anonimizador';

        $estados = ['asignada', 'en_edicion', 'enviada_revision', 'rechazada', 'aprobada'];
        $stats = [];

        foreach ($estados as $estado) {
            $ids = $this->getEntrevistaIdsFiltradas($tipo, $filtroIds, $filtroDependencia, $estado);
            $s = $this->audioStatsForIds($ids);

            if (in_array($estado, ['en_edicion', 'enviada_revision', 'rechazada', 'aprobada'])) {
                $q = DB::table($table . ' as at')
                    ->join('esclarecimiento.entrevistador as e', 'e.id_entrevistador', '=', 'at.' . $personaCol)
                    ->where('at.estado', $estado);

                if (!empty($filtroIds)) {
                    $q->whereIn('at.' . $personaCol, (array)$filtroIds);
                } elseif (!empty($filtroDependencia)) {
                    $q->where('e.id_dependencia_origen', $filtroDependencia);
                }

                // Solo mostrar tiempo si hay datos de heartbeat (medición activa)
                $segundosActivos = (int)$q->sum('at.segundos_edicion_activa');
                if ($segundosActivos > 0) {
                    $s['tiempo_edicion'] = $segundosActivos;
                }
            }

            $stats[$estado] = $s;
        }

        $idsTotales = $this->getEntrevistaIdsFiltradas($tipo, $filtroIds, $filtroDependencia);
        $stats['totales'] = $this->audioStatsForIds($idsTotales);

        return $stats;
    }

    private function calcDetalleAsignaciones($tipo, $filtroIds, $filtroDependencia, $filtroEstado = null, $fechaDesde = null, $fechaHasta = null)
    {
        $table = $tipo === 'transcripcion'
            ? 'esclarecimiento.asignacion_transcripcion'
            : 'esclarecimiento.asignacion_anonimizacion';
        $personaCol = $tipo === 'transcripcion' ? 'id_transcriptor' : 'id_anonimizador';

        $query = DB::table($table . ' as at')
            ->join('esclarecimiento.entrevistador as e', 'e.id_entrevistador', '=', 'at.' . $personaCol)
            ->join('users as u', 'u.id', '=', 'e.id_usuario')
            ->join('esclarecimiento.e_ind_fvt as ent', 'ent.id_e_ind_fvt', '=', 'at.id_e_ind_fvt')
            ->leftJoin(DB::raw("(
                SELECT id_e_ind_fvt,
                       COUNT(*) as num_audios,
                       COALESCE(SUM(duracion), 0) as duracion_total
                FROM esclarecimiento.adjunto
                WHERE tipo_mime LIKE '%audio%' OR tipo_mime LIKE '%video%'
                GROUP BY id_e_ind_fvt
            ) as adj"), 'adj.id_e_ind_fvt', '=', 'at.id_e_ind_fvt')
            ->leftJoin('esclarecimiento.adjunto as adj_asig', 'adj_asig.id_adjunto', '=', 'at.id_adjunto')
            ->leftJoin('users as u_rev', 'u_rev.id', '=', 'at.id_revisor')
            ->select(
                'at.id_asignacion',
                'at.id_e_ind_fvt',
                'at.id_adjunto',
                'ent.entrevista_codigo',
                'at.estado',
                'at.fecha_asignacion',
                'at.fecha_revision',
                'u.name as nombre_persona',
                'u_rev.name as nombre_revisor',
                'adj_asig.nombre_original as nombre_audio',
                'adj_asig.duracion as duracion_audio',
                DB::raw('COALESCE(adj.duracion_total, 0) as duracion_total'),
                DB::raw('COALESCE(adj.num_audios, 0) as num_audios'),
                'at.historial_comentarios'
            )
            ->orderBy('at.fecha_asignacion');

        if (!empty($filtroIds)) {
            $query->whereIn('at.' . $personaCol, (array)$filtroIds);
        } elseif (!empty($filtroDependencia)) {
            $query->where('e.id_dependencia_origen', $filtroDependencia);
        }

        if (!empty($filtroEstado)) {
            $query->where('at.estado', $filtroEstado);
        }

        if (!empty($fechaDesde)) {
            $query->whereDate('at.fecha_asignacion', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('at.fecha_asignacion', '<=', $fechaHasta);
        }

        return $query->get();
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
        // Entrevistas con audio o video (excluir adjuntos anonimizados)
        $entrevistas = Entrevista::where('id_activo', 1)
            ->whereHas('rel_adjuntos', function($q) {
                $q->where(function($inner) {
                    $inner->where('tipo_mime', 'like', '%audio%')
                          ->orWhere('tipo_mime', 'like', '%video%');
                })->where('nombre_original', 'not like', '%[Anonimizado]%');
            })
            ->with(['rel_adjuntos' => function($q) {
                $q->where(function($inner) {
                    $inner->where('tipo_mime', 'like', '%audio%')
                          ->orWhere('tipo_mime', 'like', '%video%');
                })->where('nombre_original', 'not like', '%[Anonimizado]%');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $enProceso = collect();

        // Estado del servicio
        $servicioStatus = $this->procesamientoService->transcriptionStatus();

        return view('procesamientos.transcripcion', compact('entrevistas', 'enProceso', 'servicioStatus'));
    }

    /**
     * Iniciar transcripcion de una entrevista (todos los audios) - async+polling
     * Envia cada audio como trabajo async y retorna los job_ids inmediatamente.
     */
    public function iniciarTranscripcion(Request $request, $id)
    {
        $entrevista = Entrevista::findOrFail($id);

        // Obtener archivos de audio o video (excluir anonimizados)
        $audios = Adjunto::where('id_e_ind_fvt', $id)
            ->where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->where('nombre_original', 'not like', '%[Anonimizado]%')
            ->orderBy('id_adjunto')
            ->get();

        if ($audios->isEmpty()) {
            return response()->json(['error' => 'No hay archivos de audio o video'], 400);
        }

        $withDiarization = $request->input('diarizar', true);
        $hfToken = (string) $request->input('hf_token', '');

        $jobs = [];
        $errores = [];

        foreach ($audios as $audio) {
            $audioPath = Storage::disk('public')->path($audio->ubicacion);

            if (!file_exists($audioPath)) {
                $errores[] = "Archivo no encontrado: {$audio->nombre_original}";
                continue;
            }

            $jobId = 'ind_' . $id . '_' . $audio->id_adjunto . '_' . time();
            $asyncResult = $this->procesamientoService->transcribeAsync($audioPath, $jobId, $withDiarization, $hfToken);

            if (!($asyncResult['success'] ?? false)) {
                $errores[] = "Error enviando {$audio->nombre_original}: " . ($asyncResult['error'] ?? 'Error desconocido');
                continue;
            }

            // Guardar mapping en cache: tipo 'entrevista' para saber que al completar todos se regenera la transcripcion completa
            Cache::put("transcripcion_job_{$jobId}", [
                'tipo'          => 'entrevista',
                'entrevista_id' => $id,
                'adjunto_id'    => $audio->id_adjunto,
                'nombre'        => $audio->nombre_original,
                'codigo'        => $entrevista->entrevista_codigo,
                'saved'         => false,
                'user_id'       => Auth::id(),
                'ip'            => $request->ip(),
            ], now()->addDays(2));

            $jobs[] = $jobId;
        }

        if (empty($jobs)) {
            return response()->json([
                'success' => false,
                'error' => 'No se pudo enviar ningun archivo. ' . implode('; ', $errores)
            ], 500);
        }

        return response()->json([
            'success' => true,
            'job_ids' => $jobs,
            'total_audios' => $audios->count(),
            'errores' => $errores,
        ]);
    }

    /**
     * Transcribir un adjunto individual - async+polling
     * Envia el trabajo async y retorna el job_id inmediatamente.
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
        $hfToken = (string) $request->input('hf_token', '');

        $jobId = 'adj_' . $idAdjunto . '_' . time();
        $asyncResult = $this->procesamientoService->transcribeAsync($audioPath, $jobId, $withDiarization, $hfToken);

        if (!($asyncResult['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'error' => $asyncResult['error'] ?? 'Error al enviar al servicio de transcripcion'
            ], 500);
        }

        Cache::put("transcripcion_job_{$jobId}", [
            'tipo'          => 'adjunto',
            'entrevista_id' => $entrevista->id_e_ind_fvt,
            'adjunto_id'    => $adjunto->id_adjunto,
            'nombre'        => $adjunto->nombre_original,
            'codigo'        => $entrevista->entrevista_codigo,
            'saved'         => false,
            'user_id'       => Auth::id(),
            'ip'            => $request->ip(),
        ], now()->addDays(2));

        return response()->json([
            'success' => true,
            'job_id' => $jobId,
        ]);
    }

    /**
     * Consultar estado de trabajos individuales (entrevista completa o adjunto suelto).
     * Llamado por el navegador via AJAX GET (sin CSRF).
     * Cuando un job completa, guarda la transcripcion y registra traza.
     */
    public function transcripcionIndividualEstado(Request $request)
    {
        $jobIds = $request->query('job_ids', []);
        $resultados = [];

        foreach ($jobIds as $jobId) {
            $cached = Cache::get("transcripcion_job_{$jobId}");

            if (!$cached) {
                $resultados[$jobId] = ['status' => 'not_found'];
                continue;
            }

            try {
                $jobStatus = $this->procesamientoService->getTranscriptionJob($jobId);
                $status = $jobStatus['status'] ?? 'unknown';

                // Si completó y no se ha guardado, guardar
                if ($status === 'completed' && ($jobStatus['success'] ?? false) && !$cached['saved']) {
                    $texto = trim($jobStatus['text'] ?? '');

                    if (!empty($texto)) {
                        // Guardar texto en el adjunto
                        $adjunto = Adjunto::find($cached['adjunto_id']);
                        if ($adjunto) {
                            $adjunto->texto_extraido = $texto;
                            $adjunto->texto_extraido_at = now();
                            $adjunto->save();
                        }

                        // Si es tipo 'entrevista', no regenerar aquí — se hace cuando todos los jobs terminen
                        // Si es tipo 'adjunto', regenerar la transcripcion completa ahora
                        if (($cached['tipo'] ?? '') === 'adjunto') {
                            $entrevista = Entrevista::find($cached['entrevista_id']);
                            if ($entrevista) {
                                $this->regenerarTranscripcionCompleta($entrevista);

                                TrazaActividad::create([
                                    'fecha_hora'  => now(),
                                    'id_usuario'  => $cached['user_id'],
                                    'accion'      => 'completar_transcripcion',
                                    'objeto'      => 'adjunto',
                                    'id_registro' => $cached['adjunto_id'],
                                    'codigo'      => $cached['codigo'],
                                    'referencia'  => 'Transcripcion automatica: ' . $cached['nombre'],
                                    'ip'          => $cached['ip'],
                                ]);
                            }
                        }

                        $cached['saved'] = true;
                        Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                    } else {
                        $status = 'failed';
                        $jobStatus['error'] = 'El audio no contiene voz detectable';
                    }
                }

                // Registrar error si el job falló (solo una vez)
                if ($status === 'failed' && !($cached['error_traced'] ?? false)) {
                    TrazaActividad::create([
                        'fecha_hora'  => now(),
                        'id_usuario'  => $cached['user_id'],
                        'accion'      => 'error_transcripcion',
                        'objeto'      => 'adjunto',
                        'id_registro' => $cached['adjunto_id'],
                        'codigo'      => $cached['codigo'],
                        'referencia'  => ($cached['nombre'] ?? '') . ': ' . ($jobStatus['error'] ?? 'Error desconocido'),
                        'ip'          => $cached['ip'],
                    ]);
                    $cached['error_traced'] = true;
                    Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                }

                $resultados[$jobId] = [
                    'status'            => $status,
                    'saved'             => $cached['saved'],
                    'adjunto_id'        => $cached['adjunto_id'],
                    'entrevista_id'     => $cached['entrevista_id'],
                    'nombre'            => $cached['nombre'] ?? '',
                    'text_length'       => strlen($jobStatus['text'] ?? ''),
                    'text'              => $jobStatus['text'] ?? '',
                    'speakers_count'    => $jobStatus['speakers_count'] ?? 0,
                    'diarization_error' => $jobStatus['diarization_error'] ?? null,
                    'error'             => $jobStatus['error'] ?? null,
                ];

            } catch (\Exception $e) {
                if (!($cached['error_traced'] ?? false)) {
                    TrazaActividad::create([
                        'fecha_hora'  => now(),
                        'id_usuario'  => $cached['user_id'],
                        'accion'      => 'error_transcripcion',
                        'objeto'      => 'adjunto',
                        'id_registro' => $cached['adjunto_id'],
                        'codigo'      => $cached['codigo'],
                        'referencia'  => ($cached['nombre'] ?? '') . ': ' . $e->getMessage(),
                        'ip'          => $cached['ip'],
                    ]);
                    $cached['error_traced'] = true;
                    Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                }
                $resultados[$jobId] = [
                    'status' => 'error',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        // Si todos los jobs de una entrevista completaron, regenerar transcripcion completa y registrar traza
        $entrevistaJobs = [];
        foreach ($jobIds as $jobId) {
            $cached = Cache::get("transcripcion_job_{$jobId}");
            if ($cached && ($cached['tipo'] ?? '') === 'entrevista') {
                $entrevistaJobs[$cached['entrevista_id']][] = [
                    'job_id' => $jobId,
                    'cached' => $cached,
                    'status' => $resultados[$jobId]['status'] ?? 'unknown',
                ];
            }
        }

        foreach ($entrevistaJobs as $entrevistaId => $jobs) {
            $todosTerminados = true;
            $algunoExitoso = false;
            foreach ($jobs as $j) {
                if (!in_array($j['status'], ['completed', 'failed'])) {
                    $todosTerminados = false;
                    break;
                }
                if ($j['status'] === 'completed' && ($j['cached']['saved'] ?? false)) {
                    $algunoExitoso = true;
                }
            }

            if ($todosTerminados && $algunoExitoso) {
                // Verificar si ya se regeneró (usar flag en cache del primer job)
                $firstCached = Cache::get("transcripcion_job_{$jobs[0]['job_id']}");
                if ($firstCached && !($firstCached['regenerated'] ?? false)) {
                    $entrevista = Entrevista::find($entrevistaId);
                    if ($entrevista) {
                        $this->regenerarTranscripcionCompleta($entrevista);

                        $exitosos = count(array_filter($jobs, fn($j) => $j['status'] === 'completed'));
                        TrazaActividad::create([
                            'fecha_hora'  => now(),
                            'id_usuario'  => $firstCached['user_id'],
                            'accion'      => 'completar_transcripcion',
                            'objeto'      => 'entrevista',
                            'id_registro' => $entrevistaId,
                            'codigo'      => $firstCached['codigo'],
                            'referencia'  => 'Transcripcion automatica: ' . $exitosos . ' de ' . count($jobs) . ' archivos',
                            'ip'          => $firstCached['ip'],
                        ]);
                    }

                    // Marcar como regenerado en todos los jobs de esta entrevista
                    foreach ($jobs as $j) {
                        $c = Cache::get("transcripcion_job_{$j['job_id']}");
                        if ($c) {
                            $c['regenerated'] = true;
                            Cache::put("transcripcion_job_{$j['job_id']}", $c, now()->addDays(2));
                        }
                    }
                }
            }
        }

        return response()->json($resultados);
    }

    /**
     * Enviar trabajos de transcripcion en lote al servicio Python y retornar job_ids inmediatamente.
     * El navegador consulta el estado via transcripcionLoteEstado() de forma independiente.
     */
    public function transcripcionLote(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');
        $withDiarization = $request->input('diarizar', true);
        $hfToken = (string) $request->input('hf_token', '');

        $jobs = [];

        foreach ($ids as $id) {
            try {
                $entrevista = Entrevista::find($id);

                if (!$entrevista) {
                    $jobs[] = ['id' => $id, 'codigo' => '?', 'job_id' => null, 'status' => 'error', 'error' => 'Entrevista no encontrada'];
                    continue;
                }

                $codigo = $entrevista->entrevista_codigo;

                $audios = Adjunto::where('id_e_ind_fvt', $id)
                    ->where(function($q) {
                        $q->where('tipo_mime', 'like', '%audio%')
                          ->orWhere('tipo_mime', 'like', '%video%');
                    })
                    ->get();

                if ($audios->isEmpty()) {
                    $jobs[] = ['id' => $id, 'codigo' => $codigo, 'job_id' => null, 'status' => 'error', 'error' => 'Sin archivos de audio'];
                    continue;
                }

                foreach ($audios as $audio) {
                    $audioPath = Storage::disk('public')->path($audio->ubicacion);

                    if (!file_exists($audioPath)) {
                        $jobs[] = ['id' => $id, 'codigo' => $codigo, 'job_id' => null, 'status' => 'error', 'error' => 'Archivo no encontrado: ' . $audio->nombre_original];
                        continue;
                    }

                    $jobId = 'lote_' . $id . '_' . $audio->id_adjunto . '_' . time();
                    $asyncResult = $this->procesamientoService->transcribeAsync($audioPath, $jobId, $withDiarization, $hfToken);

                    if (!($asyncResult['success'] ?? false)) {
                        $jobs[] = ['id' => $id, 'codigo' => $codigo, 'job_id' => null, 'status' => 'error', 'error' => $asyncResult['error'] ?? 'Error al enviar ' . $audio->nombre_original];
                        continue;
                    }

                    Cache::put("transcripcion_job_{$jobId}", [
                        'entrevista_id' => $id,
                        'adjunto_id'    => $audio->id_adjunto,
                        'nombre'        => $audio->nombre_original,
                        'codigo'        => $codigo,
                        'saved'         => false,
                        'user_id'       => Auth::id(),
                        'ip'            => $request->ip(),
                    ], now()->addDays(2));

                    $jobs[] = ['id' => $id, 'codigo' => $codigo, 'audio' => $audio->nombre_original, 'job_id' => $jobId, 'status' => 'queued'];
                }

            } catch (\Exception $e) {
                $jobs[] = ['id' => $id, 'codigo' => '?', 'job_id' => null, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return response()->json(['success' => true, 'jobs' => $jobs]);
    }

    /**
     * Consultar el estado de los trabajos en lote y guardar resultados completados.
     * Llamado por el navegador periodicamente via AJAX.
     */
    public function transcripcionLoteEstado(Request $request)
    {
        $jobIds = $request->query('job_ids', []);
        $resultados = [];

        foreach ($jobIds as $jobId) {
            $cached = Cache::get("transcripcion_job_{$jobId}");

            if (!$cached) {
                $resultados[$jobId] = ['status' => 'not_found'];
                continue;
            }

            try {
                $jobStatus = $this->procesamientoService->getTranscriptionJob($jobId);
                $status = $jobStatus['status'] ?? 'unknown';

                // Si completó y no se ha guardado aún, guardar la transcripcion
                if ($status === 'completed' && ($jobStatus['success'] ?? false) && !$cached['saved']) {
                    $texto = trim($jobStatus['text'] ?? '');

                    if (!empty($texto)) {
                        $adjunto = Adjunto::find($cached['adjunto_id']);
                        if ($adjunto) {
                            $adjunto->texto_extraido = $texto;
                            $adjunto->texto_extraido_at = now();
                            $adjunto->save();
                        }

                        $entrevista = Entrevista::find($cached['entrevista_id']);
                        if ($entrevista) {
                            $this->regenerarTranscripcionCompleta($entrevista);

                            TrazaActividad::create([
                                'fecha_hora'  => now(),
                                'id_usuario'  => $cached['user_id'],
                                'accion'      => 'completar_transcripcion',
                                'objeto'      => 'adjunto',
                                'id_registro' => $cached['adjunto_id'],
                                'codigo'      => $cached['codigo'],
                                'referencia'  => 'Transcripcion automatica (lote): ' . ($cached['nombre'] ?? ''),
                                'ip'          => $cached['ip'],
                            ]);

                            $cached['saved'] = true;
                            Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                        }
                    } else {
                        // Audio sin voz detectable — marcar como error
                        $status = 'failed';
                        $jobStatus['error'] = 'El audio no contiene voz detectable';
                    }
                }

                // Registrar error si el job falló (solo una vez)
                if ($status === 'failed' && !($cached['error_traced'] ?? false)) {
                    TrazaActividad::create([
                        'fecha_hora'  => now(),
                        'id_usuario'  => $cached['user_id'],
                        'accion'      => 'error_transcripcion',
                        'objeto'      => 'adjunto',
                        'id_registro' => $cached['adjunto_id'],
                        'codigo'      => $cached['codigo'],
                        'referencia'  => ($cached['nombre'] ?? '') . ': ' . ($jobStatus['error'] ?? 'Error desconocido'),
                        'ip'          => $cached['ip'],
                    ]);
                    $cached['error_traced'] = true;
                    Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                }

                $resultados[$jobId] = [
                    'id'                => $cached['entrevista_id'],
                    'codigo'            => $cached['codigo'],
                    'status'            => $status,
                    'saved'             => $cached['saved'],
                    'success'           => $jobStatus['success'] ?? false,
                    'error'             => $jobStatus['error'] ?? null,
                    'speakers_count'    => $jobStatus['speakers_count'] ?? 0,
                    'text_length'       => strlen($jobStatus['text'] ?? ''),
                    'diarization_error' => $jobStatus['diarization_error'] ?? null,
                ];

            } catch (\Exception $e) {
                if (!($cached['error_traced'] ?? false)) {
                    TrazaActividad::create([
                        'fecha_hora'  => now(),
                        'id_usuario'  => $cached['user_id'],
                        'accion'      => 'error_transcripcion',
                        'objeto'      => 'adjunto',
                        'id_registro' => $cached['adjunto_id'],
                        'codigo'      => $cached['codigo'],
                        'referencia'  => ($cached['nombre'] ?? '') . ': ' . $e->getMessage(),
                        'ip'          => $cached['ip'],
                    ]);
                    $cached['error_traced'] = true;
                    Cache::put("transcripcion_job_{$jobId}", $cached, now()->addDays(2));
                }
                $resultados[$jobId] = [
                    'id'     => $cached['entrevista_id'],
                    'codigo' => $cached['codigo'],
                    'status' => 'error',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return response()->json($resultados);
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
    public function edicion(Request $request)
    {
        $user = Auth::user();
        $nivel = $user->id_nivel;

        // Transcriptor (nivel 4): solo ve sus asignaciones
        if ($nivel == 4) {
            // Incluir aprobadas para que vea su trabajo finalizado
            $asignaciones = AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                ->with(['rel_entrevista', 'rel_adjunto', 'rel_revisor',
                        'rel_entrevista.rel_adjuntos' => function($q) {
                    $q->where(function($inner) {
                        $inner->where('tipo_mime', 'like', '%audio%')
                              ->orWhere('tipo_mime', 'like', '%video%');
                    })->where('nombre_original', 'not like', '%[Anonimizado]%');
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

            $stats = $this->calcStatsTranscriptor($user->id_entrevistador);

            return view('procesamientos.edicion-transcriptor', compact('asignaciones', 'stats'));
        }

        // Admin/Líder: ve todas las entrevistas y asignaciones (excluir anonimizados)
        $queryPendientes = Entrevista::where('id_activo', 1)
            ->whereHas('rel_adjuntos', function($q) {
                $q->where(function($inner) {
                    $inner->where('tipo_mime', 'like', '%audio%')
                          ->orWhere('tipo_mime', 'like', '%video%');
                })->where('nombre_original', 'not like', '%[Anonimizado]%');
            });

        // Filtro dependencia
        if ($request->filled('filtro_dependencia')) {
            $queryPendientes->where('id_dependencia_origen', $request->filtro_dependencia);
        }

        // Filtro código
        if ($request->filled('filtro_codigo')) {
            $queryPendientes->where('entrevista_codigo', 'ilike', '%' . $request->filtro_codigo . '%');
        }

        // Filtro entrevistador
        if ($request->filled('filtro_entrevistador')) {
            $queryPendientes->where('id_entrevistador', $request->filtro_entrevistador);
        }

        // Filtro transcripción automática
        if ($request->filled('filtro_trans_auto')) {
            if ($request->filtro_trans_auto === 'con') {
                $queryPendientes->whereHas('rel_adjuntos', function($q) {
                    $q->whereNotNull('texto_extraido')
                      ->where(function($i) { $i->where('tipo_mime', 'like', '%audio%')->orWhere('tipo_mime', 'like', '%video%'); });
                });
            } else {
                $queryPendientes->whereDoesntHave('rel_adjuntos', function($q) {
                    $q->whereNotNull('texto_extraido')
                      ->where(function($i) { $i->where('tipo_mime', 'like', '%audio%')->orWhere('tipo_mime', 'like', '%video%'); });
                });
            }
        }

        // Filtro estado de asignación
        if ($request->filled('filtro_asignacion')) {
            if ($request->filtro_asignacion === 'sin_asignar') {
                $queryPendientes->whereNotExists(function($q) {
                    $q->from('esclarecimiento.asignacion_transcripcion as at')
                      ->whereColumn('at.id_e_ind_fvt', 'esclarecimiento.e_ind_fvt.id_e_ind_fvt')
                      ->whereNotIn('at.estado', ['aprobada']);
                });
            } else {
                $queryPendientes->whereExists(function($q) use ($request) {
                    $q->from('esclarecimiento.asignacion_transcripcion as at')
                      ->whereColumn('at.id_e_ind_fvt', 'esclarecimiento.e_ind_fvt.id_e_ind_fvt')
                      ->where('at.estado', $request->filtro_asignacion);
                });
            }
        }

        $pendientes = $queryPendientes
            ->with(['rel_adjuntos' => function($q) {
                $q->where(function($inner) {
                    $inner->where('tipo_mime', 'like', '%audio%')
                          ->orWhere('tipo_mime', 'like', '%video%');
                })->where('nombre_original', 'not like', '%[Anonimizado]%');
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Asignaciones pendientes de revisión
        $pendientesRevision = AsignacionTranscripcion::where('estado', AsignacionTranscripcion::ESTADO_ENVIADA_REVISION)
            ->with(['rel_entrevista', 'rel_transcriptor.rel_usuario', 'rel_adjunto'])
            ->orderBy('fecha_envio_revision', 'asc')
            ->get();

        // Transcriptores y Líderes disponibles para asignar (nivel 2 y 4)
        $transcriptores = Entrevistador::whereIn('id_nivel', [2, 4])
            ->with('rel_usuario')
            ->get();

        // Cargar todas las asignaciones agrupadas por entrevista (puede haber varias por audio)
        $todasAsignaciones = AsignacionTranscripcion::whereIn('estado', [
                AsignacionTranscripcion::ESTADO_ASIGNADA,
                AsignacionTranscripcion::ESTADO_EN_EDICION,
                AsignacionTranscripcion::ESTADO_ENVIADA_REVISION,
                AsignacionTranscripcion::ESTADO_RECHAZADA,
                AsignacionTranscripcion::ESTADO_APROBADA,
            ])
            ->with(['rel_transcriptor.rel_usuario', 'rel_revisor', 'rel_adjunto'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupadas por id_e_ind_fvt para la vista
        $asignacionesPorEntrevista = $todasAsignaciones->groupBy('id_e_ind_fvt');

        // Stats globales reutilizando el mismo método del centro de control
        $stats = $this->calcStatsGlobales('transcripcion');

        // Mis asignaciones (para Líder nivel 2)
        $misAsignaciones = collect();
        if ($nivel == 2) {
            $misAsignaciones = AsignacionTranscripcion::where('id_transcriptor', $user->id_entrevistador)
                ->whereNotIn('estado', [AsignacionTranscripcion::ESTADO_APROBADA])
                ->with(['rel_entrevista', 'rel_adjunto'])
                ->orderByRaw("CASE estado
                    WHEN 'rechazada' THEN 1
                    WHEN 'asignada' THEN 2
                    WHEN 'en_edicion' THEN 3
                    WHEN 'enviada_revision' THEN 4
                    ELSE 5 END")
                ->orderBy('fecha_asignacion', 'desc')
                ->get();
        }

        // Datos para filtros
        $dependenciasEdicion = CatItem::where('id_cat', 4)->where('habilitado', 1)->orderBy('orden')->get();
        $entrevistadoresEdicion = Entrevistador::whereHas('rel_usuario')->with('rel_usuario')->orderBy('id_entrevistador')->get();
        $estadosAsignacionEdicion = [
            'sin_asignar'      => 'Sin asignar',
            'asignada'         => 'Asignada',
            'en_edicion'       => 'En edición',
            'enviada_revision'  => 'Enviada a revisión',
            'rechazada'        => 'Rechazada',
            'aprobada'         => 'Aprobada',
        ];

        return view('procesamientos.edicion', compact(
            'pendientes', 'pendientesRevision', 'transcriptores', 'asignacionesPorEntrevista', 'stats', 'misAsignaciones',
            'dependenciasEdicion', 'entrevistadoresEdicion', 'estadosAsignacionEdicion'
        ));
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

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion' => 'editar_transcripcion',
            'objeto' => 'entrevista',
            'id_registro' => $entrevista->id_e_ind_fvt,
            'codigo' => $entrevista->entrevista_codigo,
            'referencia' => 'Edición directa de transcripción',
            'ip' => $request->ip(),
        ]);

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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
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

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'aprobar_transcripcion',
            'objeto' => 'entrevista',
            'id_registro' => $entrevista->id_e_ind_fvt,
            'codigo' => $entrevista->entrevista_codigo,
            'referencia' => 'Aprobación directa de transcripción',
            'ip' => request()->ip(),
        ]);

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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
            return response()->json(['error' => 'No tiene permisos para asignar'], 403);
        }

        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'id_transcriptor' => 'required|integer',
            'id_adjunto' => 'required|integer',
        ]);

        // Verificar que no exista una asignación activa para este audio específico
        $existente = AsignacionTranscripcion::where('id_adjunto', $request->id_adjunto)
            ->whereIn('estado', [
                AsignacionTranscripcion::ESTADO_ASIGNADA,
                AsignacionTranscripcion::ESTADO_EN_EDICION,
                AsignacionTranscripcion::ESTADO_ENVIADA_REVISION,
            ])
            ->first();

        if ($existente) {
            return response()->json([
                'error' => 'Este audio ya tiene una asignación activa'
            ], 400);
        }

        // Pre-llenar con el texto del audio específico (o transcripción aprobada previa)
        $adjunto = Adjunto::findOrFail($request->id_adjunto);
        $transcripcionPrevia = AsignacionTranscripcion::where('id_adjunto', $request->id_adjunto)
            ->where('estado', AsignacionTranscripcion::ESTADO_APROBADA)
            ->orderBy('fecha_revision', 'desc')
            ->first();

        $textoInicial = $transcripcionPrevia
            ? $transcripcionPrevia->transcripcion_editada
            : $adjunto->texto_extraido;

        $asignacion = AsignacionTranscripcion::create([
            'id_e_ind_fvt' => $request->id_e_ind_fvt,
            'id_adjunto' => $request->id_adjunto,
            'id_transcriptor' => $request->id_transcriptor,
            'id_asignado_por' => $user->id,
            'estado' => AsignacionTranscripcion::ESTADO_ASIGNADA,
            'fecha_asignacion' => now(),
            'transcripcion_editada' => $textoInicial,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'asignar_transcripcion',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Asignación de transcripción para entrevista #' . $request->id_e_ind_fvt,
            'ip' => $request->ip(),
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
        $asignacion = AsignacionTranscripcion::with([
            'rel_adjunto',
            'rel_entrevista',
            'rel_entrevista.rel_adjuntos' => function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            }
        ])->findOrFail($id);

        // Puede editar si es supervisor (puedeEditar en el módulo) o es el transcriptor asignado
        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion') &&
            $asignacion->id_transcriptor != $user->id_entrevistador) {
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

        // Si es asignación por audio individual, mostrar solo ese audio en el reproductor
        if ($asignacion->id_adjunto && $asignacion->rel_adjunto) {
            $entrevista->setRelation('rel_adjuntos', collect([$asignacion->rel_adjunto]));
        }

        return view('procesamientos.editar-transcripcion-asignada', compact('asignacion', 'entrevista'));
    }

    /**
     * Guardar transcripción editada (Transcriptor)
     */
    public function guardarTranscripcionAsignada(Request $request, $id)
    {
        $user = Auth::user();
        $asignacion = AsignacionTranscripcion::findOrFail($id);

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion') &&
            $asignacion->id_transcriptor != $user->id_entrevistador) {
            return response()->json(['error' => 'No tiene permisos'], 403);
        }

        $request->validate([
            'transcripcion' => 'required|string',
        ]);

        $asignacion->transcripcion_editada = $request->transcripcion;
        $asignacion->updated_at = now();
        $asignacion->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'editar_transcripcion',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Edición de transcripción asignada (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => $request->ip(),
        ]);

        flash('Transcripción guardada correctamente.')->success();
        return redirect()->back();
    }

    /**
     * Enviar transcripción a revisión (Transcriptor)
     */
    public function heartbeatEdicion($id)
    {
        $asignacion = AsignacionTranscripcion::findOrFail($id);
        $asignacion->increment('segundos_edicion_activa', 30);
        return response()->json(['ok' => true]);
    }

    public function enviarARevision($id)
    {
        $user = Auth::user();
        $asignacion = AsignacionTranscripcion::findOrFail($id);

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion') &&
            $asignacion->id_transcriptor != $user->id_entrevistador) {
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
        if (request()->filled('calificacion_audio')) {
            $asignacion->calificacion_audio = (int) request('calificacion_audio');
        }
        $asignacion->observaciones_envio = request('observaciones_envio') ?: null;
        $asignacion->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'enviar_revision',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Envío a revisión de transcripción (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => request()->ip(),
        ]);

        flash('Transcripción enviada a revisión.')->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Ver transcripción para revisión (Admin/Líder)
     */
    public function verRevision($id)
    {
        $user = Auth::user();

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
            flash('No tiene permisos para revisar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $asignacion = AsignacionTranscripcion::with([
            'rel_adjunto',
            'rel_entrevista',
            'rel_entrevista.rel_adjuntos' => function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            },
            'rel_transcriptor.rel_usuario',
            'rel_asignado_por'
        ])->findOrFail($id);

        $entrevista = $asignacion->rel_entrevista;

        // Si es asignación por audio individual, mostrar solo ese audio
        if ($asignacion->id_adjunto && $asignacion->rel_adjunto) {
            $entrevista->setRelation('rel_adjuntos', collect([$asignacion->rel_adjunto]));
        }

        return view('procesamientos.revisar-transcripcion', compact('asignacion', 'entrevista'));
    }

    /**
     * Aprobar transcripción (Admin/Líder)
     */
    public function aprobarTranscripcionAsignada(Request $request, $id)
    {
        $user = Auth::user();

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
            flash('No tiene permisos para aprobar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $asignacion = AsignacionTranscripcion::findOrFail($id);
        $entrevista = Entrevista::findOrFail($asignacion->id_e_ind_fvt);

        if ($asignacion->id_adjunto) {
            // Asignación por audio: guardar texto de vuelta en el adjunto y regenerar combinada
            $adjunto = Adjunto::find($asignacion->id_adjunto);
            if ($adjunto) {
                $adjunto->texto_extraido = $asignacion->transcripcion_editada;
                $adjunto->texto_extraido_at = now();
                $adjunto->save();
                $this->regenerarTranscripcionCompleta($entrevista);
                // Promover automatizada recién regenerada a transcripción final
                $textoFinal = $entrevista->fresh()->getTranscripcionAutomatizada();
                if ($textoFinal) {
                    $entrevista->guardarTranscripcionFinal($textoFinal, $user->id);
                }
            }
        } else {
            // Asignación de entrevista completa: guardar como transcripción final
            $entrevista->guardarTranscripcionFinal(
                $asignacion->transcripcion_editada,
                $user->id
            );
        }

        // Actualizar asignación
        $asignacion->estado = AsignacionTranscripcion::ESTADO_APROBADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $historial = $asignacion->historial_comentarios ?? [];
        $historial[] = [
            'accion'   => 'aprobada',
            'fecha'    => now()->toIso8601String(),
            'revisor'  => $user->name,
            'comentario' => $request->comentario ?? '',
        ];
        $asignacion->historial_comentarios = $historial;
        $asignacion->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'aprobar_transcripcion',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Aprobación de transcripción asignada (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => $request->ip(),
        ]);

        flash('Transcripción aprobada y guardada como adjunto de Transcripción Final.')->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Rechazar transcripción (Admin/Líder)
     */
    public function rechazarTranscripcion(Request $request, $id)
    {
        $user = Auth::user();

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
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
        $historial = $asignacion->historial_comentarios ?? [];
        $historial[] = [
            'accion'   => 'rechazada',
            'fecha'    => now()->toIso8601String(),
            'revisor'  => $user->name,
            'comentario' => $request->comentario,
        ];
        $asignacion->historial_comentarios = $historial;
        $asignacion->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'rechazar_transcripcion',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Rechazo de transcripción: ' . $request->comentario,
            'ip' => $request->ip(),
        ]);

        flash('Transcripción rechazada. El transcriptor ha sido notificado.')->warning();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Desasignar transcripción (Admin/Líder) — cualquier estado
     */
    public function desasignarTranscripcion($id)
    {
        $user = Auth::user();

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
            flash('No tiene permisos para desasignar transcripciones.')->error();
            return redirect()->route('procesamientos.edicion');
        }

        $asignacion = AsignacionTranscripcion::with(['rel_transcriptor.rel_usuario'])->findOrFail($id);
        $nombreTranscriptor = $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A';
        $estadoAnterior = $asignacion->estado;

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'desasignar_transcripcion',
            'objeto' => 'transcripcion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => "Desasignación de transcripción (entrevista #{$asignacion->id_e_ind_fvt}, estado anterior: {$estadoAnterior}, transcriptor: {$nombreTranscriptor})",
            'ip' => request()->ip(),
        ]);

        $asignacion->delete();

        flash("Asignación eliminada (transcriptor: {$nombreTranscriptor}).") ->success();
        return redirect()->route('procesamientos.edicion');
    }

    /**
     * Guardar anotaciones del revisor (HTML con <mark>) en transcripcion_anotada
     */
    public function guardarAnotaciones(Request $request, $id)
    {
        $user = Auth::user();
        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.transcripcion')) {
            return response()->json(['error' => 'Sin permisos'], 403);
        }

        $asignacion = AsignacionTranscripcion::findOrFail($id);
        $html = strip_tags($request->input('anotaciones', ''), '<mark><span><p><br><strong><em><b><i><div>');
        $asignacion->transcripcion_anotada = $html ?: null;
        $asignacion->save();

        return response()->json(['success' => true]);
    }

    /**
     * Exportar todas las asignaciones a Excel (solo Admin)
     */
    public function exportarAsignaciones()
    {
        $user = Auth::user();
        if ($user->id_nivel != 1) {
            flash('Solo el administrador puede exportar asignaciones.')->error();
            return redirect()->route('procesamientos.index');
        }

        $filename = 'asignaciones_transcripcion_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new AsignacionesTranscripcionExport(), $filename);
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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion')) {
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

        // Si hay una asignación aprobada previa, copiar su texto_anonimizado
        $anonimizacionPrevia = AsignacionAnonimizacion::where('id_e_ind_fvt', $request->id_e_ind_fvt)
            ->where('estado', AsignacionAnonimizacion::ESTADO_APROBADA)
            ->orderBy('fecha_revision', 'desc')
            ->first();

        $asignacion = AsignacionAnonimizacion::create([
            'id_e_ind_fvt' => $request->id_e_ind_fvt,
            'id_anonimizador' => $request->id_anonimizador,
            'id_asignado_por' => $user->id,
            'estado' => AsignacionAnonimizacion::ESTADO_ASIGNADA,
            'fecha_asignacion' => now(),
            'tipos_anonimizar' => $request->tipos_anonimizar ?? 'PER,LOC',
            'formato_reemplazo' => $request->formato_reemplazo ?? 'brackets',
            'texto_anonimizado' => $anonimizacionPrevia ? $anonimizacionPrevia->texto_anonimizado : null,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'asignar_anonimizacion',
            'objeto' => 'anonimizacion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Asignación de anonimización para entrevista #' . $request->id_e_ind_fvt,
            'ip' => $request->ip(),
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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion') &&
            $asignacion->id_anonimizador != $user->id_entrevistador) {
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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion') &&
            $asignacion->id_anonimizador != $user->id_entrevistador) {
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
                    // Buscar entidad preferentemente por id_entidad (más confiable)
                    if (!empty($ent['db_id'])) {
                        $entidadDB = EntidadDetectada::where('id_entidad', $ent['db_id'])
                            ->where('id_e_ind_fvt', $idEntrevista)
                            ->first();
                    } else {
                        $entidadDB = EntidadDetectada::where('id_e_ind_fvt', $idEntrevista)
                            ->where('texto', $ent['text'])
                            ->where('posicion_inicio', $ent['start'])
                            ->first();
                    }

                    if ($entidadDB) {
                        // excluir_anonimizacion = true significa que NO se cubre (descubierta)
                        $entidadDB->excluir_anonimizacion = !$ent['cubierta'];
                        $entidadDB->save();
                    }
                }
            }
        }

        // Eliminar del BD las entidades cuya etiqueta fue removida por el usuario
        if ($request->has('entidades_eliminadas')) {
            $eliminadas = json_decode($request->entidades_eliminadas, true);
            if (is_array($eliminadas) && count($eliminadas) > 0) {
                $idEntrevista = $asignacion->id_e_ind_fvt;
                foreach ($eliminadas as $ent) {
                    if (!empty($ent['id'])) {
                        // ent.id en entidades[] es el id_entidad de la BD
                        EntidadDetectada::where('id_entidad', $ent['id'])
                            ->where('id_e_ind_fvt', $idEntrevista)
                            ->delete();
                    } else {
                        EntidadDetectada::where('id_e_ind_fvt', $idEntrevista)
                            ->where('texto', $ent['text'])
                            ->where('posicion_inicio', $ent['start'])
                            ->delete();
                    }
                }
            }
        }

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'editar_anonimizacion',
            'objeto' => 'anonimizacion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Edición de anonimización asignada (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => $request->ip(),
        ]);

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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion') &&
            $asignacion->id_anonimizador != $user->id_entrevistador) {
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

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'enviar_revision',
            'objeto' => 'anonimizacion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Envío a revisión de anonimización (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => request()->ip(),
        ]);

        flash('Anonimizacion enviada a revision.')->success();
        return redirect()->route('procesamientos.anonimizacion');
    }

    /**
     * Ver anonimizacion para revision (Admin/Lider)
     */
    public function verRevisionAnonimizacion($id)
    {
        $user = Auth::user();

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion')) {
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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion')) {
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

        // Guardar como adjunto público anonimizado en el expediente
        $entrevista->guardarTranscripcionAnonimizada($asignacion->texto_anonimizado, $user->id);

        // Actualizar asignacion
        $asignacion->estado = AsignacionAnonimizacion::ESTADO_APROBADA;
        $asignacion->fecha_revision = now();
        $asignacion->id_revisor = $user->id;
        $asignacion->comentario_revision = $request->comentario;
        $asignacion->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'aprobar_anonimizacion',
            'objeto' => 'anonimizacion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Aprobación de anonimización (entrevista #' . $asignacion->id_e_ind_fvt . ')',
            'ip' => $request->ip(),
        ]);

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

        if (!RolModuloPermiso::puedeEditar($user->id_nivel, 'procesamientos.anonimizacion')) {
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

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'rechazar_anonimizacion',
            'objeto' => 'anonimizacion',
            'id_registro' => $asignacion->id_asignacion,
            'referencia' => 'Rechazo de anonimización: ' . $request->comentario,
            'ip' => $request->ip(),
        ]);

        flash('Anonimizacion rechazada. El anonimizador ha sido notificado.')->warning();
        return redirect()->route('procesamientos.anonimizacion');
    }
}
