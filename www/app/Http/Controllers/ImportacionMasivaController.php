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
            'archivo_csv'               => 'required|file|max:204800',
            'id_entrevistador'          => [
                                              $modo === 'crear' ? 'required' : 'nullable',
                                              'integer',
                                              function ($attr, $val, $fail) {
                                                  if ($val && !Entrevistador::where('id_entrevistador', $val)->exists()) {
                                                      $fail('El entrevistador seleccionado no existe.');
                                                  }
                                              },
                                          ],
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

        // Filtrar path_mappings vacíos
        $mappings = collect($request->path_mappings ?? [])
            ->filter(fn($m) => !empty($m['unc']) && !empty($m['linux']))
            ->values()
            ->toArray();

        $dirTranscripciones = rtrim($request->input('dir_local', ''), '/') ?: storage_path('app/importaciones/transcripciones');
        if (!is_dir($dirTranscripciones)) {
            @mkdir($dirTranscripciones, 0775, true);
        }

        // Crear registro de sesión de importación (total_expedientes se actualiza al final)
        $importacion = ImportacionMasiva::create([
            'id_usuario'        => Auth::id(),
            'nombre_archivo'    => $archivo->getClientOriginalName(),
            'ruta_csv'          => $rutaCsv,
            'estado'            => ImportacionMasiva::ESTADO_MAPEANDO,
            'total_expedientes' => 0,
            'configuracion'     => [
                'path_mappings'             => $mappings,
                'id_entrevistador'          => (int) $request->id_entrevistador,
                'mapeos_catalogos'          => [],
                'mapeos_geo'                => [],
                'tratamiento_transcripcion' => $request->input('tratamiento_transcripcion', 'automatizada'),
                'dir_local'                 => $dirTranscripciones,
                'modo'                      => $modo,
            ],
        ]);

        // Parsear y guardar expedientes en streaming: cada grupo se procesa y
        // descarta inmediatamente para evitar acumular todo el CSV en memoria.
        try {
            $totalExpedientes = $this->svc->parsearCsvCallback(
                $rutaAbsoluta,
                function (array $exp) use ($importacion, $mappings, $dirTranscripciones, $modo): void {
                    // Resolver rutas y verificar existencia de archivos
                    $archivosResueltos = [];
                    foreach ($exp['archivos_csv'] as $arch) {
                        $dirLocal  = ($arch['id_tipo'] !== 310) ? $dirTranscripciones : '';
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

                    ImportacionExpediente::create([
                        'id_importacion'   => $importacion->id_importacion,
                        'id_csv'           => $exp['id_csv'],
                        'estado'           => ImportacionExpediente::ESTADO_PENDIENTE,
                        'datos_csv'        => [
                            'cols'     => $exp['datos'],
                            'personas' => $exp['personas'],
                        ],
                        'filas_originales' => $exp['filas_originales'],
                        'archivos'         => $archivosResueltos,
                        'advertencias'     => $this->generarAdvertencias($exp, $modo),
                    ]);
                    // $exp sale de scope y PHP puede liberar su memoria
                }
            );
        } catch (\Exception $e) {
            $importacion->rel_expedientes()->delete();
            $importacion->delete();
            Storage::disk('local')->delete($rutaCsv);
            return back()->withErrors(['archivo_csv' => 'Error al leer el CSV: ' . $e->getMessage()]);
        }

        if ($totalExpedientes === 0) {
            $importacion->delete();
            Storage::disk('local')->delete($rutaCsv);
            return back()->withErrors(['archivo_csv' => 'El CSV no contiene filas de datos válidas.']);
        }

        $importacion->total_expedientes = $totalExpedientes;
        $importacion->save();

        TrazaActividad::create([
            'fecha_hora'  => now(),
            'id_usuario'  => Auth::id(),
            'accion'      => 'crear',
            'objeto'      => 'importacion_masiva',
            'id_registro' => $importacion->id_importacion,
            'referencia'  => 'Importación masiva: ' . $archivo->getClientOriginalName() . ' (' . $totalExpedientes . ' expedientes)',
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

        // Reconstruir estructura para extraer valores únicos desde datos guardados.
        // Se usa chunk() y se selecciona solo datos_csv para evitar cargar
        // filas_originales y archivos de todos los expedientes en memoria a la vez.
        $expedientesParsed = [];
        $importacion->rel_expedientes()
            ->select(['datos_csv'])
            ->chunk(200, function ($lote) use (&$expedientesParsed) {
                foreach ($lote as $ie) {
                    $expedientesParsed[] = [
                        'datos'        => $ie->datos_csv['cols'] ?? [],
                        'personas'     => $ie->datos_csv['personas'] ?? [],
                        'archivos_csv' => [],
                    ];
                }
            });

        $valoresUnicos = $this->svc->extraerValoresUnicos($expedientesParsed);
        $sugerencias   = $this->svc->sugerirMapeos($valoresUnicos);

        // Catálogos para los dropdowns
        $catalogos = $this->getCatalogosParaMapeo();

        // Geografía
        $departamentos = Geo::where('nivel', 2)->orderBy('descripcion')->get();
        $municipios    = Geo::where('nivel', 3)->orderBy('descripcion')->get();

        $mapeosActuales = $importacion->configuracion['mapeos_catalogos'] ?? [];
        $mapeosGeo      = $importacion->configuracion['mapeos_geo'] ?? [];

        return view('importacion.mapear', compact(
            'importacion', 'valoresUnicos', 'sugerencias',
            'catalogos', 'departamentos', 'municipios', 'mapeosActuales', 'mapeosGeo'
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
    // Cancelar / borrar importación
    // -------------------------------------------------------------------------

    public function destroy(Request $request, int $id)
    {
        $importacion = ImportacionMasiva::findOrFail($id);
        $this->autorizarAcceso($importacion);

        // No permitir borrar si ya completó exitosamente
        if ($importacion->estado === ImportacionMasiva::ESTADO_COMPLETADO) {
            return back()->withErrors(['error' => 'No se puede borrar una importación ya completada.']);
        }

        // Borrar el archivo CSV temporal si existe
        if ($importacion->ruta_csv && Storage::disk('local')->exists($importacion->ruta_csv)) {
            Storage::disk('local')->delete($importacion->ruta_csv);
        }

        // Borrar los expedientes de la sesión (no los expedientes reales ya creados)
        $importacion->rel_expedientes()->delete();
        $importacion->delete();

        TrazaActividad::create([
            'fecha_hora'  => now(),
            'id_usuario'  => Auth::id(),
            'accion'      => 'eliminar',
            'objeto'      => 'importacion_masiva',
            'id_registro' => $id,
            'referencia'  => 'Importación masiva cancelada/borrada: #' . $id,
            'ip'          => $request->ip(),
        ]);

        return redirect()->route('importacion.index')
            ->with('success', "Importación #$id cancelada y borrada correctamente.");
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
                ->where('habilitado', 1)
                ->orderBy('orden')
                ->pluck('descripcion', 'id_item');
        }
        return $resultado;
    }

    // -------------------------------------------------------------------------
    // Gestión de carpeta de transcripciones
    // -------------------------------------------------------------------------

    public function vaciarCarpeta(Request $request)
    {
        $dir = storage_path('app/importaciones/transcripciones');

        if (!is_dir($dir)) {
            return back()->with('success', 'La carpeta ya estaba vacía.');
        }

        $archivos = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        $borrados = 0;
        foreach ($archivos as $archivo) {
            $archivo->isDir() ? rmdir($archivo->getPathname()) : unlink($archivo->getPathname());
            $borrados++;
        }

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion'     => 'vaciar_carpeta_importacion',
            'objeto'     => 'importacion',
            'id_registro' => 0,
            'referencia' => "Carpeta de transcripciones vaciada ({$borrados} elementos eliminados)",
            'ip'         => $request->ip(),
        ]);

        return back()->with('success', "Carpeta vaciada correctamente ({$borrados} archivos/carpetas eliminados).");
    }

    public function subirArchivos(Request $request)
    {
        $request->validate([
            'archivos'   => 'required|array|min:1',
            'archivos.*' => 'required|file|max:2097152', // 2 GB
        ]);

        $dir = storage_path('app/importaciones/transcripciones');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $subidos = 0;
        foreach ($request->file('archivos') as $archivo) {
            $nombre = $archivo->getClientOriginalName();
            $archivo->move($dir, $nombre);
            $subidos++;
        }

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => Auth::id(),
            'accion'     => 'subir_archivos_importacion',
            'objeto'     => 'importacion',
            'id_registro' => 0,
            'referencia' => "{$subidos} archivo(s) subido(s) a carpeta de transcripciones",
            'ip'         => $request->ip(),
        ]);

        return back()->with('success', "{$subidos} archivo(s) subido(s) correctamente a la carpeta del servidor.");
    }
}
