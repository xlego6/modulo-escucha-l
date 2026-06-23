<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Adjunto;
use App\Models\TrazaActividad;
use App\Models\RolModuloPermiso;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermisoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function finDeDia(?string $fecha): ?Carbon
    {
        return $fecha ? Carbon::parse($fecha)->endOfDay() : null;
    }

    /**
     * Listado de permisos otorgados
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $entrevistadorActual = Entrevistador::where('id_usuario', $user->id)->first();

        $query = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_entrevista',
            'rel_otorgado_por.rel_usuario',
            'rel_adjunto',
            'rel_respondido_por',
        ]);

        // Solicitudes pendientes: quienes pueden aprobar/rechazar (puedeEditar en permisos)
        $solicitudesPendientes = collect();
        if (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
            RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            $solicitudesPendientes = Permiso::with(['rel_entrevistador.rel_usuario', 'rel_entrevista'])
                ->solicitudesPendientes()
                ->orderBy('fecha_solicitud', 'asc')
                ->get();
        } elseif (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
                  RolModuloPermiso::alcanceDependencia($user->id_nivel, 'permisos') && $entrevistadorActual) {
            // Alcance dependencia: solo solicitudes de entrevistas de su dependencia (sin eliminaciones)
            $solicitudesPendientes = Permiso::with(['rel_entrevistador.rel_usuario', 'rel_entrevista'])
                ->solicitudesPendientes()
                ->whereHas('rel_entrevista', function($q) use ($entrevistadorActual) {
                    $q->where('id_dependencia_origen', $entrevistadorActual->id_dependencia_origen);
                })
                ->where('tipo_solicitud', '!=', Permiso::SOLICITUD_ELIMINACION)
                ->orderBy('fecha_solicitud', 'asc')
                ->get();
        }

        // Mis solicitudes: quienes tienen alcance propio o de dependencia
        $misSolicitudes = collect();
        if ($entrevistadorActual && !RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            $misSolicitudes = Permiso::with(['rel_entrevista'])
                ->where('id_entrevistador', $entrevistadorActual->id_entrevistador)
                ->where('es_solicitud', true)
                ->orderBy('fecha_solicitud', 'desc')
                ->limit(10)
                ->get();
        }

        // Helper closure para aplicar filtros comunes de la lista
        $aplicarFiltrosLista = function() use ($query, $request) {
            if ($request->filled('codigo')) {
                $query->porCodigo($request->codigo);
            }
            if ($request->filled('estado')) {
                match ($request->estado) {
                    '1'         => $query->vigentes(),
                    '2'         => $query->revocados(),
                    'pendiente' => $query->solicitudesPendientes(),
                    'rechazado' => $query->where('es_solicitud', true)
                                         ->where('estado_solicitud', Permiso::SOLICITUD_RECHAZADA),
                    'vencido'   => $query->where('id_estado', Permiso::ESTADO_VIGENTE)
                                         ->where(function ($q) {
                                             $q->where('fecha_vencimiento', '<', now())
                                               ->orWhere('fecha_hasta', '<', now());
                                         }),
                    'programado' => $query->where('id_estado', Permiso::ESTADO_VIGENTE)
                                          ->where('fecha_desde', '>', now()),
                    default     => null,
                };
            }
            if ($request->filled('tipo')) {
                $query->where('id_tipo', $request->tipo);
            }
            // Excluir solicitudes pendientes (se muestran en su propio bloque)
            if (!$request->filled('estado') || $request->estado !== 'pendiente') {
                $query->where(function($q) {
                    $q->where('es_solicitud', false)
                      ->orWhere(function($q2) {
                          $q2->where('es_solicitud', true)
                             ->whereIn('estado_solicitud', [Permiso::SOLICITUD_APROBADA, Permiso::SOLICITUD_RECHAZADA, Permiso::SOLICITUD_REVOCADA]);
                      });
                });
            }
        };

        if (RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            // Admin/Líder: ve todos los permisos
            if ($request->filled('id_entrevistador')) {
                $query->where('id_entrevistador', $request->id_entrevistador);
            }
            if ($request->filled('id_e_ind_fvt')) {
                $query->where('id_e_ind_fvt', $request->id_e_ind_fvt);
            }
            $aplicarFiltrosLista();
        } elseif (RolModuloPermiso::alcanceDependencia($user->id_nivel, 'permisos') && $entrevistadorActual) {
            // Gestor de conocimiento: permisos de entrevistas de su dependencia
            $query->whereHas('rel_entrevista', function($q) use ($entrevistadorActual) {
                $q->where('id_dependencia_origen', $entrevistadorActual->id_dependencia_origen);
            });
            $aplicarFiltrosLista();
        } else {
            // Otros: solo sus propios registros
            if ($entrevistadorActual) {
                $query->where('id_entrevistador', $entrevistadorActual->id_entrevistador);
            } else {
                $query->whereRaw('1=0');
            }
        }

        $permisos = $query->orderBy('fecha_otorgado', 'desc')->paginate(20);

        $entrevistadores = collect();
        $tipos = [
            '' => '-- Todos --',
            1 => 'Lectura',
            2 => 'Escritura',
            3 => 'Completo',
        ];

        if (RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            $entrevistadores = Entrevistador::with('rel_usuario')
                ->orderBy('numero_entrevistador')
                ->get()
                ->pluck('rel_usuario.name', 'id_entrevistador')
                ->prepend('-- Todos --', '');
        }

        return view('permisos.index', compact('permisos', 'entrevistadores', 'tipos', 'solicitudesPendientes', 'misSolicitudes'));
    }

    /**
     * Formulario para otorgar permiso
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $tipos = $user->id_nivel == 5
            ? ['' => '-- Seleccione --', 1 => 'Lectura']
            : ['' => '-- Seleccione --', 1 => 'Lectura', 2 => 'Escritura', 3 => 'Completo'];

        // Pre-seleccionar entrevista si viene por parametro
        $entrevistaPreselect = null;
        $codigoPreselect = null;
        $id_entrevista_preselect = $request->get('entrevista');
        if ($id_entrevista_preselect) {
            $entrevistaPreselect = Entrevista::find($id_entrevista_preselect);
            $codigoPreselect = $entrevistaPreselect?->entrevista_codigo;
        }

        return view('permisos.create', compact('tipos', 'entrevistaPreselect', 'codigoPreselect', 'id_entrevista_preselect'));
    }

    /**
     * Búsqueda AJAX de entrevistas para el formulario de permisos
     */
    public function buscarEntrevistas(Request $request)
    {
        $q = trim($request->get('q', ''));
        $query = Entrevista::where('id_activo', 1)
            ->orderBy('entrevista_codigo')
            ->limit(25);

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('entrevista_codigo', 'ilike', "%{$q}%")
                    ->orWhere('titulo', 'ilike', "%{$q}%");
            });
        }

        $results = $query->get(['id_e_ind_fvt', 'entrevista_codigo', 'titulo']);

        return response()->json($results->map(fn($e) => [
            'id'   => $e->id_e_ind_fvt,
            'text' => "{$e->entrevista_codigo} — {$e->titulo}",
        ]));
    }

    /**
     * Búsqueda AJAX de entrevistadores para el formulario de permisos
     */
    public function buscarEntrevistadores(Request $request)
    {
        $q = trim($request->get('q', ''));

        $query = Entrevistador::with('rel_usuario')
            ->whereHas('rel_usuario', function ($sub) use ($q) {
                $sub->whereNotNull('name')->where('name', '!=', '');
                if ($q !== '') {
                    $sub->where('name', 'ilike', "%{$q}%");
                }
            })
            ->orderBy('numero_entrevistador')
            ->limit(30);

        $results = $query->get();

        return response()->json($results->map(fn($e) => [
            'id'   => $e->id_entrevistador,
            'text' => $e->rel_usuario->name,
        ]));
    }

    /**
     * Guardar nuevo permiso
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $tiposPermitidos = $user->id_nivel == 5 ? '1' : '1,2,3';
        $request->validate([
            'id_entrevistador' => 'required|integer',
            'id_tipo' => "required|in:{$tiposPermitidos}",
            'justificacion' => 'required|string|max:500',
            'archivo_soporte' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($user->id_nivel == 5 && (int) $request->id_tipo !== Permiso::TIPO_LECTURA) {
            return redirect()->back()->withInput()
                ->withErrors(['id_tipo' => 'El gestor de conocimiento solo puede otorgar permisos de lectura.']);
        }

        // Puede venir id_e_ind_fvt o codigos_entrevista
        if (!$request->filled('id_e_ind_fvt') && !$request->filled('codigos_entrevista')) {
            return redirect()->back()->withInput()->withErrors(['id_e_ind_fvt' => 'Debe seleccionar una entrevista o ingresar códigos.']);
        }

        $entrevistador = Entrevistador::find($request->id_entrevistador);
        if (!$entrevistador) {
            return redirect()->back()->withInput()->withErrors(['id_entrevistador' => 'El entrevistador seleccionado no existe.']);
        }

        // Procesar archivo de soporte si se adjuntó
        $idAdjunto = null;
        if ($request->hasFile('archivo_soporte')) {
            $archivo = $request->file('archivo_soporte');
            $nombreOriginal = $archivo->getClientOriginalName();
            $rutaRelativa = 'soportes/' . date('Y/m');
            $nombreArchivo = 'soporte_' . time() . '_' . $archivo->hashName();

            $archivo->storeAs($rutaRelativa, $nombreArchivo, 'public');

            $adjunto = Adjunto::create([
                'ubicacion' => $rutaRelativa . '/' . $nombreArchivo,
                'nombre_original' => $nombreOriginal,
                'tipo_mime' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
            ]);
            $idAdjunto = $adjunto->id_adjunto;
        }

        // Determinar entrevistas a procesar
        $entrevistasAProcesar = [];

        if ($request->filled('codigos_entrevista')) {
            // Procesar múltiples códigos (separados por coma, espacio o salto de línea)
            $codigos = preg_split('/[\s,]+/', $request->codigos_entrevista, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($codigos as $codigo) {
                $codigo = trim(strtoupper($codigo));
                if (empty($codigo)) continue;

                $entrevista = Entrevista::where('entrevista_codigo', $codigo)
                    ->where('id_activo', 1)
                    ->first();

                if ($entrevista) {
                    $entrevistasAProcesar[] = $entrevista;
                }
            }

            if (empty($entrevistasAProcesar)) {
                return redirect()->back()->withInput()->withErrors(['codigos_entrevista' => 'No se encontró ninguna entrevista válida con los códigos proporcionados.']);
            }
        } else {
            $entrevista = Entrevista::find($request->id_e_ind_fvt);
            if (!$entrevista) {
                return redirect()->back()->withInput()->withErrors(['id_e_ind_fvt' => 'La entrevista seleccionada no existe.']);
            }
            $entrevistasAProcesar[] = $entrevista;
        }

        $permisosCreados = 0;
        $permisosExistentes = 0;

        DB::beginTransaction();
        try {
            foreach ($entrevistasAProcesar as $entrevista) {
                // Verificar si ya existe un permiso vigente para esta entrevista/entrevistador
                $existente = Permiso::where('id_entrevistador', $request->id_entrevistador)
                    ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
                    ->where('id_estado', Permiso::ESTADO_VIGENTE)
                    ->first();

                if ($existente) {
                    $existente->update([
                        'id_tipo'          => $request->id_tipo,
                        'fecha_otorgado'   => now(),
                        'fecha_vencimiento'=> $request->fecha_vencimiento ?: null,
                        'fecha_desde'      => $request->fecha_desde ?: null,
                        'fecha_hasta'      => $this->finDeDia($request->fecha_hasta ?: null),
                        'justificacion'    => $request->justificacion,
                        'id_otorgado_por'  => $user->id_entrevistador,
                        'es_solicitud'     => false,
                        'estado_solicitud' => null,
                    ]);
                    $permiso = $existente;
                    $permisosCreados++;
                } else {
                $permiso = Permiso::create([
                    'id_entrevistador' => $request->id_entrevistador,
                    'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                    'codigo_entrevista' => $entrevista->entrevista_codigo,
                    'id_tipo' => $request->id_tipo,
                    'fecha_otorgado' => now(),
                    'fecha_vencimiento' => $request->fecha_vencimiento ?: null,
                    'fecha_desde' => $request->fecha_desde ?: null,
                    'fecha_hasta' => $this->finDeDia($request->fecha_hasta ?: null),
                    'justificacion' => $request->justificacion,
                    'id_otorgado_por' => $user->id_entrevistador,
                    'id_adjunto' => $idAdjunto,
                    'id_estado' => Permiso::ESTADO_VIGENTE,
                    'es_solicitud' => false,
                ]);
                $permisosCreados++;
                } // end else (nuevo permiso)

                // Registrar traza
                TrazaActividad::create([
                    'id_usuario' => $user->id,
                    'accion' => 'otorgar_permiso',
                    'objeto' => 'permiso',
                    'id_registro' => $permiso->id_permiso,
                    'codigo' => $entrevista->entrevista_codigo,
                    'referencia' => $entrevistador->rel_usuario->name ?? '',
                    'ip' => $request->ip(),
                ]);
            }

            DB::commit();

            $mensaje = "Se otorgaron {$permisosCreados} permiso(s) exitosamente.";
            if ($permisosExistentes > 0) {
                $mensaje .= " {$permisosExistentes} permiso(s) ya existían y fueron omitidos.";
            }

            flash($mensaje)->success();
            return redirect()->route('permisos.index');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => 'Error al crear permisos: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver detalle del permiso
     */
    public function show($id)
    {
        $permiso = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_entrevista',
            'rel_otorgado_por.rel_usuario',
            'rel_revocado_por.rel_usuario',
            'rel_adjunto'
        ])->findOrFail($id);

        return view('permisos.show', compact('permiso'));
    }

    /**
     * Revocar permiso (ahora marca como revocado en lugar de eliminar)
     */
    public function destroy(Request $request, $id)
    {
        $permiso = Permiso::findOrFail($id);
        $user = Auth::user();

        $permiso->revocar($user->id_entrevistador);

        if ($motivo = trim($request->input('motivo_rechazo', ''))) {
            $permiso->motivo_rechazo = $motivo;
            $permiso->save();
        }

        // Registrar traza
        TrazaActividad::create([
            'id_usuario' => $user->id,
            'accion' => 'revocar_permiso',
            'objeto' => 'permiso',
            'id_registro' => $id,
            'codigo' => $permiso->codigo_entrevista ?? null,
            'referencia' => $permiso->rel_entrevistador->rel_usuario->name ?? ($permiso->id_e_ind_fvt ?? ''),
            'ip' => $request->ip(),
        ]);

        flash('Permiso revocado exitosamente.')->success();
        return redirect()->route('permisos.index');
    }

    /**
     * Ver permisos de una entrevista especifica
     */
    public function porEntrevista($id)
    {
        $entrevista = Entrevista::findOrFail($id);

        $permisos = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_otorgado_por.rel_usuario',
            'rel_revocado_por.rel_usuario',
            'rel_adjunto'
        ])
            ->where('id_e_ind_fvt', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Seleccione --', '');

        return view('permisos.por_entrevista', compact('entrevista', 'permisos', 'entrevistadores'));
    }

    /**
     * Ver permisos de un usuario especifico
     */
    public function porUsuario($id)
    {
        $entrevistador = Entrevistador::with('rel_usuario')->findOrFail($id);

        $permisos = Permiso::with([
            'rel_entrevista',
            'rel_otorgado_por.rel_usuario',
            'rel_revocado_por.rel_usuario',
            'rel_adjunto'
        ])
            ->where('id_entrevistador', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('permisos.por_usuario', compact('entrevistador', 'permisos'));
    }

    /**
     * Vista de desclasificación - Formulario para otorgar acceso con soporte
     */
    public function desclasificar(Request $request)
    {
        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Seleccione --', '');

        $user = Auth::user();

        // Historial de permisos otorgados hoy por el usuario actual
        $historialHoy = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_entrevista'
        ])
            ->where('id_otorgado_por', $user->id_entrevistador)
            ->whereDate('fecha_otorgado', today())
            ->orderBy('created_at', 'desc')
            ->get();

        // Pre-seleccionar entrevistador si viene por parametro
        $id_autorizado_preselect = $request->get('autorizado');

        return view('permisos.desclasificar', compact('entrevistadores', 'historialHoy', 'id_autorizado_preselect'));
    }

    /**
     * Guardar desclasificación (permiso con soporte documental)
     */
    public function storeDesclasificacion(Request $request)
    {
        $request->validate([
            'id_entrevistador' => 'required|integer',
            'codigos_entrevista' => 'required|string',
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'justificacion' => 'required|string|max:500',
            'archivo_soporte' => 'required|file|mimes:pdf|max:10240',
        ], [
            'archivo_soporte.required' => 'El documento de soporte es obligatorio para desclasificación.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
        ]);

        $entrevistador = Entrevistador::find($request->id_entrevistador);
        if (!$entrevistador) {
            return redirect()->back()->withInput()->withErrors(['id_entrevistador' => 'El entrevistador seleccionado no existe.']);
        }

        $user = Auth::user();

        // Procesar archivo de soporte
        $archivo = $request->file('archivo_soporte');
        $nombreOriginal = $archivo->getClientOriginalName();
        $rutaRelativa = 'soportes/' . date('Y/m');
        $nombreArchivo = 'desclasificacion_' . time() . '_' . $archivo->hashName();

        $archivo->storeAs($rutaRelativa, $nombreArchivo, 'public');

        $adjunto = Adjunto::create([
            'ubicacion' => $rutaRelativa . '/' . $nombreArchivo,
            'nombre_original' => $nombreOriginal,
            'tipo_mime' => $archivo->getMimeType(),
            'tamano' => $archivo->getSize(),
        ]);

        // Procesar códigos de entrevista
        $codigos = preg_split('/[\s,]+/', $request->codigos_entrevista, -1, PREG_SPLIT_NO_EMPTY);
        $entrevistasAProcesar = [];
        $codigosNoEncontrados = [];

        foreach ($codigos as $codigo) {
            $codigo = trim(strtoupper($codigo));
            if (empty($codigo)) continue;

            $entrevista = Entrevista::where('entrevista_codigo', $codigo)
                ->where('id_activo', 1)
                ->first();

            if ($entrevista) {
                $entrevistasAProcesar[] = $entrevista;
            } else {
                $codigosNoEncontrados[] = $codigo;
            }
        }

        if (empty($entrevistasAProcesar)) {
            return redirect()->back()->withInput()->withErrors(['codigos_entrevista' => 'No se encontró ninguna entrevista válida con los códigos proporcionados.']);
        }

        $permisosCreados = 0;
        $permisosExistentes = 0;

        DB::beginTransaction();
        try {
            foreach ($entrevistasAProcesar as $entrevista) {
                // Verificar si ya existe un permiso vigente igual
                $existente = Permiso::where('id_entrevistador', $request->id_entrevistador)
                    ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
                    ->where('id_estado', Permiso::ESTADO_VIGENTE)
                    ->first();

                if ($existente) {
                    $permisosExistentes++;
                    continue;
                }

                $permiso = Permiso::create([
                    'id_entrevistador' => $request->id_entrevistador,
                    'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                    'codigo_entrevista' => $entrevista->entrevista_codigo,
                    'id_tipo' => Permiso::TIPO_LECTURA,
                    'fecha_otorgado' => now(),
                    'fecha_desde' => $request->fecha_desde,
                    'fecha_hasta' => $this->finDeDia($request->fecha_hasta),
                    'justificacion' => $request->justificacion,
                    'id_otorgado_por' => $user->id_entrevistador,
                    'id_adjunto' => $adjunto->id_adjunto,
                    'id_estado' => Permiso::ESTADO_VIGENTE,
                ]);

                // Registrar traza
                TrazaActividad::create([
                    'id_usuario' => $user->id,
                    'accion' => 'desclasificar',
                    'objeto' => 'permiso',
                    'id_registro' => $permiso->id_permiso,
                    'codigo' => $entrevista->entrevista_codigo,
                    'referencia' => $entrevistador->rel_usuario->name ?? '',
                    'ip' => $request->ip(),
                ]);

                $permisosCreados++;
            }

            DB::commit();

            $mensaje = "Se otorgaron {$permisosCreados} acceso(s) por desclasificación.";
            if ($permisosExistentes > 0) {
                $mensaje .= " {$permisosExistentes} ya existían.";
            }
            if (!empty($codigosNoEncontrados)) {
                $mensaje .= " Códigos no encontrados: " . implode(', ', $codigosNoEncontrados);
            }

            flash($mensaje)->success();
            return redirect()->route('permisos.desclasificar');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['error' => 'Error al crear permisos: ' . $e->getMessage()]);
        }
    }

    /**
     * Vista consolidada de accesos otorgados
     */
    public function accesosOtorgados(Request $request)
    {
        $query = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_entrevista',
            'rel_otorgado_por.rel_usuario',
            'rel_adjunto'
        ])->where('id_estado', Permiso::ESTADO_VIGENTE);

        // Filtros
        if ($request->filled('id_entrevistador')) {
            $query->where('id_entrevistador', $request->id_entrevistador);
        }

        if ($request->filled('codigo')) {
            $query->porCodigo($request->codigo);
        }

        if ($request->filled('vigencia')) {
            if ($request->vigencia == 'vigente') {
                $query->vigentes();
            } elseif ($request->vigencia == 'vencido') {
                $query->where(function($q) {
                    $q->where('fecha_hasta', '<', now())
                      ->orWhere('fecha_vencimiento', '<', now());
                });
            }
        }

        if ($request->filled('con_soporte')) {
            if ($request->con_soporte == '1') {
                $query->whereNotNull('id_adjunto');
            } else {
                $query->whereNull('id_adjunto');
            }
        }

        $permisos = $query->orderBy('fecha_otorgado', 'desc')->paginate(25);

        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Todos --', '');

        // Estadísticas
        $stats = [
            'total_vigentes' => Permiso::vigentes()->count(),
            'total_revocados' => Permiso::revocados()->count(),
            'con_soporte' => Permiso::where('id_estado', Permiso::ESTADO_VIGENTE)->whereNotNull('id_adjunto')->count(),
            'otorgados_hoy' => Permiso::whereDate('fecha_otorgado', today())->count(),
        ];

        return view('permisos.accesos_otorgados', compact('permisos', 'entrevistadores', 'stats'));
    }

    /**
     * Descargar soporte de permiso
     */
    public function descargarSoporte($id)
    {
        $permiso = Permiso::with('rel_adjunto')->findOrFail($id);

        if (!$permiso->rel_adjunto) {
            abort(404, 'Este permiso no tiene archivo de soporte.');
        }

        $ruta = storage_path('app/public/' . $permiso->rel_adjunto->ubicacion);

        if (!file_exists($ruta)) {
            abort(404, 'El archivo no existe.');
        }

        return response()->download($ruta, $permiso->rel_adjunto->nombre_original);
    }

    /**
     * Solicitar permiso (Entrevistador o Gestor)
     */
    public function solicitar(Request $request)
    {
        $user = Auth::user();
        $entrevistador = Entrevistador::where('id_usuario', $user->id)->first();

        if (!$entrevistador) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => 'No tiene perfil de entrevistador.'], 403);
            }
            flash('No tiene perfil de entrevistador asignado.')->error();
            return redirect()->back();
        }

        $request->validate([
            'id_e_ind_fvt' => 'required|integer',
            'tipo_solicitud' => 'required|in:acceso,edicion,eliminacion',
            'justificacion' => 'nullable|string|max:1000',
        ], [
            'tipo_solicitud.required' => 'Debe especificar el tipo de solicitud.',
        ]);

        // Eliminacion requires justificacion
        if ($request->tipo_solicitud === 'eliminacion' && empty(trim($request->justificacion ?? ''))) {
            flash('Debe proporcionar una justificación para solicitar la eliminación.')->error();
            return redirect()->back();
        }

        // Limitar solicitudes pendientes simultáneas para evitar enumeración masiva
        $pendientes = Permiso::where('id_entrevistador', $entrevistador->id_entrevistador)
            ->where('es_solicitud', true)
            ->where('estado_solicitud', Permiso::SOLICITUD_PENDIENTE)
            ->count();

        if ($pendientes >= 10) {
            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['error' => 'Ha alcanzado el límite de solicitudes pendientes.'], 429);
            }
            flash('Tiene demasiadas solicitudes pendientes. Espere a que sean procesadas antes de enviar nuevas.')->warning();
            return redirect()->back();
        }

        $entrevista = Entrevista::findOrFail($request->id_e_ind_fvt);

        // Check if already has a pending or approved solicitud of same type
        $existente = Permiso::where('id_entrevistador', $entrevistador->id_entrevistador)
            ->where('id_e_ind_fvt', $request->id_e_ind_fvt)
            ->where('es_solicitud', true)
            ->where('tipo_solicitud', $request->tipo_solicitud)
            ->whereIn('estado_solicitud', [Permiso::SOLICITUD_PENDIENTE, Permiso::SOLICITUD_APROBADA])
            ->first();

        if ($existente) {
            flash('Ya tiene una solicitud pendiente o aprobada de este tipo para esta entrevista.')->warning();
            return redirect()->back();
        }

        $idTipo = $request->tipo_solicitud === 'edicion' ? Permiso::TIPO_ESCRITURA : Permiso::TIPO_LECTURA;

        $permiso = Permiso::create([
            'id_entrevistador' => $entrevistador->id_entrevistador,
            'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
            'codigo_entrevista' => $entrevista->entrevista_codigo,
            'id_tipo' => $idTipo,
            'es_solicitud' => true,
            'tipo_solicitud' => $request->tipo_solicitud,
            'estado_solicitud' => Permiso::SOLICITUD_PENDIENTE,
            'fecha_solicitud' => now(),
            'justificacion' => $request->justificacion ?? ('Solicitud de ' . $request->tipo_solicitud),
            'id_estado' => Permiso::ESTADO_VIGENTE,
        ]);

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'solicitar_permiso',
            'objeto' => 'permiso',
            'id_registro' => $permiso->id_permiso,
            'codigo' => $entrevista->entrevista_codigo,
            'referencia' => $entrevista->entrevista_codigo . ' (' . $request->tipo_solicitud . ')',
            'ip' => $request->ip(),
        ]);

        flash('Solicitud enviada correctamente.')->success();
        return redirect()->back();
    }

    /**
     * Aprobar solicitud (Admin o Gestor de misma dependencia)
     */
    public function aprobar(Request $request, $id)
    {
        $user = Auth::user();
        $permiso = Permiso::with(['rel_entrevista', 'rel_entrevistador'])->findOrFail($id);

        if ($permiso->estado_solicitud !== Permiso::SOLICITUD_PENDIENTE) {
            flash('Esta solicitud ya fue procesada.')->warning();
            return redirect()->route('permisos.index');
        }

        // Eliminaciones: solo quien puede eliminar en el módulo permisos
        if ($permiso->tipo_solicitud === Permiso::SOLICITUD_ELIMINACION &&
            !RolModuloPermiso::puedeEliminar($user->id_nivel, 'permisos')) {
            flash('No tiene permisos para aprobar solicitudes de eliminación.')->error();
            return redirect()->route('permisos.index');
        }

        if (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
            RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            // Alcance total: puede aprobar cualquier solicitud
        } elseif (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
                  RolModuloPermiso::alcanceDependencia($user->id_nivel, 'permisos')) {
            $gestorEntrevistador = Entrevistador::where('id_usuario', $user->id)->first();
            if (!$gestorEntrevistador || !$permiso->rel_entrevista ||
                $permiso->rel_entrevista->id_dependencia_origen != $gestorEntrevistador->id_dependencia_origen) {
                flash('No tiene permisos para aprobar esta solicitud.')->error();
                return redirect()->route('permisos.index');
            }
            // Gestor de conocimiento (nivel 5): solo puede aprobar solicitudes de lectura
            if ($user->id_nivel == 5 && $permiso->id_tipo != Permiso::TIPO_LECTURA) {
                flash('El gestor de conocimiento solo puede aprobar solicitudes de permiso de lectura.')->error();
                return redirect()->route('permisos.index');
            }
        } else {
            flash('No tiene permisos para aprobar solicitudes.')->error();
            return redirect()->route('permisos.index');
        }

        DB::beginTransaction();
        try {
            $permiso->estado_solicitud = Permiso::SOLICITUD_APROBADA;
            $permiso->fecha_otorgado = now();
            $permiso->fecha_respuesta = now();
            $permiso->id_respondido_por = $user->id;
            $permiso->id_otorgado_por = $user->id_entrevistador ?: null;
            $permiso->id_estado = Permiso::ESTADO_VIGENTE;
            $permiso->fecha_desde = $request->fecha_desde ?: null;
            $permiso->fecha_hasta = $this->finDeDia($request->fecha_hasta ?: null);
            $permiso->save();

            // Si es solicitud de eliminación, hacer soft delete de la entrevista
            if ($permiso->tipo_solicitud === Permiso::SOLICITUD_ELIMINACION && $permiso->rel_entrevista) {
                $permiso->rel_entrevista->update(['id_activo' => 0]);

                TrazaActividad::create([
                    'fecha_hora' => now(),
                    'id_usuario' => $user->id,
                    'accion' => 'eliminar',
                    'objeto' => 'entrevista',
                    'id_registro' => $permiso->id_e_ind_fvt,
                    'codigo' => $permiso->codigo_entrevista,
                    'referencia' => 'Eliminación aprobada: ' . $permiso->codigo_entrevista,
                    'ip' => $request->ip(),
                ]);
            }

            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => $user->id,
                'accion' => 'aprobar_solicitud',
                'objeto' => 'permiso',
                'id_registro' => $permiso->id_permiso,
                'codigo' => $permiso->codigo_entrevista,
                'referencia' => $permiso->codigo_entrevista . ' (' . $permiso->tipo_solicitud . ')',
                'ip' => $request->ip(),
            ]);

            DB::commit();
            flash('Solicitud aprobada correctamente.')->success();
        } catch (\Exception $e) {
            DB::rollBack();
            flash('Error al aprobar la solicitud: ' . $e->getMessage())->error();
        }

        return redirect()->route('permisos.index');
    }

    /**
     * Rechazar solicitud (Admin o Gestor de misma dependencia)
     */
    public function rechazar(Request $request, $id)
    {
        $user = Auth::user();
        $permiso = Permiso::with(['rel_entrevista'])->findOrFail($id);

        if ($permiso->estado_solicitud !== Permiso::SOLICITUD_PENDIENTE) {
            flash('Esta solicitud ya fue procesada.')->warning();
            return redirect()->route('permisos.index');
        }

        if ($permiso->tipo_solicitud === Permiso::SOLICITUD_ELIMINACION &&
            !RolModuloPermiso::puedeEliminar($user->id_nivel, 'permisos')) {
            flash('No tiene permisos para procesar solicitudes de eliminación.')->error();
            return redirect()->route('permisos.index');
        }

        if (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
            RolModuloPermiso::alcanceTodas($user->id_nivel, 'permisos')) {
            // ok
        } elseif (RolModuloPermiso::puedeEditar($user->id_nivel, 'permisos') &&
                  RolModuloPermiso::alcanceDependencia($user->id_nivel, 'permisos')) {
            $gestorEntrevistador = Entrevistador::where('id_usuario', $user->id)->first();
            if (!$gestorEntrevistador || !$permiso->rel_entrevista ||
                $permiso->rel_entrevista->id_dependencia_origen != $gestorEntrevistador->id_dependencia_origen) {
                flash('No tiene permisos para rechazar esta solicitud.')->error();
                return redirect()->route('permisos.index');
            }
        } else {
            flash('No tiene permisos para rechazar solicitudes.')->error();
            return redirect()->route('permisos.index');
        }

        $request->validate([
            'motivo_rechazo' => 'nullable|string|max:500',
        ]);

        $permiso->estado_solicitud = Permiso::SOLICITUD_RECHAZADA;
        $permiso->fecha_respuesta = now();
        $permiso->id_respondido_por = $user->id;
        $permiso->id_estado = Permiso::ESTADO_REVOCADO;
        $permiso->motivo_rechazo = $request->input('motivo_rechazo');
        $permiso->save();

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'rechazar_solicitud',
            'objeto' => 'permiso',
            'id_registro' => $permiso->id_permiso,
            'codigo' => $permiso->codigo_entrevista,
            'referencia' => $permiso->codigo_entrevista . ' (' . $permiso->tipo_solicitud . ')',
            'ip' => $request->ip(),
        ]);

        flash('Solicitud rechazada.')->warning();
        return redirect()->route('permisos.index');
    }
}
