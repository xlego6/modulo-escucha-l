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
            $resultados['entrevistas'] = $this->buscarEntrevistas($termino, $request);
            $resultados['personas'] = $this->buscarPersonas($termino, $request);
            $resultados['documentos'] = $this->buscarDocumentos($termino, $request);

            $resultados['total'] = $resultados['entrevistas']->count() +
                                   $resultados['personas']->count() +
                                   $resultados['documentos']->count();
        }

        // Catalogos para filtros
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

        // Hechos victimizantes para filtro
        $hechos_victimizantes = CatItem::whereHas('rel_catalogo', function($q) {
                $q->where('nombre', 'like', '%hechos%')->orWhere('nombre', 'like', '%victimiz%');
            })
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        // Practicas de resistencia
        $resistencias = CatItem::whereHas('rel_catalogo', function($q) {
                $q->where('nombre', 'like', '%resistencia%');
            })
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        // Determine permission context for current user
        $user = \Illuminate\Support\Facades\Auth::user();
        $entrevistadorActual = \App\Models\Entrevistador::where('id_usuario', $user->id)->first();
        $permisosAprobados = collect();
        if ($entrevistadorActual) {
            $permisosAprobados = \App\Models\Permiso::where('id_entrevistador', $entrevistadorActual->id_entrevistador)
                ->where('id_estado', \App\Models\Permiso::ESTADO_VIGENTE)
                ->where(function($q) {
                    $q->where('es_solicitud', false)
                      ->orWhere(function($q2) {
                          $q2->where('es_solicitud', true)
                             ->where('estado_solicitud', \App\Models\Permiso::SOLICITUD_APROBADA);
                      });
                })
                ->pluck('id_e_ind_fvt');
        }

        return view('buscador.index', compact(
            'resultados',
            'termino',
            'tiene_busqueda',
            'territorios',
            'sexos',
            'etnias',
            'tipos_adjunto',
            'hechos_victimizantes',
            'resistencias',
            'entrevistadorActual',
            'permisosAprobados'
        ));
    }

    /**
     * Buscar entrevistas - Incluye busqueda en documentos asociados
     * El buscador muestra todas las entrevistas a todos los roles autenticados;
     * el control de acceso al detalle/edicion se aplica en EntrevistaController.
     */
    private function buscarEntrevistas($termino, Request $request, $limite = 50)
    {
        $terminos = $this->parsearTerminos($termino);

        $query = Entrevista::where('id_activo', 1);

        // Apply boolean text search across all relevant text fields
        $query->where(function($q) use ($terminos) {
            // First apply main text fields
            foreach ($terminos as $i => $t) {
                $term = $t['termino'];
                $op = $t['operador'];

                $aplicar = function($q) use ($term) {
                    $q->where('titulo', 'ILIKE', '%' . $term . '%')
                      ->orWhere('entrevista_codigo', 'ILIKE', '%' . $term . '%')
                      ->orWhere('anotaciones', 'ILIKE', '%' . $term . '%')
                      ->orWhere('nombre_proyecto', 'ILIKE', '%' . $term . '%')
                      ->orWhere('detalle_idiomas', 'ILIKE', '%' . $term . '%')
                      ->orWhereHas('rel_contenido', function($qc) use ($term) {
                          $qc->where('otras_poblaciones_mencionadas', 'ILIKE', '%' . $term . '%')
                             ->orWhere('otras_ocupaciones_mencionadas', 'ILIKE', '%' . $term . '%')
                             ->orWhere('detalle_grupos_etnicos', 'ILIKE', '%' . $term . '%')
                             ->orWhere('otros_hechos_victimizantes', 'ILIKE', '%' . $term . '%')
                             ->orWhere('detalle_resistencias', 'ILIKE', '%' . $term . '%')
                             ->orWhere('responsables_individuales', 'ILIKE', '%' . $term . '%')
                             ->orWhere('temas_abordados', 'ILIKE', '%' . $term . '%');
                      })
                      ->orWhereHas('rel_adjuntos', function($qa) use ($term) {
                          $qa->where('id_tipo', Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
                             ->where('texto_extraido', 'ILIKE', '%' . $term . '%');
                      });
                };

                if ($i === 0 || $op === null || $op === 'AND') {
                    $q->where(function($q2) use ($aplicar) { $aplicar($q2); });
                } elseif ($op === 'OR') {
                    $q->orWhere(function($q2) use ($aplicar) { $aplicar($q2); });
                } elseif ($op === 'NOT') {
                    $q->whereNot(function($q2) use ($aplicar) { $aplicar($q2); });
                }
            }
        });

        // Geo filter: departamento (text search on geo table)
        if ($request->filled('departamento')) {
            $query->whereHas('rel_lugar_entrevista', function($q) use ($request) {
                $q->where('descripcion', 'ILIKE', '%' . $request->departamento . '%');
            });
        }

        // Municipio filter
        if ($request->filled('municipio')) {
            $query->whereHas('rel_entrevistador', function($q) use ($request) {
                // Search in entrevista_lugar geo
            });
            // Also search by location code in entrevista table directly
            $query->whereHas('rel_lugar_toma', function($q) use ($request) {
                $q->where('descripcion', 'ILIKE', '%' . $request->municipio . '%');
            });
        }

        // Hecho victimizante filter
        if ($request->filled('id_hecho_victimizante')) {
            $query->whereHas('rel_contenido.rel_hechos_victimizantes', function($q) use ($request) {
                $q->where('cat_item.id_item', $request->id_hecho_victimizante);
            });
        }

        // Resistencia filter
        if ($request->filled('id_resistencia')) {
            $query->whereHas('rel_contenido.rel_practicas_resistencia', function($q) use ($request) {
                $q->where('cat_item.id_item', $request->id_resistencia);
            });
        }

        $entrevistasDirectas = $query->with([
            'rel_entrevistador', 'rel_entrevistador.rel_usuario',
            'rel_lugar_entrevista', 'rel_dependencia_origen',
            'rel_equipo_estrategia', 'rel_contenido'
        ])->limit($limite)->get();

        // Add coincidencia attributes
        foreach ($entrevistasDirectas as $e) {
            $e->setAttribute('fuente_coincidencia', 'entrevista');
            $coincidencias = [];
            if (stripos($e->entrevista_codigo, $termino) !== false) $coincidencias[] = 'Codigo';
            if (stripos($e->titulo, $termino) !== false) $coincidencias[] = 'Titulo';
            $transcripcion = $e->getTextoParaProcesamiento();
            if (stripos($transcripcion ?? '', $termino) !== false) $coincidencias[] = 'Transcripcion';
            if (stripos($e->nombre_proyecto ?? '', $termino) !== false) $coincidencias[] = 'Proyecto';
            if ($e->rel_contenido) {
                $camposContenido = [
                    'otras_poblaciones_mencionadas' => 'Otras Poblaciones',
                    'otras_ocupaciones_mencionadas' => 'Otras Ocupaciones',
                    'detalle_grupos_etnicos' => 'Detalle Etnicos',
                    'otros_hechos_victimizantes' => 'Otros Hechos',
                    'detalle_resistencias' => 'Detalle Resistencias',
                    'responsables_individuales' => 'Responsables',
                    'temas_abordados' => 'Temas',
                ];
                foreach ($camposContenido as $campo => $etiqueta) {
                    if (stripos($e->rel_contenido->$campo ?? '', $termino) !== false) {
                        $coincidencias[] = $etiqueta;
                    }
                }
            }
            $e->setAttribute('coincidencias', $coincidencias);
        }

        // Also search in document contents
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

        foreach ($entrevistasConDocumentos as $e) {
            $e->setAttribute('fuente_coincidencia', 'documento');
            $coincidencias = [];
            $documentosCoincidentes = $e->rel_adjuntos->filter(function($adj) use ($termino) {
                return (stripos($adj->nombre_original, $termino) !== false) ||
                       (stripos($adj->texto_extraido ?? '', $termino) !== false);
            });
            foreach ($documentosCoincidentes as $doc) {
                $coincidencia = ['nombre' => $doc->nombre_original, 'extracto' => null];
                if (stripos($doc->texto_extraido ?? '', $termino) !== false) {
                    $coincidencia['extracto'] = $this->extraerContexto($doc->texto_extraido, $termino);
                }
                $coincidencias[] = $coincidencia;
            }
            $e->setAttribute('coincidencias', $coincidencias);
        }

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
     * Parse query string with boolean operators (AND, OR, NOT) and quoted phrases
     * Returns array of ['termino' => string, 'operador' => 'AND'|'OR'|'NOT'|null]
     */
    private function parsearTerminos($query)
    {
        $query = trim($query);
        if (empty($query)) return [];

        // Check if query contains boolean operators
        if (!preg_match('/\b(AND|OR|NOT)\b/i', $query) && strpos($query, '"') === false) {
            return [['termino' => $query, 'operador' => null]];
        }

        $tokens = [];
        $operadorActual = 'AND'; // default

        // Tokenize: find quoted phrases and individual words with operators
        preg_match_all('/("(?:[^"]+)"|\bNOT\b|\bAND\b|\bOR\b|[^\s]+)/i', $query, $matches);
        $partes = $matches[0];

        foreach ($partes as $parte) {
            $upper = strtoupper($parte);
            if ($upper === 'AND') {
                $operadorActual = 'AND';
            } elseif ($upper === 'OR') {
                $operadorActual = 'OR';
            } elseif ($upper === 'NOT') {
                $operadorActual = 'NOT';
            } else {
                $termino = trim($parte, '"');
                if (!empty($termino)) {
                    $tokens[] = ['termino' => $termino, 'operador' => $operadorActual];
                    $operadorActual = 'AND'; // reset to AND after each term
                }
            }
        }

        return empty($tokens) ? [['termino' => $query, 'operador' => null]] : $tokens;
    }

    /**
     * Apply boolean terms to a query across multiple fields
     */
    private function aplicarTerminosBool($query, array $campos, array $terminos)
    {
        foreach ($terminos as $i => $t) {
            $termino = $t['termino'];
            $operador = $t['operador'];

            if ($i === 0 || $operador === 'AND' || $operador === null) {
                $query->where(function($q) use ($campos, $termino) {
                    foreach ($campos as $j => $campo) {
                        if ($j === 0) {
                            $q->where($campo, 'ILIKE', '%' . $termino . '%');
                        } else {
                            $q->orWhere($campo, 'ILIKE', '%' . $termino . '%');
                        }
                    }
                });
            } elseif ($operador === 'OR') {
                $query->orWhere(function($q) use ($campos, $termino) {
                    foreach ($campos as $j => $campo) {
                        if ($j === 0) {
                            $q->where($campo, 'ILIKE', '%' . $termino . '%');
                        } else {
                            $q->orWhere($campo, 'ILIKE', '%' . $termino . '%');
                        }
                    }
                });
            } elseif ($operador === 'NOT') {
                $query->where(function($q) use ($campos, $termino) {
                    foreach ($campos as $campo) {
                        $q->where($campo, 'NOT ILIKE', '%' . $termino . '%');
                    }
                });
            }
        }
        return $query;
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
