@extends('layouts.app')

@section('title', 'Anonimizacion')
@section('content_header', 'Anonimizacion de Testimonios')

@section('content')
{{-- Estadisticas --}}
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Pendientes</span>
                <span class="info-box-number">{{ $stats['pendientes'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-secondary">
            <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Asignadas</span>
                <span class="info-box-number">{{ $stats['asignadas'] }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En Revision</span>
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
                            $tieneEntidades = \App\Models\EntidadDetectada::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)->count();
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
                                    <a href="{{ route('procesamientos.previsualizar-anonimizacion', $entrevista->id_e_ind_fvt) }}"
                                       class="btn btn-sm {{ $asignacion && $asignacion->estado == 'aprobada' ? 'btn-success' : 'btn-danger' }}"
                                       title="{{ $asignacion && $asignacion->estado == 'aprobada' ? 'Ver anonimizacion final' : 'Previsualizar/Editar anonimizacion' }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if(!$asignacion || $asignacion->estado == 'aprobada')
                                    <button type="button" class="btn btn-sm btn-info"
                                            onclick="abrirModalAsignar({{ $entrevista->id_e_ind_fvt }}, '{{ $entrevista->entrevista_codigo }}')"
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
                        <label>Tipos a anonimizar</label>
                        <div class="row">
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-PER" value="PER" checked>
                                    <label class="custom-control-label" for="tipo-PER">
                                        <span class="badge badge-primary">PER</span> Personas
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-LOC" value="LOC" checked>
                                    <label class="custom-control-label" for="tipo-LOC">
                                        <span class="badge badge-success">LOC</span> Lugares
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-ORG" value="ORG">
                                    <label class="custom-control-label" for="tipo-ORG">
                                        <span class="badge badge-info">ORG</span> Organizaciones
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-DATE" value="DATE">
                                    <label class="custom-control-label" for="tipo-DATE">
                                        <span class="badge badge-secondary">DATE</span> Fechas
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-EVENT" value="EVENT">
                                    <label class="custom-control-label" for="tipo-EVENT">
                                        <span class="badge badge-warning">EVENT</span> Eventos
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-GUN" value="GUN">
                                    <label class="custom-control-label" for="tipo-GUN">
                                        <span class="badge badge-danger">GUN</span> Armas
                                    </label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input tipo-check" id="tipo-MISC" value="MISC">
                                    <label class="custom-control-label" for="tipo-MISC">
                                        <span class="badge badge-dark">MISC</span> Otros
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Formato de reemplazo</label>
                        <select class="form-control" id="formato_reemplazo" name="formato_reemplazo">
                            <option value="brackets">[TIPO] - Ej: [PER], [LOC]</option>
                            <option value="numbered">[TIPO_N] - Ej: [PER_1], [LOC_2]</option>
                            <option value="redacted">[REDACTADO]</option>
                            <option value="asterisks">***</option>
                        </select>
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
@endsection

@section('js')
<script>
function abrirModalAsignar(id, codigo) {
    $('#asignar_id_entrevista').val(id);
    $('#asignar_codigo').val(codigo);
    $('#asignar_error').addClass('d-none');
    $('#id_anonimizador').val('');
    $('#modalAsignar').modal('show');
}

$('#formAsignar').on('submit', function(e) {
    e.preventDefault();

    var btn = $('#btnAsignar');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Asignando...');
    $('#asignar_error').addClass('d-none');

    // Obtener tipos seleccionados
    var tipos = [];
    $('.tipo-check:checked').each(function() {
        tipos.push($(this).val());
    });

    $.ajax({
        url: '{{ route("procesamientos.asignar-anonimizacion") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            id_e_ind_fvt: $('#asignar_id_entrevista').val(),
            id_anonimizador: $('#id_anonimizador').val(),
            tipos_anonimizar: tipos.join(','),
            formato_reemplazo: $('#formato_reemplazo').val()
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
