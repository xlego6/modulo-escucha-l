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

        // =============================================
        // Contenido del Testimonio (Paso 3)
        // =============================================

        $hechos_victimizantes = DB::table('esclarecimiento.contenido_hecho_victimizante as chv')
            ->join('catalogos.cat_item as ci', 'chv.id_hecho', '=', 'ci.id_item')
            ->select('ci.descripcion as hecho', DB::raw('COUNT(*) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
            ->get();

        $practicas_resistencia = DB::table('esclarecimiento.contenido_practica_resistencia as cpr')
            ->join('catalogos.cat_item as ci', 'cpr.id_practica', '=', 'ci.id_item')
            ->select('ci.descripcion as practica', DB::raw('COUNT(*) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
            ->get();

        // Rango de años de hechos (min y max para la barra de rango)
        $rango_fechas_hechos = DB::table('esclarecimiento.contenido_testimonio')
            ->whereNotNull('fecha_hechos_inicial')
            ->selectRaw("
                MIN(EXTRACT(YEAR FROM fecha_hechos_inicial))::integer AS min_year,
                MAX(EXTRACT(YEAR FROM COALESCE(fecha_hechos_final, fecha_hechos_inicial)))::integer AS max_year
            ")
            ->first();

        // Histograma por década (1900-2020)
        $decadas_raw = DB::table('esclarecimiento.contenido_testimonio')
            ->whereNotNull('fecha_hechos_inicial')
            ->whereRaw("EXTRACT(YEAR FROM fecha_hechos_inicial) BETWEEN 1900 AND 2030")
            ->selectRaw("(FLOOR(EXTRACT(YEAR FROM fecha_hechos_inicial) / 10) * 10)::integer AS decada, COUNT(*) AS total")
            ->groupByRaw("FLOOR(EXTRACT(YEAR FROM fecha_hechos_inicial) / 10) * 10")
            ->orderBy('decada')
            ->pluck('total', 'decada')
            ->toArray();

        // Rellenar décadas sin datos con 0
        $fechas_decadas = [];
        for ($d = 1900; $d <= 2020; $d += 10) {
            $fechas_decadas[$d] = $decadas_raw[$d] ?? 0;
        }

        // =============================================
        // Testimoniantes (Paso 2)
        // =============================================

        $poblaciones_testimoniantes = DB::table('fichas.persona_poblacion as pp')
            ->join('fichas.persona_entrevistada as pe', 'pp.id_persona', '=', 'pe.id_persona')
            ->join('catalogos.cat_item as ci', 'pp.id_poblacion', '=', 'ci.id_item')
            ->select('ci.descripcion as poblacion', DB::raw('COUNT(DISTINCT pe.id_persona) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
            ->get();

        $rangos_etarios = DB::table('fichas.persona as p')
            ->join('fichas.persona_entrevistada as pe', 'p.id_persona', '=', 'pe.id_persona')
            ->join('catalogos.cat_item as ci', 'p.id_rango_etario', '=', 'ci.id_item')
            ->whereNotNull('p.id_rango_etario')
            ->select('ci.descripcion as rango', DB::raw('COUNT(DISTINCT p.id_persona) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
            ->get();

        $sexo_testimoniantes = DB::table('fichas.persona as p')
            ->join('fichas.persona_entrevistada as pe', 'p.id_persona', '=', 'pe.id_persona')
            ->join('catalogos.cat_item as ci', 'p.id_sexo', '=', 'ci.id_item')
            ->whereNotNull('p.id_sexo')
            ->select('ci.descripcion as sexo', DB::raw('COUNT(DISTINCT p.id_persona) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
            ->get();

        // =============================================
        // Clasificación (Prueba de Daño)
        // =============================================

        // SQL compartido para calcular nivel de clasificación por consentimiento
        $casoNivel = "
            CASE
                WHEN prueba_dano_inteligencia = 1 THEN 5
                WHEN (prueba_dano_inteligencia IS NULL OR prueba_dano_inteligencia = 2)
                     AND (prueba_dano_derechos_privados IS NULL OR prueba_dano_derechos_privados = 2)
                     AND (prueba_dano_intereses_publicos IS NULL OR prueba_dano_intereses_publicos = 2)
                     AND (prueba_dano_nna IS NULL OR prueba_dano_nna = 2) THEN -1
                WHEN prueba_dano_derechos_privados = 1
                     AND (prueba_dano_intereses_publicos = 1 OR prueba_dano_nna = 1) THEN 4
                WHEN prueba_dano_derechos_privados = 1 THEN 3
                WHEN prueba_dano_intereses_publicos = 1 OR prueba_dano_nna = 1 THEN 2
                ELSE 1
            END
        ";
        $casoLabel = "
            CASE nivel
                WHEN 5  THEN 'Inteligencia y Contrainteligencia'
                WHEN 4  THEN 'Pública-Clasificada y Reservada'
                WHEN 3  THEN 'Pública-Clasificada'
                WHEN 2  THEN 'Pública-Reservada'
                WHEN 1  THEN 'Pública-Pública'
                ELSE        'Pendiente de Calificación'
            END AS clasificacion
        ";

        // Por entrevista: la clasificación más restrictiva de sus consentimientos
        $clasif_por_entrevista = DB::select("
            WITH niveles AS (
                SELECT pe.id_e_ind_fvt, ({$casoNivel}) AS nivel
                FROM fichas.persona_entrevistada pe
                JOIN fichas.consentimiento_informado ci ON ci.id_persona_entrevistada = pe.id_persona_entrevistada
                WHERE pe.id_e_ind_fvt IS NOT NULL
            ),
            max_nivel AS (
                SELECT id_e_ind_fvt, MAX(nivel) AS nivel
                FROM niveles GROUP BY id_e_ind_fvt
            )
            SELECT {$casoLabel}, nivel, COUNT(*) AS total
            FROM max_nivel GROUP BY nivel ORDER BY nivel DESC
        ");

        // Por consentimiento individual
        $clasif_por_consentimiento = DB::select("
            WITH base AS (
                SELECT ({$casoNivel}) AS nivel
                FROM fichas.consentimiento_informado ci
            )
            SELECT {$casoLabel}, nivel, COUNT(*) AS total
            FROM base GROUP BY nivel ORDER BY nivel DESC
        ");

        // =============================================
        // Dependencia
        // =============================================

        $entrevistas_por_dependencia = DB::table('esclarecimiento.e_ind_fvt as e')
            ->join('catalogos.cat_item as ci', 'e.id_dependencia_origen', '=', 'ci.id_item')
            ->where('e.id_activo', 1)
            ->whereNotNull('e.id_dependencia_origen')
            ->select('ci.descripcion as dependencia', DB::raw('COUNT(*) as total'))
            ->groupBy('ci.descripcion')
            ->orderByDesc('total')
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
            'top_entrevistadores',
            // Paso 3
            'hechos_victimizantes',
            'practicas_resistencia',
            'rango_fechas_hechos',
            'fechas_decadas',
            // Paso 2
            'poblaciones_testimoniantes',
            'rangos_etarios',
            'sexo_testimoniantes',
            // Clasificación y dependencia
            'clasif_por_entrevista',
            'clasif_por_consentimiento',
            'entrevistas_por_dependencia'
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
