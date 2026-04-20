<?php

namespace App\Http\Controllers;

use App\Models\TrazaActividad;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            // Gestor: ve su propia actividad + actividad relacionada con entrevistas de su dependencia
            $entrevistadorGestor = Entrevistador::where('id_usuario', $user->id)->first();
            if ($entrevistadorGestor && $entrevistadorGestor->id_dependencia_origen) {
                $codigosDependencia = Entrevista::where('id_dependencia_origen', $entrevistadorGestor->id_dependencia_origen)
                    ->where('id_activo', 1)
                    ->pluck('entrevista_codigo');
                $query->where(function($q) use ($user, $codigosDependencia) {
                    $q->where('id_usuario', $user->id)
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
     * Exportar traza a Excel
     */
    public function exportar(Request $request)
    {
        return redirect()->route('traza.index')
            ->with('info', 'Funcionalidad de exportacion en desarrollo.');
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
