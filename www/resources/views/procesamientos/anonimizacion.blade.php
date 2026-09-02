@extends('layouts.app')

@section('title', 'Anonimizacion')
@section('content_header', 'Anonimizacion de Testimonios')

@section('css')
<style>
.stat-block { border-radius:6px; padding:10px 12px; color:#fff; min-height:80px; display:flex; flex-direction:column; justify-content:space-between; }
.stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; opacity:.85; }
.stat-main  { font-size:28px; font-weight:700; line-height:1.1; }
.stat-row   { font-size:11px; opacity:.9; display:flex; justify-content:space-between; flex-wrap:wrap; gap:4px; margin-top:2px; }
.bg-asignadas   { background: linear-gradient(135deg,#4a1f1f,#7a2e2e); }
.bg-en-edicion  { background: linear-gradient(135deg,#8e0000,#c62828); }
.bg-en-revision { background: linear-gradient(135deg,#e65100,#fb8c00); }
.bg-rechazadas  { background: linear-gradient(135deg,#4a0e0e,#8b1a1a); }
.bg-aprobadas   { background: linear-gradient(135deg,#1b5e20,#388e3c); }
.bg-totales     { background: linear-gradient(135deg,#4a148c,#7b1fa2); }
.js-historial-pop { cursor: pointer; }
.js-historial-pop:focus { outline: none; box-shadow: none; }
.popover.historial-popover { min-width: 300px; max-width: 380px; font-size: 12px; }
.popover.historial-popover .popover-header { font-size: 12px; }
</style>
@endsection

@section('content')
{{-- Bloques estadísticos --}}
@php
    $bt = $stats;
    function fmtDurAnon($s) {
        if (!$s) return '0m';
        $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60);
        return $h ? "{$h}h {$m}m" : "{$m}m";
    }
@endphp
<div class="row mb-3">
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-asignadas">
            <div class="stat-label">Asignadas</div>
            <div class="stat-main">{{ number_format($bt['asignada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['asignada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['asignada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-edicion">
            <div class="stat-label">En edición</div>
            <div class="stat-main">{{ number_format($bt['en_edicion']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['en_edicion']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['en_edicion']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-revision">
            <div class="stat-label">En revisión</div>
            <div class="stat-main">{{ number_format($bt['enviada_revision']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['enviada_revision']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['enviada_revision']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-rechazadas">
            <div class="stat-label">Rechazadas</div>
            <div class="stat-main">{{ number_format($bt['rechazada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['rechazada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['rechazada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-aprobadas">
            <div class="stat-label">Aprobadas</div>
            <div class="stat-main">{{ number_format($bt['aprobada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['aprobada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['aprobada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-totales">
            <div class="stat-label">Totales</div>
            <div class="stat-main">{{ number_format($bt['totales']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['totales']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurAnon($bt['totales']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Mis anonimizaciones asignadas (solo Líder) --}}
@if(isset($misAsignaciones) && $misAsignaciones->isNotEmpty())
<div class="card card-danger card-outline mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Mis anonimizaciones asignadas ({{ $misAsignaciones->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Código</th>
                    <th>Asignada</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($misAsignaciones as $asig)
                @php
                    $badgeClassMis = [
                        'asignada'         => 'badge-secondary',
                        'en_edicion'       => 'badge-info',
                        'enviada_revision'  => 'badge-warning',
                        'rechazada'        => 'badge-danger',
                    ][$asig->estado] ?? 'badge-secondary';
                    $labelEstadoMis = [
                        'asignada'         => 'Asignada',
                        'en_edicion'       => 'En edición',
                        'enviada_revision'  => 'En revisión',
                        'rechazada'        => 'Rechazada',
                    ][$asig->estado] ?? $asig->estado;
                @endphp
                <tr>
                    <td><code>{{ $asig->rel_entrevista->entrevista_codigo ?? '-' }}</code></td>
                    <td><small>{{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d/m/Y') : '-' }}</small></td>
                    <td>
                        @php $histJsonMis = e(json_encode($asig->historial_comentarios ?: [])); @endphp
                        <span class="badge {{ $badgeClassMis }} js-historial-pop"
                              data-historial="{{ $histJsonMis }}"
                              data-estado="{{ $asig->estado }}"
                              data-comentario="{{ e($asig->comentario_revision ?? '') }}"
                              tabindex="0">{{ $labelEstadoMis }}</span>
                    </td>
                    <td>
                        <a href="{{ route('procesamientos.editar-anonimizacion-asignada', $asig->id_asignacion) }}"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        @if($asig->estado === 'enviada_revision')
                        <a href="{{ route('procesamientos.ver-revision-anonimizacion', $asig->id_asignacion) }}"
                           class="btn btn-sm btn-warning ml-1">
                            <i class="fas fa-eye"></i> Revisar
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Pendientes de Revision --}}
@if($pendientesRevision->count() > 0)
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-inbox mr-2"></i>Anonimizaciones Pendientes de Revision ({{ $pendientesRevision->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>Anonimizador</th>
                    <th>Enviada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendientesRevision as $asignacion)
                <tr>
                    <td><code>{{ $asignacion->rel_entrevista->entrevista_codigo }}</code></td>
                    <td>{{ \Illuminate\Support\Str::limit($asignacion->rel_entrevista->titulo, 40) }}</td>
                    <td>
                        <i class="fas fa-user mr-1"></i>
                        {{ $asignacion->rel_anonimizador->rel_usuario->name ?? 'N/A' }}
                    </td>
                    <td>
                        @if($asignacion->fecha_envio_revision)
                            {{ $asignacion->fecha_envio_revision->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('procesamientos.ver-revision-anonimizacion', $asignacion->id_asignacion) }}"
                           class="btn btn-sm btn-warning">
                            <i class="fas fa-eye"></i> Revisar
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Filtros para Entrevistas con Transcripcion --}}
<div class="card card-outline card-secondary mb-0">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body py-2">
        <form action="{{ route('procesamientos.anonimizacion') }}" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small mb-1">Dependencia</label>
                        <select name="filtro_dependencia" class="form-control form-control-sm">
                            <option value="">-- Todas --</option>
                            @foreach($dependenciasAnonimizacion as $dep)
                                <option value="{{ $dep->id_item }}" {{ request('filtro_dependencia') == $dep->id_item ? 'selected' : '' }}>{{ $dep->descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="small mb-1">Código</label>
                        <input type="text" name="filtro_codigo" class="form-control form-control-sm" value="{{ request('filtro_codigo') }}" placeholder="Buscar código...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-2">
                        <label class="small mb-1">Entrevistador</label>
                        <select name="filtro_entrevistador" class="form-control form-control-sm">
                            <option value="">-- Todos --</option>
                            @foreach($entrevistadoresAnonimizacion as $ent)
                                <option value="{{ $ent->id_entrevistador }}" {{ request('filtro_entrevistador') == $ent->id_entrevistador ? 'selected' : '' }}>{{ $ent->rel_usuario->name ?? 'N/A' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="small mb-1">Entidades</label>
                        <select name="filtro_entidades" class="form-control form-control-sm">
                            <option value="">-- Todas --</option>
                            <option value="con" {{ request('filtro_entidades') === 'con' ? 'selected' : '' }}>Con entidades detectadas</option>
                            <option value="sin" {{ request('filtro_entidades') === 'sin' ? 'selected' : '' }}>Sin detectar</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-2">
                        <label class="small mb-1">Estado Asignación</label>
                        <select name="filtro_asignacion" class="form-control form-control-sm">
                            <option value="">-- Todos --</option>
                            @foreach($estadosAsignacionAnonimizacion as $val => $label)
                                <option value="{{ $val }}" {{ request('filtro_asignacion') === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center">
                <button type="submit" class="btn btn-sm btn-primary mr-2">
                    <i class="fas fa-search mr-1"></i>Filtrar
                </button>
                <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-sm btn-default">
                    <i class="fas fa-eraser mr-1"></i>Limpiar
                </a>
                @if(request()->hasAny(['filtro_dependencia','filtro_codigo','filtro_entrevistador','filtro_entidades','filtro_asignacion']))
                    <span class="badge badge-info ml-2">Filtros activos</span>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        {{-- Lista de Entrevistas para Asignar --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas con Transcripcion</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Titulo</th>
                            <th>Entidades</th>
                            <th>Asignacion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendientes as $entrevista)
                        @php
                            $asignacion = $asignacionesActivas->get($entrevista->id_e_ind_fvt);
                            $tieneEntidades = $entrevista->rel_entidades_count;
                        @endphp
                        <tr>
                            <td><code>{{ $entrevista->entrevista_codigo }}</code></td>
                            <td>
                                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}">
                                    {{ \Illuminate\Support\Str::limit($entrevista->titulo, 35) }}
                                </a>
                            </td>
                            <td>
                                @if($tieneEntidades > 0)
                                    <span class="badge badge-info">{{ $tieneEntidades }} entidades</span>
                                @else
                                    <span class="badge badge-light text-muted">Sin detectar</span>
                                @endif
                            </td>
                            <td>
                                @if($asignacion)
                                    <span class="badge {{ $asignacion->estado_badge_class }}">
                                        {{ $asignacion->estado == 'aprobada' ? 'Finalizada' : $asignacion->fmt_estado }}
                                    </span>
                                    <br>
                                    <small>
                                        <i class="fas fa-user text-muted"></i>
                                        {{ $asignacion->rel_anonimizador->rel_usuario->name ?? 'N/A' }}
                                    </small>
                                    @if($asignacion->estado == 'aprobada' && $asignacion->fecha_revision)
                                    <br>
                                    <small class="text-success">
                                        <i class="fas fa-check"></i>
                                        {{ $asignacion->fecha_revision->format('d/m/Y') }}
                                    </small>
                                    @else
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        {{ $asignacion->fecha_asignacion ? $asignacion->fecha_asignacion->format('d/m/Y H:i') : '-' }}
                                    </small>
                                    @endif
                                @else
                                    <span class="badge badge-light text-muted">Sin asignar</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @if(!$asignacion || $asignacion->estado == 'aprobada')
                                    @php
                                        $documentosData = $entrevista->rel_adjuntos
                                            ->whereIn('id_tipo', [
                                                \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA,
                                                \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL,
                                            ])
                                            ->map(function($a) {
                                                return [
                                                    'id' => $a->id_adjunto,
                                                    'nombre' => $a->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL
                                                        ? 'Transcripción final'
                                                        : 'Transcripción automatizada',
                                                    'tipo' => $a->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL
                                                        ? 'final'
                                                        : 'automatizada',
                                                ];
                                            })->values()->toJson();
                                    @endphp
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="abrirModalAsignar({{ $entrevista->id_e_ind_fvt }}, '{{ $entrevista->entrevista_codigo }}', {!! htmlspecialchars($documentosData, ENT_QUOTES) !!})"
                                            title="{{ $asignacion && $asignacion->estado == 'aprobada' ? 'Reasignar anonimizador' : 'Asignar a anonimizador' }}">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    @endif
                                    @if($entrevista->rel_adjuntos->contains(fn($a) => $a->es_audio || $a->es_video))
                                    <button type="button" class="btn btn-sm btn-dark btn-anonimizar-audio"
                                            data-id="{{ $entrevista->id_e_ind_fvt }}"
                                            data-codigo="{{ $entrevista->entrevista_codigo }}"
                                            title="Anonimizar audio/video">
                                        <i class="fas fa-microphone-slash"></i>
                                    </button>
                                    @endif
                                    @if($asignacion && $asignacion->estado != 'aprobada')
                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="abrirModalDesasignar({{ $asignacion->id_asignacion }}, '{{ $entrevista->entrevista_codigo }}', '{{ $asignacion->estado }}')"
                                            title="Desasignar">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-file-alt fa-2x mb-2"></i><br>
                                No hay entrevistas con transcripcion
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pendientes->hasPages())
            <div class="card-footer">
                {{ $pendientes->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Modal Asignar Anonimizador --}}
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Asignar Anonimizador</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formAsignar">
                <div class="modal-body">
                    <input type="hidden" id="asignar_id_entrevista" name="id_e_ind_fvt">

                    <div class="form-group">
                        <label>Entrevista</label>
                        <input type="text" class="form-control" id="asignar_codigo" readonly>
                    </div>

                    <div class="form-group">
                        <label for="id_anonimizador">Anonimizador <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_anonimizador" name="id_anonimizador" required>
                            <option value="">-- Seleccione --</option>
                            @foreach($anonimizadores as $t)
                            <option value="{{ $t->id_entrevistador }}">
                                {{ $t->rel_usuario->name ?? 'Sin nombre' }} ({{ $t->fmt_numero_entrevistador }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_adjunto">Documento a anonimizar</label>
                        <select class="form-control" id="id_adjunto" name="id_adjunto">
                            <option value="">-- Sin documento asociado --</option>
                        </select>
                        <small class="form-text text-muted">Referencia informativa del documento que se anonimizará.</small>
                    </div>

                    <div id="asignar_error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnAsignar">
                        <i class="fas fa-check mr-1"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Desasignar --}}
<div class="modal fade" id="modalDesasignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-user-minus mr-2"></i>Desasignar Anonimización</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Entrevista: <strong id="desasignar-codigo"></strong></p>
                <div id="desasignar-aviso-trabajo" class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    <strong>Atención:</strong> Esta asignación está en estado <strong id="desasignar-estado-label"></strong>.
                    El trabajo realizado por el anonimizador se perderá.
                </div>
                <p class="text-muted small mb-0">Esta acción no se puede deshacer. La entrevista quedará libre para ser reasignada.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formDesasignar" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-user-minus mr-1"></i> Confirmar Desasignación
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
// Popover historial de revisión
function renderHistorial(historial, estado, comentarioFallback) {
    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    if (!Array.isArray(historial)) historial = [];

    // Si el estado actual (aprobada/rechazada) no está en el historial pero hay
    // comentario_revision, lo inyectamos como entrada sintética para no perderlo.
    var estadosConComentario = ['aprobada', 'rechazada'];
    if (estadosConComentario.indexOf(estado) !== -1) {
        var yaEsta = historial.some(function(e) { return e.accion === estado; });
        if (!yaEsta && comentarioFallback) {
            historial = historial.concat([{ accion: estado, fecha: '', revisor: '', comentario: comentarioFallback }]);
        }
    }

    if (historial.length === 0) {
        return '<em class="text-muted">Sin historial de revisión</em>';
    }
    var html = '';
    historial.slice().reverse().forEach(function(entrada, i) {
        var esRechazo = entrada.accion === 'rechazada';
        var icon  = esRechazo ? 'times-circle text-danger' : 'check-circle text-success';
        var label = esRechazo ? 'Rechazada' : 'Aprobada';
        var fecha = '';
        if (entrada.fecha) {
            try {
                fecha = new Date(entrada.fecha).toLocaleString('es-CO', {
                    day:'2-digit', month:'2-digit', year:'numeric',
                    hour:'2-digit', minute:'2-digit'
                });
            } catch(ex) { fecha = entrada.fecha; }
        }
        var borde = i < historial.length - 1 ? ' border-bottom pb-1 mb-1' : '';
        html += '<div class="' + borde + '">';
        html += '<div class="d-flex justify-content-between">';
        html += '<span><i class="fas fa-' + esc(icon) + ' mr-1"></i><strong>' + esc(label) + '</strong>';
        if (entrada.revisor) html += ' <small class="text-muted">por ' + esc(entrada.revisor) + '</small>';
        html += '</span>';
        if (fecha) html += '<small class="text-muted ml-2">' + esc(fecha) + '</small>';
        html += '</div>';
        if (entrada.comentario) html += '<div class="text-muted mt-1">' + esc(entrada.comentario) + '</div>';
        html += '</div>';
    });
    return html;
}

$(function() {
    $('body').on('click', '.js-historial-pop', function() {
        var $el = $(this);
        // Si el popover ya está visible, cerrarlo (toggle)
        if ($el.data('bs.popover') && $el.data('bs.popover').tip && $el.data('bs.popover').tip.hasClass('show')) {
            $el.popover('hide');
            return;
        }
        // Cerrar cualquier otro popover abierto
        $('.js-historial-pop').not($el).popover('hide');

        if (!$el.data('popover-init')) {
            $el.data('popover-init', true);
            $el.popover({
                html:      true,
                trigger:   'manual',
                placement: 'left',
                container: 'body',
                template:  '<div class="popover historial-popover" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
                title:     '<i class="fas fa-history mr-1"></i>Historial de revisión',
                content:   function() {
                    return renderHistorial(
                        $(this).data('historial'),
                        $(this).attr('data-estado'),
                        $(this).attr('data-comentario')
                    );
                }
            });
        }
        $el.popover('show');
    });

    // Cerrar al hacer click fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.js-historial-pop, .popover').length) {
            $('.js-historial-pop').popover('hide');
        }
    });
});

function abrirModalDesasignar(idAsignacion, codigo, estado) {
    var etiquetas = {
        asignada: 'Asignada',
        en_edicion: 'En Edición',
        enviada_revision: 'Enviada a Revisión',
        rechazada: 'Rechazada'
    };
    $('#desasignar-codigo').text(codigo);
    $('#desasignar-estado-label').text(etiquetas[estado] || estado);
    $('#formDesasignar').attr('action', '{{ url("procesamientos/asignacion-anonimizacion") }}/' + idAsignacion + '/desasignar');
    $('#modalDesasignar').modal('show');
}

function abrirModalAsignar(id, codigo, documentos) {
    $('#asignar_id_entrevista').val(id);
    $('#asignar_codigo').val(codigo);
    $('#asignar_error').addClass('d-none');
    $('#id_anonimizador').val('');

    documentos = documentos || [];
    var $sel = $('#id_adjunto').empty().append('<option value="">-- Sin documento asociado --</option>');
    documentos.forEach(function(doc) {
        $sel.append($('<option>').val(doc.id).text(doc.nombre));
    });
    // Preseleccionar la transcripción final si existe, si no la automatizada
    var final = documentos.find(function(d) { return d.tipo === 'final'; });
    $sel.val(final ? final.id : (documentos[0] ? documentos[0].id : ''));

    $('#modalAsignar').modal('show');
}

$('#formAsignar').on('submit', function(e) {
    e.preventDefault();

    var btn = $('#btnAsignar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asignando...');
    $('#asignar_error').addClass('d-none');

    $.ajax({
        url: '{{ route("procesamientos.asignar-anonimizacion") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_e_ind_fvt: $('#asignar_id_entrevista').val(),
            id_anonimizador: $('#id_anonimizador').val(),
            id_adjunto: $('#id_adjunto').val()
        },
        success: function(response) {
            $('#modalAsignar').modal('hide');
            location.reload();
        },
        error: function(xhr) {
            var msg = xhr.responseJSON?.error || 'Error al asignar';
            $('#asignar_error').removeClass('d-none').text(msg);
            btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Asignar');
        }
    });
});

$(document).ready(function() {
    // Anonimizar audio
    $('.btn-anonimizar-audio').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('id');
        var codigo = $btn.data('codigo');

        if (!confirm('¿Anonimizar audio/video de ' + codigo + '?\n\nSe creara una copia con voz distorsionada. Este proceso puede tomar varios segundos.')) {
            return;
        }

        var htmlOriginal = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("procesamientos/anonimizacion") }}/' + id + '/anonimizar-audio',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    $btn.removeClass('btn-dark').addClass('btn-success')
                        .html('<i class="fas fa-check"></i>');
                    alert('Audio anonimizado: ' + response.procesados + ' archivo(s) procesado(s).');
                    location.reload();
                } else {
                    alert('Error: ' + (response.error || 'Error desconocido'));
                    $btn.prop('disabled', false).html(htmlOriginal);
                }
            },
            error: function(xhr) {
                var msg = xhr.responseJSON?.error || 'Error al anonimizar audio';
                alert('Error: ' + msg);
                $btn.prop('disabled', false).html(htmlOriginal);
            }
        });
    });
});
</script>
@endsection
