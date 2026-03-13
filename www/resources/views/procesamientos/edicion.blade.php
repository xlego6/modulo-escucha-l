@extends('layouts.app')

@section('title', 'Edicion de Transcripciones')
@section('content_header', 'Edicion de Transcripciones')

@section('css')
<style>
.stat-block { border-radius:6px; padding:10px 12px; color:#fff; min-height:80px; display:flex; flex-direction:column; justify-content:space-between; }
.stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; opacity:.85; }
.stat-main  { font-size:28px; font-weight:700; line-height:1.1; }
.stat-row   { font-size:11px; opacity:.9; display:flex; justify-content:space-between; flex-wrap:wrap; gap:4px; margin-top:2px; }
.bg-procesadas  { background: linear-gradient(135deg,#2e7d32,#43a047); }
.bg-asignadas   { background: linear-gradient(135deg,#37474f,#546e7a); }
.bg-en-edicion  { background: linear-gradient(135deg,#1565c0,#1e88e5); }
.bg-en-revision { background: linear-gradient(135deg,#e65100,#fb8c00); }
.bg-rechazadas  { background: linear-gradient(135deg,#b71c1c,#e53935); }
.bg-aprobadas   { background: linear-gradient(135deg,#1b5e20,#388e3c); }
.bg-totales     { background: linear-gradient(135deg,#4a148c,#7b1fa2); }
</style>
@endsection

@section('content')
{{-- Bloques estadísticos --}}
@php
    $bt = $stats;
    function fmtDurEd($s) {
        if (!$s) return '0m';
        $h = intdiv($s, 3600); $m = intdiv($s % 3600, 60);
        return $h ? "{$h}h {$m}m" : "{$m}m";
    }
@endphp
<div class="row mb-3">
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-procesadas">
            <div class="stat-label">Procesadas</div>
            <div class="stat-main">{{ number_format($bt['procesadas']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['procesadas']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['procesadas']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-asignadas">
            <div class="stat-label">Asignadas</div>
            <div class="stat-main">{{ number_format($bt['asignada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['asignada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['asignada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-edicion">
            <div class="stat-label">En edición</div>
            <div class="stat-main">{{ number_format($bt['en_edicion']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['en_edicion']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['en_edicion']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-revision">
            <div class="stat-label">En revisión</div>
            <div class="stat-main">{{ number_format($bt['enviada_revision']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['enviada_revision']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['enviada_revision']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-rechazadas">
            <div class="stat-label">Rechazadas</div>
            <div class="stat-main">{{ number_format($bt['rechazada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['rechazada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['rechazada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-aprobadas">
            <div class="stat-label">Aprobadas</div>
            <div class="stat-main">{{ number_format($bt['aprobada']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['aprobada']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['aprobada']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-totales">
            <div class="stat-label">Totales</div>
            <div class="stat-main">{{ number_format($bt['totales']['cantidad_entrevistas']) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-music"></i> {{ number_format($bt['totales']['cantidad_audios']) }}</span>
                <span><i class="fas fa-clock"></i> {{ fmtDurEd($bt['totales']['duracion_total']) }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Mis transcripciones asignadas (solo Líder) --}}
@if(isset($misAsignaciones) && $misAsignaciones->isNotEmpty())
<div class="card card-primary card-outline mb-3">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Mis transcripciones asignadas ({{ $misAsignaciones->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Código</th>
                    <th>Audio asignado</th>
                    <th>Asignada</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($misAsignaciones as $asig)
                @php
                    $badgeClass = [
                        'asignada'         => 'badge-secondary',
                        'en_edicion'       => 'badge-info',
                        'enviada_revision'  => 'badge-warning',
                        'rechazada'        => 'badge-danger',
                    ][$asig->estado] ?? 'badge-secondary';
                    $labelEstado = [
                        'asignada'         => 'Asignada',
                        'en_edicion'       => 'En edición',
                        'enviada_revision'  => 'En revisión',
                        'rechazada'        => 'Rechazada',
                    ][$asig->estado] ?? $asig->estado;
                @endphp
                <tr>
                    <td><code>{{ $asig->rel_entrevista->entrevista_codigo ?? '-' }}</code></td>
                    <td>
                        @if($asig->id_adjunto && $asig->rel_adjunto)
                            <small><i class="fas fa-file-audio text-info mr-1"></i>{{ \Illuminate\Support\Str::limit($asig->rel_adjunto->nombre_original, 40) }}</small>
                        @else
                            <small class="text-muted">Entrevista completa</small>
                        @endif
                    </td>
                    <td><small>{{ $asig->fecha_asignacion ? \Carbon\Carbon::parse($asig->fecha_asignacion)->format('d/m/Y') : '-' }}</small></td>
                    <td><span class="badge {{ $badgeClass }}">{{ $labelEstado }}</span></td>
                    <td>
                        <a href="{{ route('procesamientos.editar-transcripcion-asignada', $asig->id_asignacion) }}"
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        @if($asig->estado === 'enviada_revision')
                        <a href="{{ route('procesamientos.ver-revision', $asig->id_asignacion) }}"
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

{{-- Pendientes de Revisión --}}
@if($pendientesRevision->count() > 0)
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-inbox mr-2"></i>Transcripciones Pendientes de Revision ({{ $pendientesRevision->count() }})</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo / Audio</th>
                    <th>Transcriptor</th>
                    <th>Enviada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendientesRevision as $asignacion)
                <tr>
                    <td><code>{{ $asignacion->rel_entrevista->entrevista_codigo }}</code></td>
                    <td>
                        {{ \Illuminate\Support\Str::limit($asignacion->rel_entrevista->titulo, 35) }}
                        @if($asignacion->id_adjunto && $asignacion->rel_adjunto)
                            <br><small class="text-muted"><i class="fas fa-file-audio"></i> {{ \Illuminate\Support\Str::limit($asignacion->rel_adjunto->nombre_original, 30) }}</small>
                        @endif
                    </td>
                    <td>
                        <i class="fas fa-user mr-1"></i>
                        {{ $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A' }}
                    </td>
                    <td>
                        @if($asignacion->fecha_envio_revision)
                            {{ $asignacion->fecha_envio_revision->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('procesamientos.ver-revision', $asignacion->id_asignacion) }}"
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

{{-- Lista de Entrevistas para Asignar --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas con Audio/Video</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>Audios</th>
                    <th>Trans. Auto</th>
                    <th>Asignacion por Audio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendientes as $entrevista)
                @php
                    $asigs = $asignacionesPorEntrevista->get($entrevista->id_e_ind_fvt, collect());
                    // IDs de adjuntos con asignación activa (no aprobada)
                    $adjuntosAsignados = $asigs->whereNotIn('estado', ['aprobada'])->pluck('id_adjunto')->filter()->toArray();
                    // ¿Hay al menos un audio sin asignación activa?
                    $hayAudioLibre = $entrevista->rel_adjuntos->contains(fn($a) => !in_array($a->id_adjunto, $adjuntosAsignados));
                    $audiosTranscritos = $entrevista->rel_adjuntos->filter(fn($a) => !empty($a->texto_extraido))->count();
                    $totalAudios = $entrevista->rel_adjuntos->count();
                @endphp
                <tr>
                    <td><code>{{ $entrevista->entrevista_codigo }}</code></td>
                    <td>
                        <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}">
                            {{ \Illuminate\Support\Str::limit($entrevista->titulo, 40) }}
                        </a>
                    </td>
                    <td>
                        <span class="badge badge-info">
                            {{ $totalAudios }} audio(s)
                        </span>
                    </td>
                    <td>
                        @if($audiosTranscritos === $totalAudios && $totalAudios > 0)
                            <span class="badge badge-success">
                                <i class="fas fa-check mr-1"></i>Completa
                            </span>
                        @elseif($audiosTranscritos > 0)
                            <span class="badge badge-warning">
                                {{ $audiosTranscritos }}/{{ $totalAudios }}
                            </span>
                        @else
                            <span class="badge badge-secondary">Sin texto</span>
                        @endif
                    </td>
                    <td>
                        @if($asigs->isEmpty())
                            <span class="badge badge-light text-muted">Sin asignar</span>
                        @else
                            @foreach($entrevista->rel_adjuntos as $adjunto)
                            @php
                                $asigAdjunto = $asigs->firstWhere('id_adjunto', $adjunto->id_adjunto);
                            @endphp
                            <div class="mb-1">
                                <small class="text-muted" title="{{ $adjunto->nombre_original }}">
                                    <i class="fas fa-file-audio"></i>
                                    {{ \Illuminate\Support\Str::limit($adjunto->nombre_original, 22) }}
                                </small>
                                @if($asigAdjunto)
                                    <span class="badge {{ $asigAdjunto->estado_badge_class }}">
                                        {{ $asigAdjunto->fmt_estado }}
                                    </span>
                                    <small class="text-muted">{{ $asigAdjunto->rel_transcriptor->rel_usuario->name ?? '' }}</small>
                                @else
                                    <span class="badge badge-light">Sin asignar</span>
                                @endif
                            </div>
                            @endforeach
                        @endif
                    </td>
                    <td>
                        <div class="btn-group">
                            @if(\App\Models\RolModuloPermiso::puedeEditar(Auth::user()->id_nivel, 'procesamientos.transcripcion'))
                            <a href="{{ route('procesamientos.editar-transcripcion', $entrevista->id_e_ind_fvt) }}"
                               class="btn btn-sm btn-primary" title="Editar directamente">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if($hayAudioLibre)
                            @php
                                $adjuntosData = $entrevista->rel_adjuntos->map(function($a) use ($adjuntosAsignados) {
                                    return [
                                        'id' => $a->id_adjunto,
                                        'nombre' => $a->nombre_original,
                                        'asignado' => in_array($a->id_adjunto, $adjuntosAsignados),
                                    ];
                                })->values()->toJson();
                            @endphp
                            <button type="button" class="btn btn-sm btn-info"
                                    onclick="abrirModalAsignar({{ $entrevista->id_e_ind_fvt }}, '{{ $entrevista->entrevista_codigo }}', {!! htmlspecialchars($adjuntosData, ENT_QUOTES) !!})"
                                    title="Asignar audio a transcriptor">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-file-audio fa-2x mb-2"></i><br>
                        No hay entrevistas con archivos de audio/video
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

{{-- Modal Asignar Transcriptor --}}
<div class="modal fade" id="modalAsignar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Asignar Transcriptor</h5>
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
                        <label for="id_adjunto">Audio a transcribir <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_adjunto" name="id_adjunto" required>
                            <option value="">-- Seleccione el audio --</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_transcriptor">Transcriptor / Líder <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_transcriptor" name="id_transcriptor" required>
                            <option value="">-- Seleccione --</option>
                            @foreach($transcriptores as $t)
                            <option value="{{ $t->id_entrevistador }}">
                                {{ $t->rel_usuario->name ?? 'Sin nombre' }} ({{ $t->fmt_numero_entrevistador }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div id="asignar_error" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info" id="btnAsignar">
                        <i class="fas fa-check mr-1"></i> Asignar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
function abrirModalAsignar(id, codigo, adjuntosJson) {
    var adjuntos = typeof adjuntosJson === 'string' ? JSON.parse(adjuntosJson) : adjuntosJson;

    $('#asignar_id_entrevista').val(id);
    $('#asignar_codigo').val(codigo);
    $('#asignar_error').addClass('d-none');
    $('#id_transcriptor').val('');

    // Poblar selector de audios
    var $sel = $('#id_adjunto').empty().append('<option value="">-- Seleccione el audio --</option>');
    adjuntos.forEach(function(adj) {
        if (!adj.asignado) {
            $sel.append('<option value="' + adj.id + '">' + adj.nombre + '</option>');
        }
    });

    $('#modalAsignar').modal('show');
}

$('#formAsignar').on('submit', function(e) {
    e.preventDefault();

    var btn = $('#btnAsignar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asignando...');
    $('#asignar_error').addClass('d-none');

    $.ajax({
        url: '{{ route("procesamientos.asignar") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_e_ind_fvt: $('#asignar_id_entrevista').val(),
            id_adjunto: $('#id_adjunto').val(),
            id_transcriptor: $('#id_transcriptor').val()
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
</script>
@endsection
