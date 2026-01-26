@extends('layouts.app')

@section('title', 'Edicion de Transcripciones')
@section('content_header', 'Edicion de Transcripciones')

@section('content')
{{-- Estadísticas --}}
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-file-audio"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Entrevistas</span>
                <span class="info-box-number">{{ $stats['pendientes'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-user-edit"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En Proceso</span>
                <span class="info-box-number">{{ $stats['asignadas'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Pendientes Revision</span>
                <span class="info-box-number">{{ $stats['en_revision'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Finalizadas</span>
                <span class="info-box-number">{{ $stats['aprobadas'] }}</span>
            </div>
        </div>
    </div>
</div>

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
                    <th>Titulo</th>
                    <th>Transcriptor</th>
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
                    <th>Adjuntos</th>
                    <th>Asignacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pendientes as $entrevista)
                @php
                    $asignacion = $asignacionesActivas->get($entrevista->id_e_ind_fvt);
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
                            {{ $entrevista->rel_adjuntos->count() }} adjuntos
                        </span>
                    </td>
                    <td>
                        @if($asignacion)
                            @if($asignacion->estado === 'aprobada')
                                {{-- Estado Finalizado --}}
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Finalizada
                                </span>
                                <br>
                                <small class="text-success">
                                    <i class="fas fa-user-check"></i>
                                    {{ $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A' }}
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-check"></i>
                                    {{ $asignacion->fecha_revision ? $asignacion->fecha_revision->format('d/m/Y H:i') : '-' }}
                                </small>
                                @if($asignacion->rel_revisor)
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-stamp"></i> Aprobado por: {{ $asignacion->rel_revisor->name }}
                                </small>
                                @endif
                            @else
                                {{-- Estados activos --}}
                                <span class="badge {{ $asignacion->estado_badge_class }}">
                                    {{ $asignacion->fmt_estado }}
                                </span>
                                <br>
                                <small>
                                    <i class="fas fa-user text-muted"></i>
                                    {{ $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A' }}
                                </small>
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
                            <a href="{{ route('procesamientos.editar-transcripcion', $entrevista->id_e_ind_fvt) }}"
                               class="btn btn-sm btn-primary" title="Editar directamente">
                                <i class="fas fa-edit"></i>
                            </a>
                            @if(!$asignacion || $asignacion->estado === 'aprobada')
                            <button type="button" class="btn btn-sm {{ $asignacion && $asignacion->estado === 'aprobada' ? 'btn-outline-info' : 'btn-info' }}"
                                    onclick="abrirModalAsignar({{ $entrevista->id_e_ind_fvt }}, '{{ $entrevista->entrevista_codigo }}')"
                                    title="{{ $asignacion && $asignacion->estado === 'aprobada' ? 'Reasignar transcripción' : 'Asignar a transcriptor' }}">
                                <i class="fas fa-user-plus"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
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
                        <label for="id_transcriptor">Transcriptor <span class="text-danger">*</span></label>
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
function abrirModalAsignar(id, codigo) {
    $('#asignar_id_entrevista').val(id);
    $('#asignar_codigo').val(codigo);
    $('#asignar_error').addClass('d-none');
    $('#id_transcriptor').val('');
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
