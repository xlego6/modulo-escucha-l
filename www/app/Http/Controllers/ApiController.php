<?php

namespace App\Http\Controllers;

use App\Models\Geo;
use App\Models\CatItem;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Obtener municipios por departamento
     */
    public function municipios(Request $request)
    {
        $id_departamento = $request->get('id_departamento');

        if (!$id_departamento) {
            return response()->json([]);
        }

        $municipios = Geo::where('id_padre', $id_departamento)
            ->where('nivel', 3)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_geo');

        // Agregar opción "Sin Información" al final de cualquier listado de municipios
        $sinInfo = Geo::where('nivel', 3)
            ->where('descripcion', 'Sin Información')
            ->first();

        if ($sinInfo && !$municipios->has($sinInfo->id_geo)) {
            $municipios->put($sinInfo->id_geo, 'Sin Información');
        }

        return response()->json($municipios);
    }

    /**
     * Obtener tipos de testimonio por dependencia
     */
    public function tiposTestimonio(Request $request)
    {
        $id_dependencia = $request->get('id_dependencia');

        // Los tipos dependen de si es DCMH/DADH o DAV
        // Por ahora retornamos todos los tipos
        $tipos = CatItem::where('id_cat', 5)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item');

        return response()->json($tipos);
    }

    /**
     * Buscar personas existentes
     */
    public function buscarPersonas(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $personas = \App\Models\Persona::where(function($q) use ($query) {
            $q->where('nombre', 'ILIKE', "%{$query}%")
              ->orWhere('apellido', 'ILIKE', "%{$query}%")
              ->orWhere('num_documento', 'ILIKE', "%{$query}%");
        })
        ->limit(10)
        ->get()
        ->map(function($p) {
            return [
                'id' => $p->id_persona,
                'text' => $p->fmt_nombre_completo . ($p->num_documento ? ' - ' . $p->num_documento : ''),
            ];
        });

        if ($personas->isNotEmpty()) {
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => Auth::id(),
                'accion'     => 'buscar',
                'objeto'     => 'persona',
                'codigo'     => mb_substr($query, 0, 100),
                'referencia' => 'Búsqueda de persona (autocompletado): "' . $query . '" — ' . $personas->count() . ' resultado(s)',
                'ip'         => $request->ip(),
            ]);
        }

        return response()->json($personas);
    }

    /**
     * Proxy de búsqueda al Tesauro de DDHH del CNMH (TemaTres)
     * Formato Select2: { results: [{id, text}, ...] }
     */
    public function buscarTesauro(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $cacheKey = 'tesauro_search_' . md5($q);

        $results = Cache::remember($cacheKey, 3600, function () use ($q) {
            try {
                $response = Http::timeout(6)->get(
                    'https://tesauro.centrodememoriahistorica.gov.co/vocab/services.php',
                    ['task' => 'search', 'arg' => $q, 'output' => 'json']
                );

                if (!$response->successful()) {
                    return [];
                }

                $data = $response->json();
                $results = [];

                if (!empty($data['result']) && is_array($data['result'])) {
                    foreach ($data['result'] as $term) {
                        if (!empty($term['string']) && empty($term['no_term_string'])) {
                            $results[] = [
                                'id'   => $term['term_id'],
                                'text' => $term['string'],
                            ];
                        }
                    }
                }

                return $results;
            } catch (\Exception $e) {
                return null; // null = no cachear el error
            }
        });

        // Si falló la conexión, no devolver caché vacía
        if ($results === null) {
            Cache::forget($cacheKey);
            return response()->json(['results' => [], 'error' => 'No se pudo conectar al Tesauro.']);
        }

        return response()->json(['results' => $results]);
    }
}
