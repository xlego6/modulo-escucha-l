@extends('layouts.app')

@section('title', 'Procesamientos')
@section('content_header', 'Centro de Procesamientos')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.tipo-selector { display: flex; gap: 10px; flex-wrap: wrap; }
.tipo-selector .btn { flex: 1; min-width: 160px; text-align: left; padding: 12px 18px; }
.tipo-selector .btn.active { box-shadow: 0 0 0 3px rgba(235,192,26,0.5); }
.tipo-selector .btn small { display: block; font-weight: normal; opacity: 0.75; margin-top: 2px; }

.stat-block { border-radius: 6px; padding: 16px; color: #fff; height: 100%; }
.stat-block .stat-label { font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em; opacity: 0.9; }
.stat-block .stat-main { font-size: 1.7rem; font-weight: 700; line-height: 1.1; margin: 6px 0 2px; }
.stat-block .stat-row { font-size: 0.82rem; opacity: 0.85; margin-top: 4px; }
.stat-block .stat-row span { display: inline-block; margin-right: 12px; }

.bg-procesadas  { background: linear-gradient(135deg,#709fc3,#4a80a8); }
.bg-asignadas   { background: linear-gradient(135deg,#a5a5a5,#7f7f7f); }
.bg-en-edicion  { background: linear-gradient(135deg,#73c0c3,#4ea8ab); }
.bg-en-revision { background: linear-gradient(135deg,#daa913,#b08a0e); }
.bg-rechazadas  { background: linear-gradient(135deg,#ee133b,#c00f2e); }
.bg-aprobadas   { background: linear-gradient(135deg,#91bd5e,#6a9440); }
.bg-totales     { background: linear-gradient(135deg,#595959,#333); }

.detalle-section { border-left: 4px solid #ebc01a; padding-left: 12px; }
.detalle-block { background: #f8f8f8; border: 1px solid #e0e0e0; border-radius: 6px; padding: 14px; margin-bottom: 10px; }
.detalle-block .db-label { font-size: 0.78rem; text-transform: uppercase; color: #7f7f7f; letter-spacing: 0.04em; }
.detalle-block .db-val { font-size: 1.4rem; font-weight: 700; color: #333; }
.detalle-block .db-sub { font-size: 0.8rem; color: #595959; margin-top: 3px; }
</style>
@endsection

@php
function fmtDur($seg) {
    if (!$seg) return '00:00:00';
    $h = floor($seg / 3600);
    $m = floor(($seg % 3600) / 60);
    $s = $seg % 60;
    return sprintf('%02d:%02d:%02d', $h, $m, $s);
}
@endphp

@section('content')

{{-- ══════════════════════════════════════════════════════════
     SELECTOR TRANSCRIPCIONES / ANONIMIZACIONES
═══════════════════════════════════════════════════════════ --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary mb-0">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-layer-group mr-2"></i>Vista de Procesamientos</h3>
            </div>
            <div class="card-body py-2">
                <div class="tipo-selector">
                    <button type="button"
                            class="btn btn-outline-primary {{ $tipo === 'transcripcion' ? 'active' : '' }}"
                            onclick="cambiarTipo('transcripcion')">
                        <i class="fas fa-file-alt"></i> Transcripciones
                        <small>Edición y revisión de textos</small>
                    </button>
                    <button type="button"
                            class="btn btn-outline-danger {{ $tipo === 'anonimizacion' ? 'active' : '' }}"
                            onclick="cambiarTipo('anonimizacion')">
                        <i class="fas fa-user-secret"></i> Anonimizaciones
                        <small>Versiones públicas de testimonios</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     SECCIÓN: TRANSCRIPCIONES
═══════════════════════════════════════════════════════════ --}}
<div id="seccion-transcripcion" class="{{ $tipo === 'transcripcion' ? '' : 'd-none' }}">

    {{-- Bloques estadísticos globales --}}
    <div class="row mb-1">
        <div class="col-12">
            <h5 class="text-muted"><i class="fas fa-chart-bar mr-2"></i>Resumen global de transcripciones</h5>
        </div>
    </div>
    <div class="row mb-3">
        @php $bt = $statsTranscripcion; @endphp

        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-procesadas">
                <div class="stat-label">Procesadas</div>
                <div class="stat-main">{{ number_format($bt['procesadas']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['procesadas']['cantidad_audios']) }} audios</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['procesadas']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-asignadas">
                <div class="stat-label">Asignadas</div>
                <div class="stat-main">{{ number_format($bt['asignada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['asignada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['asignada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-en-edicion">
                <div class="stat-label">En edición</div>
                <div class="stat-main">{{ number_format($bt['en_edicion']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['en_edicion']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['en_edicion']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-en-revision">
                <div class="stat-label">En revisión</div>
                <div class="stat-main">{{ number_format($bt['enviada_revision']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['enviada_revision']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['enviada_revision']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-rechazadas">
                <div class="stat-label">Rechazadas</div>
                <div class="stat-main">{{ number_format($bt['rechazada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['rechazada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['rechazada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-aprobadas">
                <div class="stat-label">Aprobadas</div>
                <div class="stat-main">{{ number_format($bt['aprobada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['aprobada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['aprobada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-totales">
                <div class="stat-label">Totales</div>
                <div class="stat-main">{{ number_format($bt['totales']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($bt['totales']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($bt['totales']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtro por transcriptor / dependencia --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Detalle por transcriptor o dependencia</h3>
        </div>
        <form method="GET" action="{{ route('procesamientos.index') }}">
            <input type="hidden" name="tipo" value="transcripcion">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label class="text-sm font-weight-bold">Transcriptor(es)</label>
                            <select name="ids[]" id="sel-transcriptores" class="form-control select2" multiple>
                                @foreach($transcriptores as $tr)
                                    <option value="{{ $tr->id_entrevistador }}"
                                        {{ in_array($tr->id_entrevistador, (array)$filtroIds) && $tipo === 'transcripcion' ? 'selected' : '' }}>
                                        {{ $tr->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="text-sm font-weight-bold">Dependencia</label>
                            <select name="dependencia" class="form-control">
                                <option value="">-- Todas --</option>
                                @foreach($dependencias as $dep)
                                    <option value="{{ $dep->id_item }}"
                                        {{ $filtroDependencia == $dep->id_item && $tipo === 'transcripcion' ? 'selected' : '' }}>
                                        {{ $dep->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-search mr-1"></i>Ver detalle
                        </button>
                        @if(($tipo === 'transcripcion') && (!empty($filtroIds) || !empty($filtroDependencia)))
                            <a href="{{ route('procesamientos.index') }}?tipo=transcripcion" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Detalle del filtro (si hay filtro aplicado) --}}
    @if($tipo === 'transcripcion' && $detalleStats)
    <div class="detalle-section mb-3">
        <h5 class="mb-3"><i class="fas fa-user-edit mr-2 text-primary"></i>Detalle de transcriptores seleccionados</h5>

        {{-- Bloques de detalle por estado --}}
        <div class="row mb-3">
            @php
                $colores = [
                    'asignada'         => 'bg-asignadas',
                    'en_edicion'       => 'bg-en-edicion',
                    'enviada_revision'  => 'bg-en-revision',
                    'rechazada'        => 'bg-rechazadas',
                    'aprobada'         => 'bg-aprobadas',
                    'totales'          => 'bg-totales',
                ];
                $etiquetas = [
                    'asignada'         => 'Asignadas',
                    'en_edicion'       => 'En edición',
                    'enviada_revision'  => 'En revisión',
                    'rechazada'        => 'Rechazadas',
                    'aprobada'         => 'Aprobadas',
                    'totales'          => 'Totales',
                ];
            @endphp
            @foreach($etiquetas as $key => $label)
            @php $sd = $detalleStats[$key]; @endphp
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="stat-block {{ $colores[$key] }}">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-main">{{ number_format($sd['cantidad_entrevistas']) }}</div>
                    <div class="stat-row">
                        <span><i class="fas fa-music"></i> {{ number_format($sd['cantidad_audios']) }}</span>
                        <span><i class="fas fa-clock"></i> {{ fmtDur($sd['duracion_total']) }}</span>
                    </div>
                    @if($key === 'en_edicion' && isset($sd['tiempo_edicion']))
                    <div class="stat-row">
                        <span><i class="fas fa-stopwatch"></i> Edición: {{ fmtDur($sd['tiempo_edicion']) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        {{-- Listado de asignaciones --}}
        @if($detalleAsignaciones->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas asignadas ({{ $detalleAsignaciones->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Audio asignado</th>
                            <th>Transcriptor</th>
                            <th>Fecha asig.</th>
                            <th>Estado</th>
                            <th class="text-right">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalleAsignaciones as $asig)
                        @php
                            $badgeClass = [
                                'asignada'        => 'badge-secondary',
                                'en_edicion'      => 'badge-info',
                                'enviada_revision' => 'badge-warning',
                                'rechazada'       => 'badge-danger',
                                'aprobada'        => 'badge-success',
                            ][$asig->estado] ?? 'badge-secondary';
                            $labelEstado = [
                                'asignada'        => 'Asignada',
                                'en_edicion'      => 'En edición',
                                'enviada_revision' => 'En revisión',
                                'rechazada'       => 'Rechazada',
                                'aprobada'        => 'Aprobada',
                            ][$asig->estado] ?? $asig->estado;
                            $duracion = $asig->id_adjunto ? $asig->duracion_audio : $asig->duracion_total;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('entrevistas.show', $asig->id_e_ind_fvt) }}" target="_blank">
                                    {{ $asig->entrevista_codigo ?? 'SIN-CÓDIGO' }}
                                </a>
                            </td>
                            <td>
                                @if($asig->id_adjunto && $asig->nombre_audio)
                                    <small><i class="fas fa-file-audio text-info mr-1"></i>{{ \Illuminate\Support\Str::limit($asig->nombre_audio, 35) }}</small>
                                @else
                                    <small class="text-muted">{{ $asig->num_audios }} audio(s) en total</small>
                                @endif
                            </td>
                            <td>{{ $asig->nombre_persona }}</td>
                            <td>{{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d/m/Y') : '-' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $labelEstado }}</span></td>
                            <td class="text-right text-monospace">{{ fmtDur($duracion) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        @php
                            // Sumar duración de audios únicos (sin repetir el mismo audio en distintos estados)
                            $durTotal = $detalleAsignaciones->filter(fn($a) => $a->id_adjunto)->unique('id_adjunto')->sum('duracion_audio')
                                      + $detalleAsignaciones->filter(fn($a) => !$a->id_adjunto)->unique('id_e_ind_fvt')->sum('duracion_total');
                        @endphp
                        <tr>
                            <td colspan="5"><strong>Total asignaciones (audios únicos)</strong></td>
                            <td class="text-right text-monospace"><strong>{{ fmtDur($durTotal) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
        <div class="alert alert-info">No se encontraron asignaciones para el filtro seleccionado.</div>
        @endif
    </div>
    @endif

</div>{{-- /seccion-transcripcion --}}


{{-- ══════════════════════════════════════════════════════════
     SECCIÓN: ANONIMIZACIONES
═══════════════════════════════════════════════════════════ --}}
<div id="seccion-anonimizacion" class="{{ $tipo === 'anonimizacion' ? '' : 'd-none' }}">

    {{-- Bloques estadísticos globales --}}
    <div class="row mb-1">
        <div class="col-12">
            <h5 class="text-muted"><i class="fas fa-chart-bar mr-2"></i>Resumen global de anonimizaciones</h5>
        </div>
    </div>
    <div class="row mb-3">
        @php $ba = $statsAnonimizacion; @endphp

        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-asignadas">
                <div class="stat-label">Asignadas</div>
                <div class="stat-main">{{ number_format($ba['asignada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['asignada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['asignada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-en-edicion">
                <div class="stat-label">En edición</div>
                <div class="stat-main">{{ number_format($ba['en_edicion']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['en_edicion']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['en_edicion']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-en-revision">
                <div class="stat-label">En revisión</div>
                <div class="stat-main">{{ number_format($ba['enviada_revision']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['enviada_revision']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['enviada_revision']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-rechazadas">
                <div class="stat-label">Rechazadas</div>
                <div class="stat-main">{{ number_format($ba['rechazada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['rechazada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['rechazada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-aprobadas">
                <div class="stat-label">Aprobadas</div>
                <div class="stat-main">{{ number_format($ba['aprobada']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['aprobada']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['aprobada']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-lg col-md-4 col-6 mb-2">
            <div class="stat-block bg-totales">
                <div class="stat-label">Totales</div>
                <div class="stat-main">{{ number_format($ba['totales']['cantidad_entrevistas']) }}</div>
                <div class="stat-row">
                    <span><i class="fas fa-music"></i> {{ number_format($ba['totales']['cantidad_audios']) }}</span>
                    <span><i class="fas fa-clock"></i> {{ fmtDur($ba['totales']['duracion_total']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Filtro por anonimizador / dependencia --}}
    <div class="card card-outline card-secondary mb-3">
        <div class="card-header py-2">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Detalle por anonimizador o dependencia</h3>
        </div>
        <form method="GET" action="{{ route('procesamientos.index') }}">
            <input type="hidden" name="tipo" value="anonimizacion">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="form-group mb-0">
                            <label class="text-sm font-weight-bold">Anonimizador(es)</label>
                            <select name="ids[]" id="sel-anonimizadores" class="form-control select2" multiple>
                                @foreach($anonimizadores as $an)
                                    <option value="{{ $an->id_entrevistador }}"
                                        {{ in_array($an->id_entrevistador, (array)$filtroIds) && $tipo === 'anonimizacion' ? 'selected' : '' }}>
                                        {{ $an->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="text-sm font-weight-bold">Dependencia</label>
                            <select name="dependencia" class="form-control">
                                <option value="">-- Todas --</option>
                                @foreach($dependencias as $dep)
                                    <option value="{{ $dep->id_item }}"
                                        {{ $filtroDependencia == $dep->id_item && $tipo === 'anonimizacion' ? 'selected' : '' }}>
                                        {{ $dep->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-danger mr-2">
                            <i class="fas fa-search mr-1"></i>Ver detalle
                        </button>
                        @if(($tipo === 'anonimizacion') && (!empty($filtroIds) || !empty($filtroDependencia)))
                            <a href="{{ route('procesamientos.index') }}?tipo=anonimizacion" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Detalle del filtro (si hay filtro aplicado) --}}
    @if($tipo === 'anonimizacion' && $detalleStats)
    <div class="detalle-section mb-3">
        <h5 class="mb-3"><i class="fas fa-user-secret mr-2 text-danger"></i>Detalle de anonimizadores seleccionados</h5>

        <div class="row mb-3">
            @foreach($etiquetas ?? ['asignada' => 'Asignadas', 'en_edicion' => 'En edición', 'enviada_revision' => 'En revisión', 'rechazada' => 'Rechazadas', 'aprobada' => 'Aprobadas', 'totales' => 'Totales'] as $key => $label)
            @php
                $coloresD = ['asignada'=>'bg-asignadas','en_edicion'=>'bg-en-edicion','enviada_revision'=>'bg-en-revision','rechazada'=>'bg-rechazadas','aprobada'=>'bg-aprobadas','totales'=>'bg-totales'];
                $sd = $detalleStats[$key];
            @endphp
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="stat-block {{ $coloresD[$key] }}">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="stat-main">{{ number_format($sd['cantidad_entrevistas']) }}</div>
                    <div class="stat-row">
                        <span><i class="fas fa-music"></i> {{ number_format($sd['cantidad_audios']) }}</span>
                        <span><i class="fas fa-clock"></i> {{ fmtDur($sd['duracion_total']) }}</span>
                    </div>
                    @if($key === 'en_edicion' && isset($sd['tiempo_edicion']))
                    <div class="stat-row">
                        <span><i class="fas fa-stopwatch"></i> Edición: {{ fmtDur($sd['tiempo_edicion']) }}</span>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if($detalleAsignaciones->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas asignadas ({{ $detalleAsignaciones->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Anonimizador</th>
                            <th>Fecha asignación</th>
                            <th>Estado</th>
                            <th class="text-center">Audios</th>
                            <th class="text-right">Duración</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($detalleAsignaciones as $asig)
                        @php
                            $badgeClass = ['asignada'=>'badge-secondary','en_edicion'=>'badge-info','enviada_revision'=>'badge-warning','rechazada'=>'badge-danger','aprobada'=>'badge-success'][$asig->estado] ?? 'badge-secondary';
                            $labelEstado = ['asignada'=>'Asignada','en_edicion'=>'En edición','enviada_revision'=>'En revisión','rechazada'=>'Rechazada','aprobada'=>'Aprobada'][$asig->estado] ?? $asig->estado;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('entrevistas.show', $asig->id_e_ind_fvt) }}" target="_blank">
                                    {{ $asig->entrevista_codigo ?? 'SIN-CÓDIGO' }}
                                </a>
                            </td>
                            <td>{{ $asig->nombre_persona }}</td>
                            <td>{{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d/m/Y') : '-' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $labelEstado }}</span></td>
                            <td class="text-center">{{ $asig->num_audios }}</td>
                            <td class="text-right text-monospace">{{ fmtDur($asig->duracion_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <td colspan="4"><strong>Totales</strong></td>
                            <td class="text-center"><strong>{{ $detalleAsignaciones->sum('num_audios') }}</strong></td>
                            <td class="text-right text-monospace"><strong>{{ fmtDur($detalleAsignaciones->sum('duracion_total')) }}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        @else
        <div class="alert alert-info">No se encontraron asignaciones para el filtro seleccionado.</div>
        @endif
    </div>
    @endif

</div>{{-- /seccion-anonimizacion --}}


{{-- ══════════════════════════════════════════════════════════
     ACCESOS RÁPIDOS Y COLA DE TRABAJOS
═══════════════════════════════════════════════════════════ --}}
<div class="row mt-2">
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('procesamientos.transcripcion') }}" class="small-box-footer">
        <div class="small-box bg-primary">
            <div class="inner"><h4>Transcripción</h4><p>Automatizada (WhisperX)</p></div>
            <div class="icon"><i class="fas fa-robot"></i></div>
        </div></a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('procesamientos.edicion') }}" class="small-box-footer">
        <div class="small-box bg-success">
            <div class="inner"><h4>Edición</h4><p>Revisión de transcripciones</p></div>
            <div class="icon"><i class="fas fa-edit"></i></div>
        </div></a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('procesamientos.entidades') }}" class="small-box-footer">
        <div class="small-box bg-warning">
            <div class="inner"><h4>Entidades</h4><p>Detección NER (spaCy)</p></div>
            <div class="icon"><i class="fas fa-tags"></i></div>
        </div></a>
    </div>
    <div class="col-md-3 col-sm-6">
        <a href="{{ route('procesamientos.anonimizacion') }}" class="small-box-footer">
        <div class="small-box bg-danger">
            <div class="inner"><h4>Anonimización</h4><p>Versiones públicas</p></div>
            <div class="icon"><i class="fas fa-user-secret"></i></div>
        </div></a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Cola de trabajos</h3>
            </div>
            <div class="card-body py-2">
                <div class="d-flex justify-content-between mb-2">
                    <span><i class="fas fa-clock text-info mr-2"></i>En espera</span>
                    <span class="badge badge-info badge-pill px-3">{{ $trabajosEnCola }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span><i class="fas fa-cog fa-spin text-warning mr-2"></i>Procesando</span>
                    <span class="badge badge-warning badge-pill px-3">{{ $trabajosProcesando }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({ placeholder: 'Seleccionar...', allowClear: true, width: '100%' });
});

function cambiarTipo(tipo) {
    window.location.href = '{{ route('procesamientos.index') }}?tipo=' + tipo;
}
</script>
@endsection
