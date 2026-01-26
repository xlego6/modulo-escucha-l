<?php

namespace App\Http\Controllers;

use App\Models\TrazaActividad;
use App\User;
use Illuminate\Http\Request;

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
        $query = TrazaActividad::with(['rel_usuario'])
            ->orderBy('fecha_hora', 'desc');

        // Filtro por usuario
        if ($request->filled('id_usuario')) {
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
        $usuarios = User::orderBy('name')
            ->pluck('name', 'id')
            ->prepend('-- Todos --', '');

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

        return view('traza.index', compact('trazas', 'usuarios', 'acciones', 'objetos'));
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
