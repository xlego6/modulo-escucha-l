<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Adjunto;
use App\Models\TrazaActividad;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de permisos otorgados
     */
    public function index(Request $request)
    {
        $query = Permiso::with([
            'rel_entrevistador.rel_usuario',
            'rel_entrevista',
            'rel_otorgado_por.rel_usuario',
            'rel_adjunto'
        ]);

        if ($request->filled('id_entrevistador')) {
            $query->where('id_entrevistador', $request->id_entrevistador);
        }

        if ($request->filled('id_e_ind_fvt')) {
            $query->where('id_e_ind_fvt', $request->id_e_ind_fvt);
        }

        if ($request->filled('codigo')) {
            $query->porCodigo($request->codigo);
        }

        if ($request->filled('estado')) {
            if ($request->estado == '1') {
                $query->vigentes();
            } elseif ($request->estado == '2') {
                $query->revocados();
            }
        }

        if ($request->filled('tipo')) {
            $query->where('id_tipo', $request->tipo);
        }

        $permisos = $query->orderBy('created_at', 'desc')->paginate(20);

        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Todos --', '');

        $tipos = [
            '' => '-- Todos --',
            1 => 'Lectura',
            2 => 'Escritura',
            3 => 'Completo',
        ];

        return view('permisos.index', compact('permisos', 'entrevistadores', 'tipos'));
    }

    /**
     * Formulario para otorgar permiso
     */
    public function create(Request $request)
    {
        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Seleccione --', '');

        $entrevistas = Entrevista::where('id_activo', 1)
            ->orderBy('entrevista_codigo')
            ->get()
            ->mapWithKeys(function($e) {
                return [$e->id_e_ind_fvt => "{$e->entrevista_codigo} - {$e->titulo}"];
            })
            ->prepend('-- Seleccione --', '');

        $tipos = [
            '' => '-- Seleccione --',
            1 => 'Lectura',
            2 => 'Escritura',
            3 => 'Completo',
        ];

        // Pre-seleccionar entrevista si viene por parametro
        $id_entrevista_preselect = $request->get('entrevista');

        return view('permisos.create', compact('entrevistadores', 'entrevistas', 'tipos', 'id_entrevista_preselect'));
    }

    /**
     * Guardar nuevo permiso
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_entrevistador' => 'required|integer',
            'id_tipo' => 'required|in:1,2,3',
            'justificacion' => 'required|string|max:500',
            'archivo_soporte' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Puede venir id_e_ind_fvt o codigos_entrevista
        if (!$request->filled('id_e_ind_fvt') && !$request->filled('codigos_entrevista')) {
            return redirect()->back()->withInput()->withErrors(['id_e_ind_fvt' => 'Debe seleccionar una entrevista o ingresar códigos.']);
        }

        $entrevistador = Entrevistador::find($request->id_entrevistador);
        if (!$entrevistador) {
            return redirect()->back()->withInput()->withErrors(['id_entrevistador' => 'El entrevistador seleccionado no existe.']);
        }

        $user = Auth::user();

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
                    'id_tipo' => $request->id_tipo,
                    'fecha_otorgado' => now(),
                    'fecha_vencimiento' => $request->fecha_vencimiento ?: null,
                    'fecha_desde' => $request->fecha_desde ?: null,
                    'fecha_hasta' => $request->fecha_hasta ?: null,
                    'justificacion' => $request->justificacion,
                    'id_otorgado_por' => $user->id_entrevistador,
                    'id_adjunto' => $idAdjunto,
                    'id_estado' => Permiso::ESTADO_VIGENTE,
                ]);

                // Registrar traza
                TrazaActividad::create([
                    'id_usuario' => $user->id,
                    'accion' => 'otorgar_permiso',
                    'objeto' => 'permiso',
                    'id_registro' => $permiso->id_permiso,
                    'referencia' => $entrevista->entrevista_codigo,
                    'codigo' => $entrevistador->rel_usuario->name ?? '',
                    'ip' => $request->ip(),
                ]);

                $permisosCreados++;
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

        // Revocar en lugar de eliminar
        $permiso->revocar($user->id_entrevistador);

        // Registrar traza
        TrazaActividad::create([
            'id_usuario' => $user->id,
            'accion' => 'revocar_permiso',
            'objeto' => 'permiso',
            'id_registro' => $id,
            'referencia' => $permiso->codigo_entrevista ?? $permiso->id_e_ind_fvt,
            'codigo' => $permiso->rel_entrevistador->rel_usuario->name ?? '',
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
                    'fecha_hasta' => $request->fecha_hasta,
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
                    'referencia' => $entrevista->entrevista_codigo,
                    'codigo' => $entrevistador->rel_usuario->name ?? '',
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
}
