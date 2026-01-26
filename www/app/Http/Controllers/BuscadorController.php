<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Persona;
use App\Models\Adjunto;
use App\Models\CatItem;
use App\Models\Geo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuscadorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Vista principal de la buscadora - Busqueda unificada
     */
    public function index(Request $request)
    {
        $termino = $request->get('q', '');
        $tiene_busqueda = strlen(trim($termino)) >= 2;

        $resultados = [
            'entrevistas' => collect(),
            'personas' => collect(),
            'documentos' => collect(),
            'total' => 0,
        ];

        if ($tiene_busqueda) {
            // Buscar en los tres tipos simultaneamente
            $resultados['entrevistas'] = $this->buscarEntrevistas($termino, $request);
            $resultados['personas'] = $this->buscarPersonas($termino, $request);
            $resultados['documentos'] = $this->buscarDocumentos($termino, $request);

            $resultados['total'] = $resultados['entrevistas']->count() +
                                   $resultados['personas']->count() +
                                   $resultados['documentos']->count();
        }

        // Catalogos para filtros avanzados
        $territorios = Geo::where('nivel', 2)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_geo')
            ->prepend('-- Todos --', '');

        $sexos = CatItem::where('id_cat', 1)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        $etnias = CatItem::where('id_cat', 3)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        $tipos_adjunto = CatItem::where('id_cat', 6)
            ->orderBy('orden')
            ->pluck('descripcion', 'id_item');

        return view('buscador.index', compact(
            'resultados',
            'termino',
            'tiene_busqueda',
            'territorios',
            'sexos',
            'etnias',
            'tipos_adjunto'
        ));
    }

    /**
     * Buscar entrevistas - Incluye busqueda en documentos asociados
     */
    private function buscarEntrevistas($termino, Request $request, $limite = 50)
    {
        // Primero buscar entrevistas directamente (incluye transcripción en adjunto tipo 312)
        $entrevistasDirectas = Entrevista::where('id_activo', 1)
            ->where(function($q) use ($termino) {
                $q->where('titulo', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('entrevista_codigo', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('anotaciones', 'ILIKE', '%' . $termino . '%') // Legacy
                  ->orWhere('nombre_proyecto', 'ILIKE', '%' . $termino . '%')
                  // Buscar en adjunto de transcripción automatizada
                  ->orWhereHas('rel_adjuntos', function($qa) use ($termino) {
                      $qa->where('id_tipo', Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
                         ->where('texto_extraido', 'ILIKE', '%' . $termino . '%');
                  });
            })
            ->with(['rel_entrevistador', 'rel_entrevistador.rel_usuario', 'rel_lugar_entrevista', 'rel_dependencia_origen', 'rel_equipo_estrategia'])
            ->limit($limite)
            ->get();

        // Agregar atributo de donde viene la coincidencia
        foreach ($entrevistasDirectas as $e) {
            $e->setAttribute('fuente_coincidencia', 'entrevista');

            $coincidencias = [];
            if (stripos($e->entrevista_codigo, $termino) !== false) {
                $coincidencias[] = 'Codigo';
            }
            if (stripos($e->titulo, $termino) !== false) {
                $coincidencias[] = 'Titulo';
            }
            // Buscar en transcripción automatizada (adjunto) o anotaciones legacy
            $transcripcion = $e->getTextoParaProcesamiento();
            if (stripos($transcripcion ?? '', $termino) !== false) {
                $coincidencias[] = 'Transcripcion';
            }
            if (stripos($e->nombre_proyecto ?? '', $termino) !== false) {
                $coincidencias[] = 'Proyecto/Investigacion';
            }
            $e->setAttribute('coincidencias', $coincidencias);
        }

        // Buscar entrevistas que tienen documentos con el termino
        $entrevistasConDocumentos = Entrevista::where('id_activo', 1)
            ->whereHas('rel_adjuntos', function($q) use ($termino) {
                $q->where('existe_archivo', 1)
                  ->where(function($q2) use ($termino) {
                      $q2->where('nombre_original', 'ILIKE', '%' . $termino . '%')
                         ->orWhere('texto_extraido', 'ILIKE', '%' . $termino . '%');
                  });
            })
            ->whereNotIn('id_e_ind_fvt', $entrevistasDirectas->pluck('id_e_ind_fvt'))
            ->with(['rel_entrevistador', 'rel_entrevistador.rel_usuario', 'rel_lugar_entrevista', 'rel_adjuntos'])
            ->limit($limite)
            ->get();

        // Agregar informacion de documentos encontrados
        foreach ($entrevistasConDocumentos as $e) {
            $e->setAttribute('fuente_coincidencia', 'documento');

            $coincidencias = [];
            $documentosCoincidentes = $e->rel_adjuntos->filter(function($adj) use ($termino) {
                return (stripos($adj->nombre_original, $termino) !== false) ||
                       (stripos($adj->texto_extraido ?? '', $termino) !== false);
            });

            foreach ($documentosCoincidentes as $doc) {
                $coincidencia = [
                    'nombre' => $doc->nombre_original,
                    'extracto' => null,
                ];

                if (stripos($doc->texto_extraido ?? '', $termino) !== false) {
                    $coincidencia['extracto'] = $this->extraerContexto($doc->texto_extraido, $termino);
                }

                $coincidencias[] = $coincidencia;
            }
            $e->setAttribute('coincidencias', $coincidencias);
        }

        // Combinar resultados
        return $entrevistasDirectas->merge($entrevistasConDocumentos);
    }

    /**
     * Buscar personas - Incluye busqueda en entrevistas asociadas
     */
    private function buscarPersonas($termino, Request $request, $limite = 50)
    {
        $personas = Persona::with(['rel_sexo', 'rel_etnia', 'rel_tipo_documento'])
            ->where(function($q) use ($termino) {
                $q->where('nombre', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('apellido', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('alias', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('nombre_identitario', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('num_documento', 'ILIKE', '%' . $termino . '%');
            })
            ->limit($limite)
            ->get();

        // Agregar coincidencias
        foreach ($personas as $p) {
            $coincidencias = [];

            if (stripos($p->nombre ?? '', $termino) !== false) {
                $coincidencias[] = 'Nombre';
            }
            if (stripos($p->apellido ?? '', $termino) !== false) {
                $coincidencias[] = 'Apellido';
            }
            if (stripos($p->alias ?? '', $termino) !== false) {
                $coincidencias[] = 'Alias';
            }
            if (stripos($p->nombre_identitario ?? '', $termino) !== false) {
                $coincidencias[] = 'Nombre identitario';
            }
            if (stripos($p->num_documento ?? '', $termino) !== false) {
                $coincidencias[] = 'Documento';
            }
            $p->setAttribute('coincidencias', $coincidencias);

            // Contar entrevistas vinculadas
            $p->setAttribute('num_entrevistas', DB::table('fichas.persona_entrevistada')
                ->where('id_persona', $p->id_persona)
                ->count());
        }

        return $personas;
    }

    /**
     * Buscar en documentos adjuntos
     */
    private function buscarDocumentos($termino, Request $request, $limite = 50)
    {
        $documentos = Adjunto::with(['rel_entrevista', 'rel_tipo'])
            ->where('existe_archivo', 1)
            ->whereHas('rel_entrevista', function($q) {
                $q->where('id_activo', 1);
            })
            ->where(function($q) use ($termino) {
                $q->where('nombre_original', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('texto_extraido', 'ILIKE', '%' . $termino . '%');
            })
            // Ordenar por relevancia: primero los que tienen coincidencia en texto_extraido
            ->orderByRaw("CASE WHEN texto_extraido ILIKE ? THEN 0 ELSE 1 END", ['%' . $termino . '%'])
            ->orderBy('created_at', 'desc')
            ->limit($limite)
            ->get();

        // Agregar extracto con el texto encontrado
        foreach ($documentos as $doc) {
            $coincidencias = [];
            $coincidencia_texto = false;
            $extracto = null;

            if (stripos($doc->nombre_original, $termino) !== false) {
                $coincidencias[] = 'Nombre del archivo';
            }

            if ($doc->texto_extraido && stripos($doc->texto_extraido, $termino) !== false) {
                $coincidencia_texto = true;
                $coincidencias[] = 'Contenido';
                $extracto = $this->extraerContexto($doc->texto_extraido, $termino);
            }

            $doc->setAttribute('coincidencia_texto', $coincidencia_texto);
            $doc->setAttribute('extracto', $extracto);
            $doc->setAttribute('coincidencias', $coincidencias);
        }

        return $documentos;
    }

    /**
     * Extraer contexto alrededor del termino encontrado
     */
    private function extraerContexto($texto, $termino, $caracteres = 150)
    {
        $posicion = stripos($texto, $termino);

        if ($posicion === false) {
            return Str::limit($texto, $caracteres * 2);
        }

        $inicio = max(0, $posicion - $caracteres);
        $fin = min(strlen($texto), $posicion + strlen($termino) + $caracteres);

        $extracto = substr($texto, $inicio, $fin - $inicio);

        // Limpiar inicio y fin
        if ($inicio > 0) {
            $extracto = '...' . ltrim(substr($extracto, strpos($extracto, ' ') + 1));
        }
        if ($fin < strlen($texto)) {
            $extracto = substr($extracto, 0, strrpos($extracto, ' ')) . '...';
        }

        // Resaltar el termino
        $extracto = preg_replace(
            '/(' . preg_quote($termino, '/') . ')/i',
            '<mark class="bg-warning">$1</mark>',
            $extracto
        );

        return $extracto;
    }

    /**
     * Busqueda rapida (AJAX)
     */
    public function rapida(Request $request)
    {
        $termino = $request->get('q', '');

        if (strlen($termino) < 2) {
            return response()->json([]);
        }

        $resultados = [];

        // Buscar entrevistas
        $entrevistas = Entrevista::where('id_activo', 1)
            ->where(function($q) use ($termino) {
                $q->where('titulo', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('entrevista_codigo', 'ILIKE', '%' . $termino . '%');
            })
            ->limit(5)
            ->get(['id_e_ind_fvt', 'entrevista_codigo', 'titulo']);

        foreach ($entrevistas as $e) {
            $resultados[] = [
                'tipo' => 'entrevista',
                'id' => $e->id_e_ind_fvt,
                'titulo' => $e->entrevista_codigo . ' - ' . Str::limit($e->titulo, 40),
                'url' => route('entrevistas.show', $e->id_e_ind_fvt),
            ];
        }

        // Buscar personas
        $personas = Persona::where(function($q) use ($termino) {
                $q->where('nombre', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('apellido', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('num_documento', 'ILIKE', '%' . $termino . '%');
            })
            ->limit(5)
            ->get(['id_persona', 'nombre', 'apellido', 'num_documento']);

        foreach ($personas as $p) {
            $resultados[] = [
                'tipo' => 'persona',
                'id' => $p->id_persona,
                'titulo' => $p->nombre . ' ' . $p->apellido . ($p->num_documento ? ' (' . $p->num_documento . ')' : ''),
                'url' => route('personas.show', $p->id_persona),
            ];
        }

        // Buscar en documentos
        $documentos = Adjunto::where('existe_archivo', 1)
            ->whereHas('rel_entrevista', function($q) {
                $q->where('id_activo', 1);
            })
            ->where(function($q) use ($termino) {
                $q->where('nombre_original', 'ILIKE', '%' . $termino . '%')
                  ->orWhere('texto_extraido', 'ILIKE', '%' . $termino . '%');
            })
            ->with('rel_entrevista')
            ->limit(3)
            ->get();

        foreach ($documentos as $d) {
            $resultados[] = [
                'tipo' => 'documento',
                'id' => $d->id_adjunto,
                'titulo' => $d->nombre_original . ($d->rel_entrevista ? ' (' . $d->rel_entrevista->entrevista_codigo . ')' : ''),
                'url' => $d->rel_entrevista ? route('adjuntos.gestionar', $d->rel_entrevista->id_e_ind_fvt) : '#',
            ];
        }

        return response()->json($resultados);
    }
}
