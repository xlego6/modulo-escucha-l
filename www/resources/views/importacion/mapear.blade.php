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

        {{-- Catálogos --}}
        @foreach($valoresUnicos as $campo => $valores)
        @if($campo === 'lugar_depto' || $campo === 'lugar_muni_raw') @continue @endif
        @if(isset($etiquetas[$campo]) && count($valores) > 0)
        <div class="card mb-2">
            <div class="card-header py-2">
                <h6 class="mb-0">{{ $etiquetas[$campo] ?? $campo }}</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width:45%">Valor en CSV</th>
                            <th>Mapear a</th>
                            <th style="width:5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($valores as $valorCsv)
                        @php
                            $sugerido = $sugerencias[$campo][$valorCsv] ?? null;
                            $actual   = $mapeosActuales[$campo][$valorCsv] ?? $sugerido;
                            $opciones = $catalogos[$campo] ?? [];
                        @endphp
                        <tr>
                            <td class="align-middle">
                                <code>{{ $valorCsv }}</code>
                            </td>
                            <td>
                                <select class="form-control form-control-sm"
                                    name="mapeos[{{ $campo }}][{{ $valorCsv }}]">
                                    <option value="">— Sin mapear (se omite) —</option>
                                    @foreach($opciones as $idOpc => $descOpc)
                                    <option value="{{ $idOpc }}" @selected($actual == $idOpc)>
                                        {{ $descOpc }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="align-middle text-center">
                                @if($sugerido && $actual == $sugerido)
                                <span title="Sugerencia automática" class="text-success"><i class="fas fa-magic"></i></span>
                                @elseif($actual)
                                <span title="Mapeado manualmente" class="text-primary"><i class="fas fa-check"></i></span>
                                @else
                                <span title="Sin mapear" class="text-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @endforeach

        {{-- Geografía --}}
        @if(!empty($valoresUnicos['lugar_depto']) || !empty($valoresUnicos['lugar_muni_raw']))
        <div class="card mb-2">
            <div class="card-header py-2">
                <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Lugares geográficos</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Valor en CSV</th>
                            <th>Tipo</th>
                            <th>Mapear a</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($valoresUnicos['lugar_depto'] ?? [] as $depto)
                        @php
                            $sugeridoD = $sugerencias['lugar_depto'][$depto] ?? null;
                            $actualD   = $mapeosGeo['lugar_depto'][$depto] ?? $sugeridoD;
                        @endphp
                        <tr>
                            <td><code>{{ $depto }}</code></td>
                            <td><span class="badge badge-secondary">Departamento</span></td>
                            <td>
                                <select class="form-control form-control-sm select2-geo"
                                    name="mapeos_geo[lugar_depto][{{ $depto }}]">
                                    <option value="">— Sin mapear —</option>
                                    @foreach($departamentos as $geo)
                                    <option value="{{ $geo->id_geo }}" @selected($actualD == $geo->id_geo)>
                                        {{ $geo->descripcion }}
                                    </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                        @endforeach

                        @foreach($valoresUnicos['lugar_muni_raw'] ?? [] as $key)
                        @php
                            [$deptoNom, $muniNom] = explode('||', $key, 2);
                            $sugeridoM = $sugerencias['lugar_muni'][$key] ?? null;
                            $actualM   = $mapeosGeo['lugar_muni'][$key] ?? $sugeridoM;
                            $idDeptoMapeado = $mapeosGeo['lugar_depto'][$deptoNom]
                                           ?? ($sugerencias['lugar_depto'][$deptoNom] ?? null);
                        @endphp
                        <tr>
                            <td><code>{{ $muniNom }}</code> <small class="text-muted">({{ $deptoNom }})</small></td>
                            <td><span class="badge badge-light">Municipio</span></td>
                            <td>
                                {{-- Las opciones se inyectan desde JS (evita N × 1100 <option> en el HTML) --}}
                                <select class="form-control form-control-sm select2-muni"
                                    name="mapeos_geo[lugar_muni][{{ $key }}]"
                                    data-depto="{{ $idDeptoMapeado }}"
                                    data-selected="{{ $actualM ?? '' }}">
                                    <option value="">— Sin mapear —</option>
                                </select>
                                @if($sugeridoM && (!$actualM || $actualM == $sugeridoM))
                                <small class="text-success"><i class="fas fa-magic"></i> Sugerencia automática</small>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
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
    $('select.form-control-sm').not('.select2-geo, .select2-muni').each(function () {
        $(this).select2({ theme: 'bootstrap4', width: '100%', minimumResultsForSearch: 5 });
    });

    $('.select2-geo').select2({ theme: 'bootstrap4', width: '100%' });

    // Municipios: se pasan como JSON para evitar renderizar N × 1100 <option> en el servidor.
    // Cada select recibe data-depto (id del departamento padre para filtrar) y
    // data-selected (id del municipio actualmente mapeado).
    var todosMunicipios = @json($municipios->map(fn($m) => ['id' => $m->id_geo, 'text' => $m->descripcion, 'padre' => $m->id_padre])->values());

    $('.select2-muni').each(function () {
        var $sel     = $(this);
        var idDepto  = parseInt($sel.data('depto'))    || 0;
        var selected = parseInt($sel.data('selected')) || 0;

        // Filtrar por departamento; si no hay depto mapeado mostrar todos
        var lista = idDepto
            ? todosMunicipios.filter(function (m) { return m.padre == idDepto; })
            : todosMunicipios.slice();

        // Si el valor seleccionado no está en la lista filtrada, incluirlo igualmente
        if (selected && !lista.find(function (m) { return m.id == selected; })) {
            var extra = todosMunicipios.find(function (m) { return m.id == selected; });
            if (extra) lista.unshift(extra);
        }

        var data = [{ id: '', text: '— Sin mapear —' }].concat(
            lista.map(function (m) { return { id: m.id, text: m.text }; })
        );

        $sel.select2({ theme: 'bootstrap4', width: '100%', data: data });

        // Restaurar valor seleccionado (Select2 con data: no lee el atributo selected del DOM)
        if (selected) {
            $sel.val(selected).trigger('change.select2');
        }
    });
});
</script>
@endsection
