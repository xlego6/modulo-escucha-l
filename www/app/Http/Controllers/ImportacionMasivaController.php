<?php

namespace App\Http\Controllers;

use App\Jobs\ProcesarExpedienteImportacionJob;
use App\Models\CatItem;
use App\Models\Entrevistador;
use App\Models\Geo;
use App\Models\ImportacionExpediente;
use App\Models\ImportacionMasiva;
use App\Models\TrazaActividad;
use App\Services\ImportacionMasivaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImportacionMasivaController extends Controller
{
    public function __construct(private ImportacionMasivaService $svc)
    {
        $this->middleware('auth');
    }

    // -------------------------------------------------------------------------
    // Paso 0 – Listado de importaciones
    // -------------------------------------------------------------------------

    public function index()
    {
        $importaciones = ImportacionMasiva::orderByDesc('created_at')
            ->with('rel_usuario')
            ->paginate(20);

        return view('importacion.index', compact('importaciones'));
    }

    // -------------------------------------------------------------------------
    // Paso 1 – Subir CSV y configurar rutas
    // -------------------------------------------------------------------------

    public function create()
    {
        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get();

        $dirTranscripciones = storage_path('app/importaciones/transcripciones');

        return view('importacion.create', compact('entrevistadores', 'dirTranscripciones'));
    }

    public function subir(Request $request)
    {
        $modo = $request->input('modo', 'crear');

        $request->validate([
            'archivo_csv'               => 'required|file|max:20480',
            'id_entrevistador'          => array_filter([
                                              $modo === 'crear' ? 'required' : 'nullable',
                                              'integer',
                                              \Illuminate\Validation\Rule::exists('esclarecimiento.entrevistador', 'id_entrevistador'),
                                          ]),
            'path_mappings'             => 'nullable|array',
            'path_mappings.*.unc'       => 'nullable|string|max:500',
            'path_mappings.*.linux'     => 'nullable|string|max:500',
            'tratamiento_transcripcion' => 'nullable|in:adjunto,automatizada,ambos',
            'dir_local'                 => 'nullable|string|max:500',
            'modo'                      => 'nullable|in:crear,actualizar',
        ]);

        // Verificar manualmente que el archivo sea CSV/TXT por extensión
        $ext = strtolower($request->file('archivo_csv')->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt'])) {
            return back()->withErrors(['archivo_csv' => 'El archivo debe tener extensión .csv o .txt']);
        }

        // Guardar CSV en storage temporal
        $archivo = $request->file('archivo_csv');
        $rutaCsv = $archivo->storeAs(
            'importaciones/csv',
            time() . '_' . \Illuminate\Support\Str::random(8) . '.csv',
            'local'
        );

        $rutaAbsoluta = storage_path('app/' . $rutaCsv);

        // Parsear
        try {
            $expedientes = $this->svc->parsearCsv($rutaAbsoluta);
        } catch (\Exception $e) {
            return back()->withErrors(['archivo_csv' => 'Error al leer el CSV: ' . $e->getMessage()]);
        }

        if (empty($expedientes)) {
            return back()->withErrors(['archivo_csv' => 'El CSV no contiene filas de datos válidas.']);
        }

        // Filtrar path_mappings vacíos
        $mappings = collect($request->path_mappings ?? [])
            ->filter(fn($m) => !empty($m['unc']) && !empty($m['linux']))
            ->values()
            ->toArray();

        $dirTranscripciones = rtrim($request->input('dir_local', ''), '/') ?: storage_path('app/importaciones/transcripciones');
        if (!is_dir($dirTranscripciones)) {
            @mkdir($dirTranscripciones, 0775, true);
        }

        // Resolver rutas y construir archivos con estado de existencia
        foreach ($expedientes as &$exp) {
            $archivosResueltos = [];
            foreach ($exp['archivos_csv'] as $arch) {
                // Archivos que no son audio/video principal pueden estar en la carpeta local
                $dirLocal = ($arch['id_tipo'] !== 310) ? $dirTranscripciones : '';
                $rutaLinux = $this->svc->resolverRuta($arch['ruta'], $mappings, $dirLocal);
                $info      = $this->svc->verificarArchivo($rutaLinux);

                $archivosResueltos[] = array_merge($arch, [
                    'ruta_linux'    => $rutaLinux,
                    'existe'        => $info['existe'],
                    'es_directorio' => $info['es_directorio'],
                    'tamano'        => $info['tamano'],
                    'convertir'     => $info['existe'] && !$info['es_directorio'] && $info['tamano']
                        ? $this->svc->necesitaConversion($rutaLinux, $info['tamano'])
                        : false,
                ]);
            }
            $exp['archivos_resueltos'] = $archivosResueltos;
        }
        unset($exp);

        // Crear registro de sesión de importación
        $importacion = ImportacionMasiva::create([
            'id_usuario'       => Auth::id(),
            'nombre_archivo'   => $archivo->getClientOriginalName(),
            'ruta_csv'         => $rutaCsv,
            'estado'           => ImportacionMasiva::ESTADO_MAPEANDO,
            'total_expedientes' => count($expedientes),
            'configuracion'    => [
                'path_mappings'             => $mappings,
                'id_entrevistador'          => (int) $request->id_entrevistador,
                'mapeos_catalogos'          => [],
                'mapeos_geo'                => [],
                'tratamiento_transcripcion' => $request->input('tratamiento_transcripcion', 'automatizada'),
                'dir_local'                 => $dirTranscripciones,
                'modo'                      => $modo,
            ],
        ]);

        // Guardar expedientes en DB
        foreach ($expedientes as $exp) {
            ImportacionExpediente::create([
                'id_importacion'   => $importacion->id_importacion,
                'id_csv'           => $exp['id_csv'],
                'estado'           => ImportacionExpediente::ESTADO_PENDIENTE,
                'datos_csv'        => [
                    'cols'     => $exp['datos'],    // array indexado de columnas del CSV
                    'personas' => $exp['personas'],  // personas parseadas
                ],
                'filas_originales' => $exp['filas_originales'],
                'archivos'         => $exp['archivos_resueltos'],
                'advertencias'     => $this->generarAdvertencias($exp, $modo),
            ]);
        }

        TrazaActividad::create([
            'fecha_hora'  => now(),
            'id_usuario'  => Auth::id(),
            'accion'      => 'crear',
            'objeto'      => 'importacion_masiva',
            'id_registro' => $importacion->id_importacion,
            'referencia'  => 'Importación masiva: ' . $archivo->getClientOriginalName() . ' (' . count($expedientes) . ' expedientes)',
            'ip'          => $request->ip(),
        ]);

        return redirect()->route('importacion.mapear', $importacion->id_importacion);
    }

    // -------------------------------------------------------------------------
    // Paso 2 – Mapeo de catálogos
    // -------------------------------------------------------------------------

    public function mapear(int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        // Reconstruir estructura para extraer valores únicos desde datos guardados
        $expedientesParsed = $importacion->rel_expedientes->map(function ($ie) {
            return [
                'datos'        => $ie->datos_csv['cols'] ?? [],
                'personas'     => $ie->datos_csv['personas'] ?? [],
                'archivos_csv' => [],
            ];
        })->toArray();

        $valoresUnicos = $this->svc->extraerValoresUnicos($expedientesParsed);
        $sugerencias   = $this->svc->sugerirMapeos($valoresUnicos);

        // Catálogos para los dropdowns
        $catalogos = $this->getCatalogosParaMapeo();

        // Geografía
        $departamentos = Geo::where('nivel', 2)->orderBy('descripcion')->get();

        $mapeosActuales = $importacion->configuracion['mapeos_catalogos'] ?? [];
        $mapeosGeo      = $importacion->configuracion['mapeos_geo'] ?? [];

        return view('importacion.mapear', compact(
            'importacion', 'valoresUnicos', 'sugerencias',
            'catalogos', 'departamentos', 'mapeosActuales', 'mapeosGeo'
        ));
    }

    public function guardarMapeos(Request $request, int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        $config = $importacion->configuracion;
        $config['mapeos_catalogos'] = $request->input('mapeos', []);
        $config['mapeos_geo']       = $request->input('mapeos_geo', []);

        $importacion->configuracion = $config;
        $importacion->estado        = ImportacionMasiva::ESTADO_CONFIRMADO;
        $importacion->save();

        return redirect()->route('importacion.confirmar', $id);
    }

    // -------------------------------------------------------------------------
    // Paso 3 – Vista previa y confirmación
    // -------------------------------------------------------------------------

    public function confirmar(int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        $expedientes = $importacion->rel_expedientes()
            ->orderBy('id_csv')
            ->get();

        $config        = $importacion->configuracion;
        $mapeosCat     = $config['mapeos_catalogos'] ?? [];
        $mapeosGeo     = $config['mapeos_geo']       ?? [];
        $pathMappings  = $config['path_mappings']    ?? [];

        return view('importacion.confirmar', compact(
            'importacion', 'expedientes', 'mapeosCat', 'mapeosGeo', 'pathMappings'
        ));
    }

    public function procesar(Request $request, int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        if (!in_array($importacion->estado, [
            ImportacionMasiva::ESTADO_CONFIRMADO,
            ImportacionMasiva::ESTADO_CON_ERRORES, // permitir reintentar
        ])) {
            return back()->withErrors(['error' => 'Esta importación ya fue procesada o no está lista.']);
        }

        $importacion->estado = ImportacionMasiva::ESTADO_PROCESANDO;
        $importacion->save();

        // Encolar un job por expediente (solo los pendientes / errores)
        $expedientes = $importacion->rel_expedientes()
            ->whereIn('estado', [ImportacionExpediente::ESTADO_PENDIENTE, ImportacionExpediente::ESTADO_ERROR])
            ->get();

        foreach ($expedientes as $exp) {
            $exp->estado = ImportacionExpediente::ESTADO_PENDIENTE; // resetear si era error
            $exp->error_mensaje = null;
            $exp->save();

            ProcesarExpedienteImportacionJob::dispatch($exp->id_imp_expediente);
        }

        TrazaActividad::create([
            'fecha_hora'  => now(),
            'id_usuario'  => Auth::id(),
            'accion'      => 'procesar',
            'objeto'      => 'importacion_masiva',
            'id_registro' => $importacion->id_importacion,
            'referencia'  => 'Procesamiento iniciado: ' . $expedientes->count() . ' expedientes encolados',
            'ip'          => $request->ip(),
        ]);

        return redirect()->route('importacion.monitor', $id);
    }

    // -------------------------------------------------------------------------
    // Paso 4 – Monitor de progreso
    // -------------------------------------------------------------------------

    public function monitor(int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        return view('importacion.monitor', compact('importacion'));
    }

    /**
     * Endpoint JSON para polling del monitor.
     */
    public function estado(int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);

        $expedientes = $importacion->rel_expedientes()
            ->orderBy('id_csv')
            ->get(['id_imp_expediente', 'id_csv', 'estado', 'error_mensaje', 'id_e_ind_fvt']);

        // Si todos terminaron, actualizar estado general
        $pendientes  = $expedientes->whereIn('estado', [ImportacionExpediente::ESTADO_PENDIENTE, ImportacionExpediente::ESTADO_PROCESANDO])->count();
        $completados = $expedientes->where('estado', ImportacionExpediente::ESTADO_COMPLETADO)->count();
        $errores     = $expedientes->where('estado', ImportacionExpediente::ESTADO_ERROR)->count();

        if ($pendientes === 0 && $importacion->estado === ImportacionMasiva::ESTADO_PROCESANDO) {
            $importacion->procesados  = $completados;
            $importacion->con_error   = $errores;
            $importacion->estado      = $errores > 0
                ? ImportacionMasiva::ESTADO_CON_ERRORES
                : ImportacionMasiva::ESTADO_COMPLETADO;
            $importacion->save();
        } else {
            $importacion->procesados = $completados;
            $importacion->con_error  = $errores;
            $importacion->save();
        }

        return response()->json([
            'estado'       => $importacion->estado,
            'porcentaje'   => $importacion->porcentaje,
            'total'        => $importacion->total_expedientes,
            'completados'  => $completados,
            'errores'      => $errores,
            'pendientes'   => $pendientes,
            'expedientes'  => $expedientes->map(fn($e) => [
                'id'              => $e->id_imp_expediente,
                'id_csv'          => $e->id_csv,
                'estado'          => $e->estado,
                'error'           => $e->error_mensaje,
                'id_e_ind_fvt'    => $e->id_e_ind_fvt,
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function autorizarAcceso(ImportacionMasiva $importacion): void
    {
        $user = Auth::user();
        // Solo el propio admin o cualquier admin puede ver/gestionar
        if ($user->id_nivel != 1 && $importacion->id_usuario != $user->id) {
            abort(403);
        }
    }

    private function generarAdvertencias(array $exp, string $modo = 'crear'): array
    {
        $advertencias = [];
        $datos = $exp['datos'];

        if ($modo === 'actualizar') {
            // En modo actualizar lo único indispensable es el código de entrevista (col 79)
            $codigo = trim($datos[79] ?? '');
            if ($codigo === '') {
                $advertencias[] = 'Columna 79 (código entrevista) vacía — este expediente no se puede actualizar';
            }
        } else {
            $camposRequeridos = [
                8  => 'Título',
                12 => 'Tipo de testimonio',
                13 => 'Formato del testimonio',
                17 => 'Modalidad',
                18 => 'Fecha toma inicial',
                19 => 'Fecha toma final',
            ];
            foreach ($camposRequeridos as $col => $etiqueta) {
                $val = trim($datos[$col] ?? '');
                if ($val === '' || strtolower($val) === 'n/a') {
                    $advertencias[] = "Campo requerido faltante: $etiqueta";
                }
            }
            if (empty($exp['personas'])) {
                $advertencias[] = 'No se detectaron datos de testimoniante(s)';
            }
        }

        if (empty($exp['archivos_csv'])) {
            $advertencias[] = 'No se encontraron rutas de archivos en este expediente';
        }

        return $advertencias;
    }

    private function getCatalogosParaMapeo(): array
    {
        $resultado = [];
        foreach (ImportacionMasivaService::CATALOGOS as $campo => $idCat) {
            $resultado[$campo] = CatItem::where('id_cat', $idCat)
                ->orderBy('orden')
                ->pluck('descripcion', 'id_item');
        }
        return $resultado;
    }
}
