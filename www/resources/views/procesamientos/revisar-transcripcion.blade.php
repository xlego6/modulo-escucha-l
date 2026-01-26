@extends('layouts.app')

@section('title', 'Revisar Transcripcion')
@section('content_header')
Revisar Transcripcion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('content')
<div class="row">
    {{-- Panel izquierdo: Información --}}
    <div class="col-md-4">
        {{-- Información de la asignación --}}
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Revision Pendiente</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Transcriptor:</dt>
                    <dd class="col-sm-7">
                        <i class="fas fa-user mr-1"></i>
                        {{ $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A' }}
                    </dd>

                    <dt class="col-sm-5">Asignada por:</dt>
                    <dd class="col-sm-7">{{ $asignacion->rel_asignado_por->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Fecha Asignacion:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5">Fecha Envio:</dt>
                    <dd class="col-sm-7">
                        @if($asignacion->fecha_envio_revision)
                            {{ $asignacion->fecha_envio_revision->format('d/m/Y H:i') }}
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        {{-- Información de la entrevista --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Datos de la Entrevista</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codigo:</dt>
                    <dd class="col-sm-8"><code>{{ $entrevista->entrevista_codigo }}</code></dd>

                    <dt class="col-sm-4">Titulo:</dt>
                    <dd class="col-sm-8">{{ $entrevista->titulo }}</dd>

                    <dt class="col-sm-4">Fecha:</dt>
                    <dd class="col-sm-8">{{ $entrevista->entrevista_fecha ? \Carbon\Carbon::parse($entrevista->entrevista_fecha)->format('d/m/Y') : '-' }}</dd>
                </dl>
                <hr>
                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-sm btn-outline-info" target="_blank">
                    <i class="fas fa-external-link-alt mr-1"></i> Ver entrevista completa
                </a>
            </div>
        </div>

        {{-- Reproductor de Audio --}}
        @if($entrevista->rel_adjuntos && $entrevista->rel_adjuntos->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-headphones mr-2"></i>Audio/Video</h3>
            </div>
            <div class="card-body">
                @foreach($entrevista->rel_adjuntos as $adjunto)
                <div class="mb-3">
                    <label class="d-block">{{ $adjunto->nombre_original }}</label>
                    @if(strpos($adjunto->tipo_mime, 'audio') !== false)
                    <audio controls class="w-100" preload="metadata">
                        <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                    </audio>
                    @elseif(strpos($adjunto->tipo_mime, 'video') !== false)
                    <video controls class="w-100" preload="metadata">
                        <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                    </video>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Botones de acción --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-2"></i>Decision</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('procesamientos.aprobar-asignacion', $asignacion->id_asignacion) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <label>Comentario (opcional)</label>
                        <textarea name="comentario" class="form-control" rows="2" placeholder="Comentario de aprobacion..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" onclick="return confirm('¿Aprobar esta transcripcion como version final?')">
                        <i class="fas fa-check mr-1"></i> Aprobar Transcripcion
                    </button>
                </form>

                <hr>

                <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalRechazar">
                    <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                </button>

                <hr>

                <a href="{{ route('procesamientos.edicion') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Panel derecho: Transcripción (Editable) --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Transcripcion (Editable)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleEdit">
                        <i class="fas fa-eye"></i> Ver como texto
                    </button>
                </div>
            </div>
            <form action="{{ route('procesamientos.guardar-asignacion', $asignacion->id_asignacion) }}" method="POST" id="formTranscripcion">
                @csrf
                <div class="card-body p-0">
                    {{-- Editor --}}
                    <textarea name="transcripcion" id="transcripcion" class="form-control border-0"
                              style="min-height: 500px; resize: vertical; font-family: monospace;">{{ $asignacion->transcripcion_editada }}</textarea>
                    {{-- Vista previa (oculta por defecto) --}}
                    <div id="vistaPrevia" style="display: none; max-height: 500px; overflow-y: auto; padding: 15px;">
                        <pre style="white-space: pre-wrap; font-family: 'Segoe UI', sans-serif; font-size: 0.95em; margin: 0;">{{ $asignacion->transcripcion_editada }}</pre>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
                    <span class="text-muted ml-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="charCount">{{ strlen($asignacion->transcripcion_editada) }}</span> caracteres
                    </span>
                </div>
            </form>
        </div>

        {{-- Comparar con transcripción automática --}}
        @php $transcripcionAuto = $entrevista->getTextoParaProcesamiento(); @endphp
        @if($transcripcionAuto)
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-robot mr-2"></i>Transcripcion Automatica Original (Comparar)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="display: none;">
                <div style="max-height: 400px; overflow-y: auto; background: #fff3cd; padding: 15px; border-radius: 4px;">
                    <pre style="white-space: pre-wrap; font-size: 0.9em; margin: 0;">{{ $transcripcionAuto }}</pre>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-times mr-2"></i>Rechazar Transcripcion</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('procesamientos.rechazar-asignacion', $asignacion->id_asignacion) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <p class="text-muted">
                        Indique el motivo del rechazo. El transcriptor recibira este comentario
                        y podra corregir la transcripcion.
                    </p>
                    <div class="form-group">
                        <label>Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="comentario" class="form-control" rows="4" required
                                  placeholder="Ej: Hay errores de ortografia en varios parrafos. Revisar la seccion donde habla del evento del 15 de marzo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    var editMode = true;

    // Toggle entre edición y vista previa
    $('#btnToggleEdit').on('click', function() {
        editMode = !editMode;

        if (editMode) {
            $('#transcripcion').show();
            $('#vistaPrevia').hide();
            $(this).html('<i class="fas fa-eye"></i> Ver como texto');
        } else {
            // Actualizar vista previa con contenido actual
            $('#vistaPrevia pre').text($('#transcripcion').val());
            $('#transcripcion').hide();
            $('#vistaPrevia').show();
            $(this).html('<i class="fas fa-edit"></i> Modo edicion');
        }
    });

    // Actualizar contador de caracteres
    $('#transcripcion').on('input', function() {
        $('#charCount').text($(this).val().length);
    });

    // Confirmación antes de salir si hay cambios sin guardar
    var originalContent = $('#transcripcion').val();
    var hasChanges = false;

    $('#transcripcion').on('input', function() {
        hasChanges = ($(this).val() !== originalContent);
    });

    $('#formTranscripcion').on('submit', function() {
        hasChanges = false;
    });

    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'Tiene cambios sin guardar. ¿Desea salir de la pagina?';
        }
    });
});
</script>
@endsection
