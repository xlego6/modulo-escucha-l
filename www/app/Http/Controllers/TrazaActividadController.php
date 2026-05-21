<?php

namespace App\Http\Controllers;

use App\Models\TrazaActividad;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\User;
use App\Exports\TrazaExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class TrazaActividadController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado de traza de actividad
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $esAdmin = in_array($user->id_nivel, [1, 2]);

        $query = TrazaActividad::with(['rel_usuario'])
            ->orderBy('fecha_hora', 'desc');

        // Filtrar según alcance del rol
        if ($user->id_nivel == 5) {
            // Gestor: ve su propia actividad + toda la actividad de los perfiles de su dependencia
            $entrevistadorGestor = Entrevistador::where('id_usuario', $user->id)
                ->orderBy('id_nivel')
                ->first();
            if ($entrevistadorGestor && $entrevistadorGestor->id_dependencia_origen) {
                // IDs de usuario de todos los perfiles de la misma dependencia
                $usuariosDependencia = Entrevistador::where('id_dependencia_origen', $entrevistadorGestor->id_dependencia_origen)
                    ->pluck('id_usuario');
                // Códigos de entrevistas de la dependencia (para capturar actividad de perfiles externos que actúen sobre ellas)
                $codigosDependencia = Entrevista::where('id_dependencia_origen', $entrevistadorGestor->id_dependencia_origen)
                    ->pluck('entrevista_codigo');
                $query->where(function($q) use ($user, $usuariosDependencia, $codigosDependencia) {
                    $q->where('id_usuario', $user->id)
                      ->orWhereIn('id_usuario', $usuariosDependencia)
                      ->orWhereIn('codigo', $codigosDependencia);
                });
            } else {
                $query->where('id_usuario', $user->id);
            }
        } elseif (!$esAdmin) {
            // Otros roles (Entrevistador, Transcriptor): solo su propia actividad
            $query->where('id_usuario', $user->id);
        }

        // Filtro por usuario (solo admins)
        if ($esAdmin && $request->filled('id_usuario')) {
            $query->where('id_usuario', $request->id_usuario);
        }

        // Filtro por accion
        if ($request->filled('accion')) {
            $query->where('accion', $request->accion);
        }

        // Filtro por objeto
        if ($request->filled('objeto')) {
            $query->where('objeto', $request->objeto);
        }

        // Filtro por fecha desde
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
        }

        // Filtro por fecha hasta
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
        }

        // Filtro por codigo/referencia
        if ($request->filled('busqueda')) {
            $busqueda = $request->busqueda;
            $query->where(function ($q) use ($busqueda) {
                $q->where('codigo', 'ilike', "%{$busqueda}%")
                  ->orWhere('referencia', 'ilike', "%{$busqueda}%");
            });
        }

        $trazas = $query->paginate(50)->appends($request->query());

        // Datos para filtros
        $usuarios = collect();
        if ($esAdmin) {
            $usuarios = User::orderBy('name')
                ->pluck('name', 'id')
                ->prepend('-- Todos --', '');
        }

        // Obtener acciones únicas de la BD
        $acciones = TrazaActividad::select('accion')
            ->distinct()
            ->whereNotNull('accion')
            ->orderBy('accion')
            ->pluck('accion', 'accion')
            ->prepend('-- Todas --', '');

        // Obtener objetos únicos de la BD
        $objetos = TrazaActividad::select('objeto')
            ->distinct()
            ->whereNotNull('objeto')
            ->orderBy('objeto')
            ->pluck('objeto', 'objeto')
            ->prepend('-- Todos --', '');

        return view('traza.index', compact('trazas', 'usuarios', 'acciones', 'objetos', 'esAdmin'));
    }

    /**
     * Ver detalle de una traza
     */
    public function show($id)
    {
        $traza = TrazaActividad::with(['rel_usuario'])
            ->findOrFail($id);

        return view('traza.show', compact('traza'));
    }

    /**
     * Exportar traza a Excel (solo administrador, máximo 500 registros)
     */
    public function exportar(Request $request)
    {
        $user = Auth::user();

        if ($user->id_nivel != 1) {
            return redirect()->route('traza.index')
                ->with('error', 'Solo el administrador puede exportar la traza de actividad.');
        }

        // Contar registros con los filtros actuales
        $query = TrazaActividad::query();
        if ($request->filled('id_usuario'))   $query->where('id_usuario', $request->id_usuario);
        if ($request->filled('accion'))        $query->where('accion', $request->accion);
        if ($request->filled('objeto'))        $query->where('objeto', $request->objeto);
        if ($request->filled('fecha_desde'))   $query->whereDate('fecha_hora', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))   $query->whereDate('fecha_hora', '<=', $request->fecha_hasta);
        if ($request->filled('busqueda')) {
            $b = $request->busqueda;
            $query->where(fn($q) => $q->where('codigo', 'ilike', "%$b%")->orWhere('referencia', 'ilike', "%$b%"));
        }

        $total = $query->count();
        $limite = 500;

        if ($total > $limite) {
            return redirect()->route('traza.index', $request->query())
                ->with('warning', "Los filtros aplicados devuelven {$total} registros. Solo se exportarán los {$limite} más recientes. Ajuste los filtros (ej. rango de fechas) para obtener menos registros.");
        }

        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion'     => 'exportar',
            'objeto'     => 'traza_actividad',
            'referencia' => 'Exportacion de traza con filtros: ' . http_build_query($request->only(['id_usuario','accion','objeto','fecha_desde','fecha_hasta','busqueda'])),
            'ip'         => $request->ip(),
        ]);

        $filtros = $request->only(['id_usuario', 'accion', 'objeto', 'fecha_desde', 'fecha_hasta', 'busqueda']);
        $fecha   = now()->format('Y-m-d');

        return Excel::download(new TrazaExport($filtros), "traza-actividad-{$fecha}.xlsx");
    }

    /**
     * Estadisticas de actividad
     */
    public function estadisticas(Request $request)
    {
        $fechaDesde = $request->fecha_desde ?? now()->subDays(30)->format('Y-m-d');
        $fechaHasta = $request->fecha_hasta ?? now()->format('Y-m-d');

        // Actividad por usuario
        $actividadPorUsuario = TrazaActividad::selectRaw('id_usuario, COUNT(*) as total')
            ->whereDate('fecha_hora', '>=', $fechaDesde)
            ->whereDate('fecha_hora', '<=', $fechaHasta)
            ->groupBy('id_usuario')
            ->with('rel_usuario')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Actividad por accion
        $actividadPorAccion = TrazaActividad::selectRaw('accion, COUNT(*) as total')
            ->whereDate('fecha_hora', '>=', $fechaDesde)
            ->whereDate('fecha_hora', '<=', $fechaHasta)
            ->whereNotNull('accion')
            ->groupBy('accion')
            ->orderByDesc('total')
            ->get();

        // Actividad por dia
        $actividadPorDia = TrazaActividad::selectRaw("DATE(fecha_hora) as fecha, COUNT(*) as total")
            ->whereDate('fecha_hora', '>=', $fechaDesde)
            ->whereDate('fecha_hora', '<=', $fechaHasta)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        return view('traza.estadisticas', compact(
            'actividadPorUsuario',
            'actividadPorAccion',
            'actividadPorDia',
            'fechaDesde',
            'fechaHasta'
        ));
    }
}
