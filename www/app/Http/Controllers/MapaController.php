<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MapaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista principal del mapa
     */
    public function index()
    {
        return view('mapa.index');
    }

    /**
     * Datos para el mapa (AJAX)
     * @param string $tipo - Tipo de ubicación: toma, origen, mencionados
     */
    public function datos(Request $request)
    {
        $tipo = $request->input('tipo', 'toma');

        $porDepartamento = [];

        switch ($tipo) {
            case 'toma':
                $porDepartamento = $this->getDatosPorLugarToma();
                break;
            case 'origen':
                $porDepartamento = $this->getDatosPorOrigenTestimoniante();
                break;
            case 'mencionados':
                $porDepartamento = $this->getDatosPorLugaresMencionados();
                break;
            default:
                $porDepartamento = $this->getDatosPorLugarToma();
        }

        // Coordenadas de departamentos
        $coordenadas = $this->getCoordenadasDepartamentos();

        $datos = [];
        foreach ($porDepartamento as $item) {
            $nombreDepto = strtoupper($this->normalizarNombreDepto($item->departamento));
            if (isset($coordenadas[$nombreDepto])) {
                $datos[] = [
                    'id' => $item->id_depto,
                    'nombre' => $item->departamento,
                    'total' => $item->total,
                    'lat' => $coordenadas[$nombreDepto]['lat'],
                    'lng' => $coordenadas[$nombreDepto]['lng'],
                ];
            }
        }

        // Estadísticas generales
        $estadisticas = [
            'total_entrevistas' => Entrevista::where('id_activo', 1)->count(),
            'total_departamentos' => count($datos),
            'max_entrevistas' => collect($datos)->max('total') ?? 0,
        ];

        return response()->json([
            'datos' => $datos,
            'estadisticas' => $estadisticas,
            'tipo' => $tipo,
        ]);
    }

    /**
     * Obtener datos por lugar de toma de entrevista
     */
    private function getDatosPorLugarToma()
    {
        // entrevista_lugar es a nivel municipio, necesitamos obtener el departamento (padre)
        return DB::select("
            SELECT
                depto.id_geo as id_depto,
                depto.descripcion as departamento,
                COUNT(e.id_e_ind_fvt) as total
            FROM esclarecimiento.e_ind_fvt e
            INNER JOIN catalogos.geo muni ON e.entrevista_lugar = muni.id_geo
            INNER JOIN catalogos.geo depto ON muni.id_padre = depto.id_geo AND depto.nivel = 2
            WHERE e.id_activo = 1 AND e.entrevista_lugar IS NOT NULL
            GROUP BY depto.id_geo, depto.descripcion
            ORDER BY total DESC
        ");
    }

    /**
     * Obtener datos por origen del testimoniante (lugar de nacimiento o residencia)
     */
    private function getDatosPorOrigenTestimoniante()
    {
        return DB::select("
            SELECT
                depto.id_geo as id_depto,
                depto.descripcion as departamento,
                COUNT(DISTINCT e.id_e_ind_fvt) as total
            FROM esclarecimiento.e_ind_fvt e
            INNER JOIN fichas.persona_entrevistada pe ON e.id_e_ind_fvt = pe.id_e_ind_fvt
            INNER JOIN fichas.persona p ON pe.id_persona = p.id_persona
            INNER JOIN catalogos.geo depto ON (
                COALESCE(p.id_lugar_nacimiento_depto, p.id_lugar_residencia_depto) = depto.id_geo
            )
            WHERE e.id_activo = 1
                AND depto.nivel = 2
                AND (p.id_lugar_nacimiento_depto IS NOT NULL OR p.id_lugar_residencia_depto IS NOT NULL)
            GROUP BY depto.id_geo, depto.descripcion
            ORDER BY total DESC
        ");
    }

    /**
     * Obtener datos por lugares mencionados en la entrevista
     */
    private function getDatosPorLugaresMencionados()
    {
        return DB::select("
            SELECT
                depto.id_geo as id_depto,
                depto.descripcion as departamento,
                COUNT(DISTINCT cl.id_e_ind_fvt) as total
            FROM esclarecimiento.contenido_lugar cl
            INNER JOIN esclarecimiento.e_ind_fvt e ON cl.id_e_ind_fvt = e.id_e_ind_fvt
            INNER JOIN catalogos.geo depto ON cl.id_departamento = depto.id_geo
            WHERE e.id_activo = 1
                AND depto.nivel = 2
                AND cl.id_departamento IS NOT NULL
            GROUP BY depto.id_geo, depto.descripcion
            ORDER BY total DESC
        ");
    }

    /**
     * Normalizar nombre de departamento para coincidir con coordenadas
     */
    private function normalizarNombreDepto($nombre)
    {
        $nombre = strtoupper(trim($nombre));
        // Quitar tildes
        $nombre = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'N'],
            $nombre
        );
        return $nombre;
    }

    /**
     * Detalle de un departamento (AJAX)
     */
    public function detalleDepartamento(Request $request, $id)
    {
        $departamento = Geo::find($id);
        $tipo = $request->input('tipo', 'toma');

        if (!$departamento) {
            return response()->json(['error' => 'Departamento no encontrado'], 404);
        }

        // Obtener entrevistas según el tipo
        switch ($tipo) {
            case 'toma':
                $entrevistas = $this->getEntrevistasPorLugarToma($id);
                $porMunicipio = $this->getMunicipiosPorLugarToma($id);
                break;
            case 'origen':
                $entrevistas = $this->getEntrevistasPorOrigen($id);
                $porMunicipio = collect(); // No aplica para origen
                break;
            case 'mencionados':
                $entrevistas = $this->getEntrevistasPorMencionados($id);
                $porMunicipio = $this->getMunicipiosMencionados($id);
                break;
            default:
                $entrevistas = collect();
                $porMunicipio = collect();
        }

        return response()->json([
            'departamento' => $departamento->descripcion,
            'total' => $entrevistas->count(),
            'entrevistas' => $entrevistas,
            'municipios' => $porMunicipio,
            'tipo' => $tipo,
        ]);
    }

    private function getEntrevistasPorLugarToma($idDepto)
    {
        return Entrevista::where('id_activo', 1)
            ->whereHas('rel_lugar_entrevista', function($q) use ($idDepto) {
                $q->where('id_padre', $idDepto);
            })
            ->select('id_e_ind_fvt', 'entrevista_codigo', 'titulo', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
    }

    private function getMunicipiosPorLugarToma($idDepto)
    {
        return DB::table('esclarecimiento.e_ind_fvt as e')
            ->join('catalogos.geo as muni', 'e.entrevista_lugar', '=', 'muni.id_geo')
            ->where('e.id_activo', 1)
            ->where('muni.id_padre', $idDepto)
            ->select('muni.descripcion as nombre', DB::raw('COUNT(*) as total'))
            ->groupBy('muni.id_geo', 'muni.descripcion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    private function getEntrevistasPorOrigen($idDepto)
    {
        return DB::table('esclarecimiento.e_ind_fvt as e')
            ->join('fichas.persona_entrevistada as pe', 'e.id_e_ind_fvt', '=', 'pe.id_e_ind_fvt')
            ->join('fichas.persona as p', 'pe.id_persona', '=', 'p.id_persona')
            ->where('e.id_activo', 1)
            ->where(function($q) use ($idDepto) {
                $q->where('p.id_lugar_nacimiento_depto', $idDepto)
                  ->orWhere('p.id_lugar_residencia_depto', $idDepto);
            })
            ->select('e.id_e_ind_fvt', 'e.entrevista_codigo', 'e.titulo', 'e.created_at')
            ->distinct()
            ->orderBy('e.created_at', 'desc')
            ->limit(20)
            ->get();
    }

    private function getEntrevistasPorMencionados($idDepto)
    {
        return DB::table('esclarecimiento.e_ind_fvt as e')
            ->join('esclarecimiento.contenido_lugar as cl', 'e.id_e_ind_fvt', '=', 'cl.id_e_ind_fvt')
            ->where('e.id_activo', 1)
            ->where('cl.id_departamento', $idDepto)
            ->select('e.id_e_ind_fvt', 'e.entrevista_codigo', 'e.titulo', 'e.created_at')
            ->distinct()
            ->orderBy('e.created_at', 'desc')
            ->limit(20)
            ->get();
    }

    private function getMunicipiosMencionados($idDepto)
    {
        return DB::table('esclarecimiento.contenido_lugar as cl')
            ->join('esclarecimiento.e_ind_fvt as e', 'cl.id_e_ind_fvt', '=', 'e.id_e_ind_fvt')
            ->join('catalogos.geo as muni', 'cl.id_municipio', '=', 'muni.id_geo')
            ->where('e.id_activo', 1)
            ->where('cl.id_departamento', $idDepto)
            ->whereNotNull('cl.id_municipio')
            ->select('muni.descripcion as nombre', DB::raw('COUNT(DISTINCT cl.id_e_ind_fvt) as total'))
            ->groupBy('muni.id_geo', 'muni.descripcion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    /**
     * Coordenadas de departamentos de Colombia
     */
    private function getCoordenadasDepartamentos()
    {
        return [
            'AMAZONAS' => ['lat' => -1.0, 'lng' => -71.9],
            'ANTIOQUIA' => ['lat' => 6.5, 'lng' => -75.5],
            'ARAUCA' => ['lat' => 6.5, 'lng' => -71.0],
            'ATLANTICO' => ['lat' => 10.7, 'lng' => -74.9],
            'BOGOTA D.C.' => ['lat' => 4.6, 'lng' => -74.1],
            'BOGOTA' => ['lat' => 4.6, 'lng' => -74.1],
            'BOLIVAR' => ['lat' => 8.6, 'lng' => -74.0],
            'BOYACA' => ['lat' => 5.5, 'lng' => -73.4],
            'CALDAS' => ['lat' => 5.3, 'lng' => -75.5],
            'CAQUETA' => ['lat' => 0.9, 'lng' => -74.0],
            'CASANARE' => ['lat' => 5.3, 'lng' => -71.3],
            'CAUCA' => ['lat' => 2.5, 'lng' => -76.8],
            'CESAR' => ['lat' => 9.3, 'lng' => -73.5],
            'CHOCO' => ['lat' => 5.7, 'lng' => -76.6],
            'CORDOBA' => ['lat' => 8.3, 'lng' => -75.6],
            'CUNDINAMARCA' => ['lat' => 5.0, 'lng' => -74.0],
            'GUAINIA' => ['lat' => 2.6, 'lng' => -68.5],
            'GUAVIARE' => ['lat' => 2.0, 'lng' => -72.6],
            'HUILA' => ['lat' => 2.5, 'lng' => -75.5],
            'LA GUAJIRA' => ['lat' => 11.5, 'lng' => -72.9],
            'GUAJIRA' => ['lat' => 11.5, 'lng' => -72.9],
            'MAGDALENA' => ['lat' => 10.4, 'lng' => -74.4],
            'META' => ['lat' => 3.5, 'lng' => -73.0],
            'NARINO' => ['lat' => 1.2, 'lng' => -77.3],
            'NORTE DE SANTANDER' => ['lat' => 7.9, 'lng' => -72.5],
            'PUTUMAYO' => ['lat' => 0.4, 'lng' => -76.5],
            'QUINDIO' => ['lat' => 4.5, 'lng' => -75.7],
            'RISARALDA' => ['lat' => 4.8, 'lng' => -75.7],
            'SAN ANDRES' => ['lat' => 12.5, 'lng' => -81.7],
            'SAN ANDRES Y PROVIDENCIA' => ['lat' => 12.5, 'lng' => -81.7],
            'SANTANDER' => ['lat' => 6.6, 'lng' => -73.1],
            'SUCRE' => ['lat' => 9.0, 'lng' => -75.4],
            'TOLIMA' => ['lat' => 4.1, 'lng' => -75.2],
            'VALLE DEL CAUCA' => ['lat' => 3.8, 'lng' => -76.5],
            'VALLE' => ['lat' => 3.8, 'lng' => -76.5],
            'VAUPES' => ['lat' => 0.2, 'lng' => -70.2],
            'VICHADA' => ['lat' => 4.4, 'lng' => -69.3],
        ];
    }
}
