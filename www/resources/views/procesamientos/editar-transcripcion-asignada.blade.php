@extends('layouts.app')

@section('title', 'Editar Transcripcion')
@section('content_header')
Editar Transcripcion: {{ $entrevista->entrevista_codigo }}
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
    {{-- Panel izquierdo: Información y Audio --}}
    <div class="col-md-4">
        {{-- Estado de la asignación --}}
        <div class="card">
            <div class="card-header bg-{{ $asignacion->estado == 'rechazada' ? 'danger' : 'info' }}">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Estado de la Asignacion</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Estado:</dt>
                    <dd class="col-sm-7">
                        <span class="badge {{ $asignacion->estado_badge_class }}">
                            {{ $asignacion->fmt_estado }}
                        </span>
                    </dd>

                    <dt class="col-sm-5">Asignada:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</dd>

                    @if($asignacion->fecha_inicio_edicion)
                    <dt class="col-sm-5">Inicio Edicion:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_inicio_edicion->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>

                @if($asignacion->estado == 'rechazada' && $asignacion->comentario_revision)
                <div class="alert alert-danger mt-3 mb-0">
                    <strong><i class="fas fa-exclamation-triangle mr-1"></i> Motivo del rechazo:</strong><br>
                    {{ $asignacion->comentario_revision }}
                </div>
                @endif
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
                        Su navegador no soporta audio HTML5
                    </audio>
                    @elseif(strpos($adjunto->tipo_mime, 'video') !== false)
                    <video controls class="w-100" preload="metadata">
                        <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                        Su navegador no soporta video HTML5
                    </video>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Panel derecho: Editor de transcripción --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-keyboard mr-2"></i>Transcripcion</h3>
            </div>
            <form action="{{ route('procesamientos.guardar-asignacion', $asignacion->id_asignacion) }}" method="POST" id="formTranscripcion">
                @csrf
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'transcripcion'])
                    <textarea name="transcripcion" id="transcripcion" class="form-control"
                              style="min-height: 500px; resize: vertical; font-family: monospace;"
                              placeholder="Escriba aqui la transcripcion del audio...">{{ old('transcripcion', $asignacion->transcripcion_editada ?? $entrevista->getTextoParaProcesamiento()) }}</textarea>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Guardar Borrador
                            </button>
                            <a href="{{ route('procesamientos.edicion') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                        </div>
                        <div class="col-md-6 text-right">
                            @if(in_array($asignacion->estado, ['asignada', 'en_edicion', 'rechazada']))
                            <button type="button" class="btn btn-success" onclick="enviarARevision()">
                                <i class="fas fa-paper-plane mr-1"></i> Enviar a Revision
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Transcripción automática (si existe) --}}
        @php $transcripcionAuto = $entrevista->getTextoParaProcesamiento(); @endphp
        @if($transcripcionAuto && $asignacion->transcripcion_editada != $transcripcionAuto)
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-robot mr-2"></i>Transcripcion Automatica (Referencia)</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="display: none;">
                <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9em;">{{ $transcripcionAuto }}</pre>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Modal confirmar envío --}}
<div class="modal fade" id="modalEnviar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-paper-plane mr-2"></i>Enviar a Revision</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>¿Esta seguro de enviar la transcripcion a revision?</p>
                <p class="text-muted small">
                    Una vez enviada, no podra editarla hasta que sea revisada por un lider.
                    Si es rechazada, podra corregirla y enviarla nuevamente.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form action="{{ route('procesamientos.enviar-revision', $asignacion->id_asignacion) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane mr-1"></i> Confirmar Envio
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
@include('partials.editor-toolbar-js')
<script>
function enviarARevision() {
    // Verificar que hay contenido
    var texto = $('#transcripcion').val().trim();
    if (texto.length < 50) {
        alert('La transcripcion debe tener al menos 50 caracteres');
        return;
    }

    // Guardar primero y luego mostrar modal
    $('#formTranscripcion').one('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function() {
                $('#modalEnviar').modal('show');
            },
            error: function() {
                alert('Error al guardar. Intente nuevamente.');
            }
        });
    });

    $('#formTranscripcion').submit();
}

// Auto-guardar cada 60 segundos
var autoSaveInterval = setInterval(function() {
    var form = $('#formTranscripcion');
    $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: form.serialize(),
        success: function() {
            console.log('Auto-guardado: ' + new Date().toLocaleTimeString());
        }
    });
}, 60000);
</script>
@endsection
