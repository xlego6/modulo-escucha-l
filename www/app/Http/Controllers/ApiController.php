<?php

namespace App\Http\Controllers;

use App\Models\Geo;
use App\Models\CatItem;
use Illuminate\Http\Request;

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

        return response()->json($personas);
    }
}
