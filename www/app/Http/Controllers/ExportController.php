<?php

namespace App\Http\Controllers;

use App\Exports\EntrevistasExport;
use App\Exports\PersonasExport;
use App\Models\Entrevistador;
use App\Models\CatItem;
use App\Models\Geo;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista de exportación
     */
    public function index()
    {
        $territorios = Geo::where('nivel', 2)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_geo')
            ->prepend('-- Todos --', '');

        $entrevistadores = Entrevistador::with('rel_usuario')
            ->orderBy('numero_entrevistador')
            ->get()
            ->pluck('rel_usuario.name', 'id_entrevistador')
            ->prepend('-- Todos --', '');

        $sexos = CatItem::where('id_cat', 1)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        $etnias = CatItem::where('id_cat', 3)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        // Catálogos adicionales para filtros
        $dependencias = CatItem::where('id_cat', 4)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todas --', '');

        $tipos_testimonio = CatItem::where('id_cat', 5)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        $tipos_adjunto = CatItem::where('id_cat', 6)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        return view('exportar.index', compact(
            'territorios',
            'entrevistadores',
            'sexos',
            'etnias',
            'dependencias',
            'tipos_testimonio',
            'tipos_adjunto'
        ));
    }

    /**
     * Exportar entrevistas a Excel
     */
    public function entrevistas(Request $request)
    {
        $user = Auth::user();

        $filtros = [
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'id_territorio' => $request->id_territorio,
            'id_entrevistador' => $request->id_entrevistador,
            'id_dependencia_origen' => $request->id_dependencia_origen,
            'id_tipo_testimonio' => $request->id_tipo_testimonio,
            'tiene_adjuntos' => $request->tiene_adjuntos,
            'id_tipo_adjunto' => $request->id_tipo_adjunto,
        ];

        // Registrar traza
        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'exportar_entrevistas',
            'objeto' => 'entrevista',
            'referencia' => 'Exportacion de entrevistas a Excel',
            'ip' => $request->ip(),
        ]);

        $nombre = 'entrevistas_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new EntrevistasExport($filtros), $nombre);
    }

    /**
     * Exportar personas a Excel
     */
    public function personas(Request $request)
    {
        $user = Auth::user();

        $filtros = [
            'id_sexo' => $request->id_sexo,
            'id_etnia' => $request->id_etnia,
            'id_lugar_residencia_depto' => $request->id_lugar_residencia_depto,
        ];

        // Registrar traza
        TrazaActividad::create([
            'fecha_hora' => now(),
            'id_usuario' => $user->id,
            'accion' => 'exportar_personas',
            'objeto' => 'persona',
            'referencia' => 'Exportacion de personas a Excel',
            'ip' => $request->ip(),
        ]);

        $nombre = 'personas_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new PersonasExport($filtros), $nombre);
    }
}
