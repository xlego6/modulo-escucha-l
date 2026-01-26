<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Persona;
use App\Models\Adjunto;
use App\Models\Entrevistador;
use App\Models\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstadisticaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard de estadísticas
     */
    public function index()
    {
        // Contadores generales
        $totales = [
            'entrevistas' => Entrevista::where('id_activo', 1)->count(),
            'personas' => Persona::count(),
            'adjuntos' => Adjunto::where('existe_archivo', 1)->count(),
            'entrevistadores' => Entrevistador::count(),
        ];

        // Entrevistas por mes (últimos 12 meses)
        $entrevistas_por_mes = Entrevista::where('id_activo', 1)
            ->where('entrevista_fecha', '>=', now()->subMonths(12))
            ->select(
                DB::raw("TO_CHAR(entrevista_fecha, 'YYYY-MM') as mes"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();

        // Entrevistas por territorio
        $entrevistas_por_territorio = Entrevista::where('id_activo', 1)
            ->whereNotNull('id_territorio')
            ->join('catalogos.geo', 'e_ind_fvt.id_territorio', '=', 'geo.id_geo')
            ->select('geo.descripcion as territorio', DB::raw('COUNT(*) as total'))
            ->groupBy('geo.descripcion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Personas por sexo
        $personas_por_sexo = Persona::whereNotNull('id_sexo')
            ->join('catalogos.cat_item', 'persona.id_sexo', '=', 'cat_item.id_item')
            ->select('cat_item.descripcion as sexo', DB::raw('COUNT(*) as total'))
            ->groupBy('cat_item.descripcion')
            ->get();

        // Personas por grupo étnico
        $personas_por_etnia = Persona::whereNotNull('id_etnia')
            ->join('catalogos.cat_item', 'persona.id_etnia', '=', 'cat_item.id_item')
            ->select('cat_item.descripcion as etnia', DB::raw('COUNT(*) as total'))
            ->groupBy('cat_item.descripcion')
            ->orderByDesc('total')
            ->get();

        // Adjuntos por tipo
        $adjuntos_por_tipo = Adjunto::where('existe_archivo', 1)
            ->whereNotNull('id_tipo')
            ->join('catalogos.cat_item', 'adjunto.id_tipo', '=', 'cat_item.id_item')
            ->select('cat_item.descripcion as tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('cat_item.descripcion')
            ->get();

        // Tamaño total de adjuntos
        $tamano_total_adjuntos = Adjunto::where('existe_archivo', 1)->sum('tamano');

        // Entrevistas recientes
        $entrevistas_recientes = Entrevista::where('id_activo', 1)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Top entrevistadores
        $top_entrevistadores = Entrevista::where('id_activo', 1)
            ->join('esclarecimiento.entrevistador', 'e_ind_fvt.id_entrevistador', '=', 'entrevistador.id_entrevistador')
            ->join('users', 'entrevistador.id_usuario', '=', 'users.id')
            ->select('users.name', DB::raw('COUNT(*) as total'))
            ->groupBy('users.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('estadisticas.index', compact(
            'totales',
            'entrevistas_por_mes',
            'entrevistas_por_territorio',
            'personas_por_sexo',
            'personas_por_etnia',
            'adjuntos_por_tipo',
            'tamano_total_adjuntos',
            'entrevistas_recientes',
            'top_entrevistadores'
        ));
    }

    /**
     * Datos para gráficos (AJAX)
     */
    public function datos(Request $request)
    {
        $tipo = $request->get('tipo', 'entrevistas_mes');

        switch ($tipo) {
            case 'entrevistas_mes':
                $data = Entrevista::where('id_activo', 1)
                    ->where('entrevista_fecha', '>=', now()->subMonths(12))
                    ->select(
                        DB::raw("TO_CHAR(entrevista_fecha, 'YYYY-MM') as label"),
                        DB::raw('COUNT(*) as value')
                    )
                    ->groupBy('label')
                    ->orderBy('label')
                    ->get();
                break;

            case 'entrevistas_territorio':
                $data = Entrevista::where('id_activo', 1)
                    ->whereNotNull('id_territorio')
                    ->join('catalogos.geo', 'e_ind_fvt.id_territorio', '=', 'geo.id_geo')
                    ->select('geo.descripcion as label', DB::raw('COUNT(*) as value'))
                    ->groupBy('geo.descripcion')
                    ->orderByDesc('value')
                    ->limit(10)
                    ->get();
                break;

            default:
                $data = [];
        }

        return response()->json($data);
    }
}
