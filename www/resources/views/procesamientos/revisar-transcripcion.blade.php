@extends('layouts.app')

@section('title', 'Revisar Transcripcion')
@section('content_header')
Revisar Transcripcion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    .editor-toolbar {
        background: #f8f9fa;
        padding: 8px;
        border-radius: 4px 4px 0 0;
        border: 1px solid #dee2e6;
        border-bottom: none;
    }
    .editor-toolbar .btn {
        padding: 0.25rem 0.5rem;
    }
    #transcripcion {
        border-radius: 0 0 4px 4px !important;
    }
    /* Vista previa de transcripcion */
    .preview-content {
        border: 1px solid #dee2e6;
        border-radius: 0 0 4px 4px;
        padding: 12px 16px;
        font-family: monospace;
        font-size: 14px;
        line-height: 1.6;
        background: #fff;
        overflow-y: auto;
    }
    .preview-content p { margin: 0 0 0.3em; }
    .preview-content h4 { color: #2d3748; margin: 0.8em 0 0.3em; }
    .preview-content blockquote {
        border-left: 3px solid #6c757d;
        padding-left: 10px;
        color: #555;
        margin: 0.3em 0;
    }
    .preview-content ul { margin: 0.3em 0; padding-left: 24px; }
    .preview-speaker {
        background: #d1ecf1;
        color: #0c5460;
        padding: 1px 6px;
        border-radius: 3px;
        font-weight: bold;
    }
    .preview-timestamp {
        background: #e2e3e5;
        color: #383d41;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 0.85em;
    }
    .preview-mark {
        background: #fff3cd;
        color: #856404;
        padding: 1px 5px;
        border-radius: 3px;
        font-style: italic;
    }
</style>
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
            </div>
            <form action="{{ route('procesamientos.guardar-asignacion', $asignacion->id_asignacion) }}" method="POST" id="formTranscripcion">
                @csrf
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'transcripcion'])
                    <textarea name="transcripcion" id="transcripcion" class="form-control"
                              style="min-height: 500px; resize: vertical; font-family: monospace;"
                              placeholder="Edite la transcripcion...">{{ $asignacion->transcripcion_editada }}</textarea>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
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
@include('partials.editor-toolbar-js')
<script>
$(document).ready(function() {
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
