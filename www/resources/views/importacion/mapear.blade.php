@extends('layouts.app')

@section('title', 'Mapeo de catálogos — Importación #' . $importacion->id_importacion)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Importación #{{ $importacion->id_importacion }} — Paso 2 de 4</h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-file-csv text-success"></i> {{ $importacion->nombre_archivo }}
                    · {{ $importacion->total_expedientes }} expedientes
                </p>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @include('importacion._pasos', ['paso_actual' => 2])

    <form method="POST" action="{{ route('importacion.guardar_mapeos', $importacion->id_importacion) }}">
        @csrf

        @php
        $etiquetas = [
            'dependencia'            => 'Dependencia de Origen',
            'equipo_estrategia'      => 'Equipo / Estrategia',
            'tipo_testimonio'        => 'Tipo de testimonio',
            'formato'                => 'Formato',
            'modalidad'              => 'Modalidad',
            'idioma'                 => 'Idioma',
            'necesidad_reparacion'   => 'Necesidad de reparación',
            'areas_compatibles'      => 'Área compatible',
            'sexo'                   => 'Sexo',
            'identidad_genero'       => 'Identidad de género',
            'orientacion_sexual'     => 'Orientación sexual',
            'etnia'                  => 'Grupo étnico',
            'rango_etario'           => 'Rango etario',
            'discapacidad'           => 'Discapacidad',
            'poblacion'              => 'Población (testimoniante)',
            'ocupacion'              => 'Ocupación (testimoniante)',
            'contenido_poblacion'    => 'Población mencionada',
            'contenido_ocupacion'    => 'Ocupación mencionada',
            'contenido_sexo'         => 'Sexo mencionado',
            'contenido_identidad'    => 'Identidad de género mencionada',
            'contenido_orientacion'  => 'Orientación sexual mencionada',
            'contenido_etnia'        => 'Grupo étnico mencionado',
            'contenido_rango_etario' => 'Rango etario mencionado',
            'contenido_discapacidad' => 'Discapacidad mencionada',
            'contenido_hecho'        => 'Hecho victimizante',
            'contenido_responsable'  => 'Responsable colectivo',
            'contenido_practica'     => 'Práctica de resistencia',
        ];
        @endphp

        {{-- ================================================================
             CATÁLOGOS — con tiers de confianza
             ================================================================ --}}
        @foreach($valoresUnicos as $campo => $valores)
        @if($campo === 'lugar_depto' || $campo === 'lugar_muni_raw') @continue @endif
        @if(!isset($etiquetas[$campo]) || count($valores) === 0) @continue @endif

        @php
            $exactos  = [];
            $revision = [];
            $opciones = $catalogos[$campo] ?? collect();
            foreach ($valores as $valorCsv) {
                $conf = $sugerencias["{$campo}_confianza"][$valorCsv] ?? null;
                $sug  = $sugerencias[$campo][$valorCsv] ?? null;
                $act  = $mapeosActuales[$campo][$valorCsv] ?? $sug;
                if ($conf === 'exacto' && $act) {
                    $exactos[] = ['csv' => $valorCsv, 'id' => $act, 'nombre' => $opciones[$act] ?? '?'];
                } else {
                    $revision[] = ['csv' => $valorCsv, 'sug' => $sug, 'act' => $act, 'conf' => $conf];
                }
            }
        @endphp

        <div class="card mb-2 card-mapeo">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ $etiquetas[$campo] }}</h6>
                <span class="text-muted small">
                    @if(count($exactos) > 0)
                    <span class="text-success"><i class="fas fa-check-circle"></i> {{ count($exactos) }}</span>
                    @endif
                    @if(count($revision) > 0)
                    <span class="ml-1 {{ count(array_filter($revision, fn($r) => !$r['act'])) > 0 ? 'text-danger' : 'text-warning' }}">
                        <i class="fas fa-pencil-alt"></i> {{ count($revision) }}
                    </span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">

                {{-- Exact matches: collapsed summary + hidden inputs --}}
                @if(count($exactos) > 0)
                <div class="px-3 py-2 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-check-circle text-success"></i>
                        <strong>{{ count($exactos) }}</strong> coincidencia(s) exacta(s) — auto-mapeadas
                    </span>
                    <a data-toggle="collapse" href="#exact-{{ $campo }}" class="btn btn-xs btn-outline-secondary">
                        <i class="fas fa-eye"></i> Revisar
                    </a>
                </div>
                @foreach($exactos as $e)
                <input type="hidden" class="hidden-auto-{{ $campo }}" name="mapeos[{{ $campo }}][{{ $e['csv'] }}]" value="{{ $e['id'] }}">
                @endforeach
                <div class="collapse" id="exact-{{ $campo }}">
                    <table class="table table-sm mb-0">
                        <tbody>
                            @foreach($exactos as $e)
                            <tr class="table-success">
                                <td style="width:45%"><code>{{ $e['csv'] }}</code></td>
                                <td>{{ $e['nombre'] }}</td>
                                <td style="width:5%" class="text-center"><i class="fas fa-check text-success"></i></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                {{-- Needs review: interactive dropdowns --}}
                @if(count($revision) > 0)
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:45%">Valor en CSV</th>
                            <th>Mapear a</th>
                            <th style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($revision as $r)
                        <tr class="{{ $r['conf'] === 'parcial' ? 'table-warning' : 'table-danger' }}">
                            <td class="align-middle"><code>{{ $r['csv'] }}</code></td>
                            <td>
                                <select class="form-control form-control-sm lazy-select2"
                                    name="mapeos[{{ $campo }}][{{ $r['csv'] }}]">
                                    <option value="">— Sin mapear (se omite) —</option>
                                    @foreach($opciones as $idOpc => $descOpc)
                                    <option value="{{ $idOpc }}" @selected($r['act'] == $idOpc)>
                                        {{ $descOpc }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="align-middle text-center">
                                @if($r['conf'] === 'parcial')
                                <span title="Sugerencia parcial" class="text-warning"><i class="fas fa-magic"></i></span>
                                @else
                                <span title="Sin mapear" class="text-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

            </div>
        </div>
        @endforeach

        {{-- ================================================================
             GEOGRAFÍA — agrupada por departamento del CSV
             ================================================================ --}}
        @if(!empty($valoresUnicos['lugar_depto']) || !empty($valoresUnicos['lugar_muni_raw']))
        @php
            // Lookup rápido id_geo → nombre de departamento
            $deptoMap = $departamentos->pluck('descripcion', 'id_geo');

            // Agrupar municipios por departamento del CSV
            $munisPorDepto = [];
            foreach (($valoresUnicos['lugar_muni_raw'] ?? []) as $key) {
                [$deptoNom,] = explode('||', $key, 2);
                $munisPorDepto[$deptoNom][] = $key;
            }

            // Departamentos del CSV que tienen municipios o están en lugar_depto
            $deptosEnCsv = $valoresUnicos['lugar_depto'] ?? [];
            // Departamentos que solo aparecen en municipios (sin fila propia)
            $deptosSoloMunis = array_diff(array_keys($munisPorDepto), $deptosEnCsv);

            // Resumen
            $totalExactosGeo = 0;
            $totalRevisionGeo = 0;
            foreach ($deptosEnCsv as $d) {
                if (($sugerencias['lugar_depto_confianza'][$d] ?? null) === 'exacto' && ($mapeosGeo['lugar_depto'][$d] ?? $sugerencias['lugar_depto'][$d] ?? null)) {
                    $totalExactosGeo++;
                } else {
                    $totalRevisionGeo++;
                }
            }
            foreach (($valoresUnicos['lugar_muni_raw'] ?? []) as $k) {
                $confM = $sugerencias['lugar_muni_confianza'][$k] ?? null;
                $actM  = $mapeosGeo['lugar_muni'][$k] ?? $sugerencias['lugar_muni'][$k] ?? null;
                if ($confM === 'exacto' && $actM) {
                    $totalExactosGeo++;
                } else {
                    $totalRevisionGeo++;
                }
            }
        @endphp

        <h5 class="mt-3 mb-2">
            <i class="fas fa-map-marker-alt"></i> Lugares geográficos
            <small class="text-muted">
                — <span class="text-success">{{ $totalExactosGeo }} auto</span>,
                <span class="{{ $totalRevisionGeo > 0 ? 'text-warning' : 'text-muted' }}">{{ $totalRevisionGeo }} por revisar</span>
            </small>
        </h5>

        @foreach(array_merge($deptosEnCsv, $deptosSoloMunis) as $deptoNom)
        @php
            $sugeridoD = $sugerencias['lugar_depto'][$deptoNom] ?? null;
            $confD     = $sugerencias['lugar_depto_confianza'][$deptoNom] ?? null;
            $actualD   = $mapeosGeo['lugar_depto'][$deptoNom] ?? $sugeridoD;
            $esDeptoExacto = $confD === 'exacto' && $actualD;
            $munisDeEsteDepto = $munisPorDepto[$deptoNom] ?? [];

            // Separar municipios exactos vs revisar
            $munisExactos = [];
            $munisRevision = [];
            foreach ($munisDeEsteDepto as $key) {
                $confM = $sugerencias['lugar_muni_confianza'][$key] ?? null;
                $sugM  = $sugerencias['lugar_muni'][$key] ?? null;
                $actM  = $mapeosGeo['lugar_muni'][$key] ?? $sugM;
                if ($confM === 'exacto' && $actM) {
                    $munisExactos[] = $key;
                } else {
                    $munisRevision[] = ['key' => $key, 'conf' => $confM, 'act' => $actM];
                }
            }
        @endphp

        <div class="card mb-2 card-mapeo">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="fas fa-map-marker-alt text-primary"></i>
                    {{ $deptoNom ?: '(sin departamento)' }}
                    @if($esDeptoExacto)
                    <span class="badge badge-success ml-1"><i class="fas fa-check"></i></span>
                    @elseif(in_array($deptoNom, $deptosEnCsv))
                    <span class="badge badge-warning ml-1">revisar</span>
                    @endif
                </h6>
                <span class="text-muted small">
                    @if(count($munisExactos))
                    <span class="text-success"><i class="fas fa-check-circle"></i> {{ count($munisExactos) }}</span>
                    @endif
                    @if(count($munisRevision))
                    <span class="ml-1 text-warning"><i class="fas fa-pencil-alt"></i> {{ count($munisRevision) }}</span>
                    @endif
                </span>
            </div>
            <div class="card-body p-0">
                {{-- Hidden inputs para matches exactos (fuera de la tabla) --}}
                @if($esDeptoExacto)
                <input type="hidden" name="mapeos_geo[lugar_depto][{{ $deptoNom }}]" value="{{ $actualD }}">
                @endif
                @foreach($munisExactos as $key)
                @php $actM = $mapeosGeo['lugar_muni'][$key] ?? $sugerencias['lugar_muni'][$key]; @endphp
                <input type="hidden" name="mapeos_geo[lugar_muni][{{ $key }}]" value="{{ $actM }}">
                @endforeach

                <table class="table table-sm mb-0">
                    <tbody>
                        {{-- Fila del departamento --}}
                        @if(in_array($deptoNom, $deptosEnCsv))
                        <tr class="{{ $esDeptoExacto ? 'table-success' : 'table-warning' }}">
                            <td style="width:45%">
                                <code>{{ $deptoNom }}</code>
                                <span class="badge badge-secondary ml-1">Departamento</span>
                            </td>
                            <td>
                                @if($esDeptoExacto)
                                {{ $departamentos->firstWhere('id_geo', $actualD)->descripcion ?? '?' }}
                                <i class="fas fa-check text-success ml-1"></i>
                                @else
                                <select class="form-control form-control-sm lazy-select2 select2-geo-depto"
                                    name="mapeos_geo[lugar_depto][{{ $deptoNom }}]">
                                    <option value="">— Sin mapear —</option>
                                    @foreach($departamentos as $geo)
                                    <option value="{{ $geo->id_geo }}" @selected($actualD == $geo->id_geo)>
                                        {{ $geo->descripcion }}
                                    </option>
                                    @endforeach
                                </select>
                                @endif
                            </td>
                        </tr>
                        @endif

                        {{-- Municipios exactos: colapsados --}}
                        @if(count($munisExactos) > 0)
                        <tr class="bg-light">
                            <td colspan="2" class="py-1 pl-4">
                                <i class="fas fa-check-circle text-success"></i>
                                <strong>{{ count($munisExactos) }}</strong> municipio(s) auto-mapeado(s)
                                <a href="#" class="ml-2 small toggle-munis-exact" data-target="#munis-exact-{{ \Illuminate\Support\Str::slug($deptoNom) }}">
                                    <i class="fas fa-eye"></i> Revisar
                                </a>
                            </td>
                        </tr>
                    </tbody>
                    <tbody class="munis-exact-toggle" id="munis-exact-{{ \Illuminate\Support\Str::slug($deptoNom) }}" style="display:none">
                        @foreach($munisExactos as $key)
                        @php
                            [, $muniNom] = explode('||', $key, 2);
                            $actM = $mapeosGeo['lugar_muni'][$key] ?? $sugerencias['lugar_muni'][$key];
                        @endphp
                        <tr class="table-success">
                            <td style="width:45%; padding-left:2rem"><code>{{ $muniNom }}</code></td>
                            <td>{{ $municipios->firstWhere('id_geo', $actM)->descripcion ?? '?' }} <i class="fas fa-check text-success"></i></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tbody>
                        @endif

                        {{-- Municipios que necesitan revisión --}}
                        @foreach($munisRevision as $mr)
                        @php
                            [, $muniNom] = explode('||', $mr['key'], 2);
                            // Para 'otro_depto' no filtrar por departamento: el municipio
                            // sugerido no pertenece al depto del CSV, hay que buscar libremente.
                            $idDeptoSelect = ($mr['conf'] === 'otro_depto') ? '' : ($actualD ?? '');
                        @endphp
                        <tr class="{{ $mr['conf'] === 'parcial' || $mr['conf'] === 'otro_depto' ? 'table-warning' : 'table-danger' }}">
                            <td style="width:45%; padding-left:2rem">
                                <code>{{ $muniNom }}</code>
                                <span class="badge badge-light ml-1">Municipio</span>
                                @if($mr['conf'] === 'otro_depto')
                                <span class="badge badge-warning ml-1" title="Municipio encontrado en otro departamento — busque el correcto en el selector"><i class="fas fa-exchange-alt"></i> otro depto</span>
                                @elseif($mr['conf'] === 'ambiguo')
                                <span class="badge badge-danger ml-1" title="Nombre repetido en varios departamentos — seleccione manualmente"><i class="fas fa-exclamation-triangle"></i> ambiguo</span>
                                @endif
                            </td>
                            <td>
                                <select class="form-control form-control-sm select2-muni"
                                    name="mapeos_geo[lugar_muni][{{ $mr['key'] }}]"
                                    data-depto="{{ $idDeptoSelect }}"
                                    data-selected="{{ $mr['act'] ?? '' }}">
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endforeach
        @endif

        <div class="d-flex justify-content-between mb-4 mt-2">
            <a href="{{ route('importacion.create') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Nueva importación
            </a>
            <button type="submit" class="btn btn-primary btn-lg">
                Guardar mapeos y continuar <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </form>

</div>
</section>
@endsection

@section('scripts')
<script>
$(document).ready(function () {
    // ------------------------------------------------------------------
    // Datos de municipios (JSON único, ~100 KB)
    // ------------------------------------------------------------------
    var deptoNames = @json($deptoMap);
    var todosMunicipios = @json($municipios->map(fn($m) => ['id' => $m->id_geo, 'text' => $m->descripcion, 'padre' => $m->id_padre])->values());

    var opcionesCache = {};
    function buildOpcionesMuni(idDepto) {
        var key = idDepto || '_all';
        if (!opcionesCache[key]) {
            var sinFiltro = !idDepto;
            var lista = sinFiltro
                ? todosMunicipios
                : todosMunicipios.filter(function (m) { return m.padre == idDepto; });
            var html = '<option value="">— Sin mapear —</option>';
            for (var i = 0; i < lista.length; i++) {
                var nombre = lista[i].text.replace(/&/g,'&amp;').replace(/</g,'&lt;');
                var label  = sinFiltro && deptoNames[lista[i].padre]
                    ? nombre + ' — ' + ('' + deptoNames[lista[i].padre]).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                    : nombre;
                html += '<option value="' + lista[i].id + '">' + label + '</option>';
            }
            opcionesCache[key] = html;
        }
        return opcionesCache[key];
    }

    function initMunisEnCard($card) {
        // Collect all selects first, then process — avoids DOM mutation
        // from Select2 breaking the jQuery .each() iterator.
        var selects = $card.find('select.select2-muni:not(.select2-hidden-accessible)').toArray();

        for (var i = 0; i < selects.length; i++) {
            (function (el, idx) {
                var $sel     = $(el);
                var idDepto  = parseInt($sel.data('depto'))    || 0;
                var selected = parseInt($sel.data('selected')) || 0;

                // Populate options (fast DOM op)
                $sel.html(buildOpcionesMuni(idDepto));

                if (selected && !$sel.find('option[value="' + selected + '"]').length) {
                    var extra = todosMunicipios.find(function (m) { return m.id == selected; });
                    if (extra) {
                        $sel.prepend('<option value="' + extra.id + '">' +
                            extra.text.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</option>');
                    }
                }
                if (selected) {
                    $sel.val(String(selected));
                }

                // Stagger Select2 init so each runs in its own tick
                var s2opts = { theme: 'bootstrap4', width: '100%' };
                if (!idDepto) {
                    s2opts.minimumInputLength = 2;
                    s2opts.placeholder = 'Escriba para buscar...';
                }
                setTimeout(function () {
                    $sel.select2(s2opts);
                }, idx * 30);
            })(selects[i], i);
        }
    }

    // ------------------------------------------------------------------
    // Lazy init: Select2 de catálogos Y municipios solo al entrar al viewport
    // ------------------------------------------------------------------
    var select2Opts = { theme: 'bootstrap4', width: '100%', minimumResultsForSearch: 5 };

    function initCardSelects(card) {
        var $card = $(card);
        // Catálogos y departamentos
        $card.find('select.lazy-select2:not(.select2-hidden-accessible)').each(function () {
            $(this).select2(select2Opts);
        });
        // Municipios
        initMunisEnCard($card);
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;
                initCardSelects(entry.target);
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '300px' });

        document.querySelectorAll('.card-mapeo').forEach(function (card) {
            observer.observe(card);
        });
    } else {
        document.querySelectorAll('.card-mapeo').forEach(function (card) {
            initCardSelects(card);
        });
    }

    // ------------------------------------------------------------------
    // Toggle de municipios exactos (tbody show/hide)
    // ------------------------------------------------------------------
    $(document).on('click', '.toggle-munis-exact', function (e) {
        e.preventDefault();
        var $target = $($(this).data('target'));
        $target.toggle();
        var $icon = $(this).find('i');
        $icon.toggleClass('fa-eye fa-eye-slash');
    });

    // ------------------------------------------------------------------
    // Cuando el usuario cambia el departamento, recargar los selects de
    // municipio dentro del mismo card con la lista filtrada del nuevo depto.
    // ------------------------------------------------------------------
    $(document).on('change', 'select.select2-geo-depto', function () {
        var newDeptoId = parseInt($(this).val()) || 0;
        var $card = $(this).closest('.card-mapeo');

        $card.find('select.select2-muni').each(function () {
            var $sel = $(this);

            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }

            $sel.html(buildOpcionesMuni(newDeptoId));
            $sel.attr('data-depto', newDeptoId || '');
            $sel.val('');

            var s2opts = { theme: 'bootstrap4', width: '100%' };
            if (!newDeptoId) {
                s2opts.minimumInputLength = 2;
                s2opts.placeholder = 'Escriba para buscar...';
            }
            $sel.select2(s2opts);
        });
    });
});
</script>
@endsection
