<?php

namespace App\Http\Controllers;

use App\Models\Entrevista;
use App\Models\Persona;
use App\Models\Adjunto;
use App\Models\CatItem;
use App\Models\Geo;
use App\Models\TrazaActividad;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
        $tiene_texto   = strlen(trim($termino)) >= 2;
        $tiene_filtros = $request->filled('id_departamento')
                      || $request->filled('id_municipio')
                      || $request->filled('id_hecho_victimizante')
                      || $request->filled('id_resistencia')
                      || $request->filled('id_dependencia');
        $tiene_busqueda = $tiene_texto || $tiene_filtros;

        $perPage     = 25;
        $limiteTotal = 500;
        $pageE = max(1, (int) $request->get('page_e', 1));
        $pageP = max(1, (int) $request->get('page_p', 1));
        $pageD = max(1, (int) $request->get('page_d', 1));

        $resultados = [
            'entrevistas' => new LengthAwarePaginator(collect(), 0, $perPage, $pageE, ['path' => $request->url(), 'pageName' => 'page_e']),
            'personas'    => new LengthAwarePaginator(collect(), 0, $perPage, $pageP, ['path' => $request->url(), 'pageName' => 'page_p']),
            'documentos'  => new LengthAwarePaginator(collect(), 0, $perPage, $pageD, ['path' => $request->url(), 'pageName' => 'page_d']),
            'total'       => 0,
            'total_e'     => 0,
            'total_p'     => 0,
            'total_d'     => 0,
            'cap_hit_e'   => false,
            'cap_hit_p'   => false,
            'cap_hit_d'   => false,
        ];

        if ($tiene_busqueda) {
            $todasEntrevistas = $this->buscarEntrevistas($termino, $request, $limiteTotal);
            $todasPersonas    = $tiene_texto ? $this->buscarPersonas($termino, $request, $limiteTotal) : collect();
            $todosDocumentos  = $tiene_texto ? $this->buscarDocumentos($termino, $request, $limiteTotal) : collect();

            $totalE = $todasEntrevistas->count();
            $totalP = $todasPersonas->count();
            $totalD = $todosDocumentos->count();

            $resultados['total_e']   = $totalE;
            $resultados['total_p']   = $totalP;
            $resultados['total_d']   = $totalD;
            $resultados['total']     = $totalE + $totalP + $totalD;
            $resultados['cap_hit_e'] = $totalE >= $limiteTotal;
            $resultados['cap_hit_p'] = $totalP >= $limiteTotal;
            $resultados['cap_hit_d'] = $totalD >= $limiteTotal;

            $resultados['entrevistas'] = (new LengthAwarePaginator(
                $todasEntrevistas->forPage($pageE, $perPage),
                $totalE,
                $perPage,
                $pageE,
                ['path' => $request->url(), 'pageName' => 'page_e']
            ))->appends($request->except('page_e'));

            $resultados['personas'] = (new LengthAwarePaginator(
                $todasPersonas->forPage($pageP, $perPage),
                $totalP,
                $perPage,
                $pageP,
                ['path' => $request->url(), 'pageName' => 'page_p']
            ))->appends($request->except('page_p'));

            $resultados['documentos'] = (new LengthAwarePaginator(
                $todosDocumentos->forPage($pageD, $perPage),
                $totalD,
                $perPage,
                $pageD,
                ['path' => $request->url(), 'pageName' => 'page_d']
            ))->appends($request->except('page_d'));

            // Registrar búsqueda en traza (con texto y/o solo con filtros)
            if ($tiene_texto) {
                $referencia = 'Búsqueda: "' . $termino . '" — ' . $resultados['total'] . ' resultado(s)';
            } else {
                $filtros = http_build_query($request->only([
                    'id_departamento', 'id_municipio', 'id_hecho_victimizante', 'id_resistencia', 'id_dependencia',
                ]));
                $referencia = 'Búsqueda por filtros (' . $filtros . ') — ' . $resultados['total'] . ' resultado(s)';
            }

            TrazaActividad::create([
                'fecha_hora'  => now(),
                'id_usuario'  => Auth::id(),
                'accion'      => 'buscar',
                'objeto'      => 'buscador',
                'codigo'      => $tiene_texto ? mb_substr($termino, 0, 100) : null,
                'referencia'  => $referencia,
                'ip'          => $request->ip(),
            ]);
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

        // Hechos victimizantes para filtro (id_cat=10)
        $hechos_victimizantes = CatItem::where('id_cat', 10)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        // Practicas de resistencia (id_cat=20)
        $resistencias = CatItem::where('id_cat', 20)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todos --', '');

        // Dependencias de origen (id_cat=4)
        $dependencias = CatItem::where('id_cat', 4)
            ->orderBy('descripcion')
            ->pluck('descripcion', 'id_item')
            ->prepend('-- Todas --', '');

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
            'tiene_texto',
            'tiene_filtros',
            'territorios',
            'sexos',
            'etnias',
            'tipos_adjunto',
            'hechos_victimizantes',
            'resistencias',
            'dependencias',
            'entrevistadorActual',
            'permisosAprobados'
        ));
    }

    /**
     * Buscar entrevistas - Incluye busqueda en documentos asociados
     * El buscador muestra todas las entrevistas a todos los roles autenticados;
     * el control de acceso al detalle/edicion se aplica en EntrevistaController.
     */
    private function buscarEntrevistas($termino, Request $request, $limite = 100)
    {
        $tiene_texto = strlen(trim($termino)) >= 2;
        $terminos    = $tiene_texto ? $this->parsearTerminos($termino) : [];

        $query = Entrevista::where('id_activo', 1);

        // Búsqueda en texto solo cuando hay término
        if ($tiene_texto) {
            $query->where(function($q) use ($terminos) {
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
        }

        // Geo filter: departamento (by ID - covers both id_territorio and entrevista_lugar.id_padre)
        if ($request->filled('id_departamento')) {
            $idDepto = (int) $request->id_departamento;
            $query->where(function($q) use ($idDepto) {
                $q->where('id_territorio', $idDepto)
                  ->orWhereHas('rel_lugar_entrevista', function($q2) use ($idDepto) {
                      $q2->where('id_padre', $idDepto);
                  });
            });
        }

        // Municipio filter (by ID - exact match on entrevista_lugar)
        if ($request->filled('id_municipio')) {
            $query->where('entrevista_lugar', (int) $request->id_municipio);
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

        // Dependencia origen filter
        if ($request->filled('id_dependencia')) {
            $query->where('id_dependencia_origen', (int) $request->id_dependencia);
        }

        $entrevistasDirectas = $query->with([
            'rel_entrevistador', 'rel_entrevistador.rel_usuario',
            'rel_lugar_entrevista', 'rel_dependencia_origen',
            'rel_equipo_estrategia', 'rel_contenido'
        ])->limit($limite)->get();

        // Atributos de coincidencia (solo con texto)
        foreach ($entrevistasDirectas as $e) {
            $e->setAttribute('fuente_coincidencia', 'entrevista');
            $coincidencias = [];
            if ($tiene_texto) {
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
            }
            $e->setAttribute('coincidencias', $coincidencias);
        }

        // Búsqueda en documentos adjuntos solo con término de texto
        $entrevistasConDocumentos = collect();
        if ($tiene_texto) {
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
                ->limit(max(0, $limite - $entrevistasDirectas->count()))
                ->get();

            foreach ($entrevistasConDocumentos as $e) {
                $e->setAttribute('fuente_coincidencia', 'documento');
                $coincidencias = [];
                $documentosCoincidentes = $e->rel_adjuntos->filter(function($adj) use ($termino) {
                    return (stripos($adj->nombre_original, $termino) !== false) ||
                           (stripos($adj->texto_extraido ?? '', $termino) !== false);
                });
                foreach ($documentosCoincidentes as $doc) {
                    $coincidencias[] = ['nombre' => $doc->nombre_original];
                }
                $e->setAttribute('coincidencias', $coincidencias);
            }
        }

        $merged = $entrevistasDirectas->merge($entrevistasConDocumentos);

        if ($tiene_texto) {
            return $merged->sortByDesc(fn($e) => $this->calcularRelevanciaEntrevista($e))->values();
        }
        return $merged->sortByDesc('entrevista_fecha')->values();
    }

    /**
     * Buscar personas - Incluye busqueda en entrevistas asociadas
     */
    private function buscarPersonas($termino, Request $request, $limite = 100)
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

        return $personas->sortByDesc(fn($p) => $this->calcularRelevanciaPersona($p))->values();
    }

    /**
     * Buscar en documentos adjuntos
     */
    private function buscarDocumentos($termino, Request $request, $limite = 100)
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

        // Agregar coincidencias (sin exponer el texto encontrado)
        foreach ($documentos as $doc) {
            $coincidencias = [];
            $coincidencia_texto = false;

            if (stripos($doc->nombre_original, $termino) !== false) {
                $coincidencias[] = 'Nombre del archivo';
            }

            if ($doc->texto_extraido && stripos($doc->texto_extraido, $termino) !== false) {
                $coincidencia_texto = true;
                $coincidencias[] = 'Contenido';
            }

            $doc->setAttribute('coincidencia_texto', $coincidencia_texto);
            $doc->setAttribute('coincidencias', $coincidencias);
        }

        return $documentos;
    }

    private function calcularRelevanciaEntrevista($entrevista): int
    {
        if ($entrevista->fuente_coincidencia === 'documento') {
            return 10;
        }
        $score = 20;
        foreach ($entrevista->coincidencias ?? [] as $c) {
            $score = max($score, match($c) {
                'Codigo'        => 100,
                'Titulo'        => 80,
                'Proyecto'      => 60,
                'Responsables'  => 55,
                'Temas'         => 50,
                'Otras Poblaciones', 'Otras Ocupaciones',
                'Detalle Etnicos', 'Otros Hechos',
                'Detalle Resistencias' => 45,
                'Transcripcion' => 40,
                default         => 30,
            });
        }
        return $score;
    }

    private function calcularRelevanciaPersona($persona): int
    {
        $score = 0;
        foreach ($persona->coincidencias ?? [] as $c) {
            $score = max($score, match($c) {
                'Nombre', 'Apellido'          => 100,
                'Alias', 'Nombre identitario' => 80,
                'Documento'                   => 60,
                default                       => 40,
            });
        }
        return $score ?: 20;
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

        if (!empty($resultados)) {
            TrazaActividad::create([
                'fecha_hora' => now(),
                'id_usuario' => Auth::id(),
                'accion'     => 'buscar_rapido',
                'objeto'     => 'buscador',
                'codigo'     => mb_substr($termino, 0, 100),
                'referencia' => 'Búsqueda rápida: "' . $termino . '" — ' . count($resultados) . ' resultado(s)',
                'ip'         => $request->ip(),
            ]);
        }

        return response()->json($resultados);
    }
}
