@extends('layouts.app')

@section('title', 'Revisar Transcripcion')
@section('content_header')
Revisar Transcripcion: {{ $entrevista->entrevista_codigo }}
@if($asignacion->id_adjunto && $asignacion->rel_adjunto)
 &mdash; {{ $asignacion->rel_adjunto->nombre_original }}
@endif
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
        min-height: calc(100vh - 300px);
        resize: vertical;
        font-family: monospace;
    }
    /* Vista previa de transcripcion */
    .preview-content {
        border: 1px solid #dee2e6;
        border-radius: 0 0 4px 4px;
        padding: 12px 16px;
        font-family: 'Barlow', sans-serif;
        font-size: 14px;
        line-height: 1.6;
        background: #fff;
        overflow-y: auto;
    }
    .preview-content p { margin: 0 0 0.5em; }
    .preview-h2 {
        font-size: 1.3em; font-weight: 700;
        color: #1a202c;
        border-bottom: 2px solid #ebc01a;
        padding-bottom: 3px;
        margin: 1em 0 0.4em;
    }
    .preview-h3 {
        font-size: 1.1em; font-weight: 700;
        color: #2d3748;
        border-left: 3px solid #ebc01a;
        padding-left: 8px;
        margin: 0.9em 0 0.3em;
    }
    .preview-h4 {
        font-size: 1em; font-weight: 700;
        color: #4a5568;
        margin: 0.8em 0 0.3em;
    }
    .preview-content blockquote {
        border-left: 3px solid #6c757d;
        padding-left: 10px;
        color: #555;
        margin: 0.3em 0;
    }
    .preview-ul { margin: 0.3em 0 0.5em; padding-left: 24px; }
    .preview-ul li { margin-bottom: 0.2em; }
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

    /* Reproductor flotante */
    #floating-player {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1050;
        width: 340px;
    }
    #floating-player .card {
        box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        border: 1px solid #adb5bd;
        margin-bottom: 0;
    }
    #floating-player .card-header {
        cursor: grab;
        user-select: none;
        padding: 0.4rem 0.75rem;
        background: #343a40;
        color: #fff;
        border-radius: 4px 4px 0 0;
    }
    #floating-player .card-header:active {
        cursor: grabbing;
    }
    #floating-player .card-header .btn-tool {
        color: #adb5bd;
    }
    #floating-player .card-header .btn-tool:hover {
        color: #fff;
    }
    #floating-player .card-body {
        padding: 0.5rem;
        background: #fff;
    }
    #floating-player audio,
    #floating-player video {
        width: 100%;
    }
    #floating-player .media-item label {
        font-size: 0.8em;
        color: #6c757d;
        margin-bottom: 2px;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    #floating-player .media-item + .media-item {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #dee2e6;
    }

    /* Tarjetas de info compactas abajo */
    .info-cards-row .card {
        margin-bottom: 0;
    }
    .info-cards-row dl.row dt,
    .info-cards-row dl.row dd {
        font-size: 0.85em;
        margin-bottom: 0.2rem;
    }

    /* Anotaciones del revisor */
    #anotaciones-editor {
        border: 1px solid #dee2e6;
        border-radius: 0 0 4px 4px;
        padding: 12px 16px;
        min-height: 300px;
        font-family: 'Barlow', sans-serif;
        font-size: 14px;
        line-height: 1.8;
        background: #fff;
        color: #212529;
        caret-color: #212529;
        outline: none;
        white-space: pre-wrap;
        overflow-y: auto;
    }
    #anotaciones-editor:focus { border-color: #80bdff; box-shadow: 0 0 0 2px rgba(0,123,255,.15); }
    #anotaciones-editor mark.amarillo { background: #fff3cd; color: #856404; padding: 1px 2px; border-radius: 2px; }
    #anotaciones-editor mark.rojo     { background: #f8d7da; color: #721c24; padding: 1px 2px; border-radius: 2px; }
    #anotaciones-editor mark.verde    { background: #d4edda; color: #155724; padding: 1px 2px; border-radius: 2px; }
    .anotacion-toolbar { background: #f8f9fa; padding: 6px 8px; border: 1px solid #dee2e6; border-bottom: none; border-radius: 4px 4px 0 0; }
    #anotaciones-guardado { transition: opacity .4s; }
</style>
@endsection

@section('content')

{{-- Reproductor flotante --}}
@if($entrevista->rel_adjuntos && $entrevista->rel_adjuntos->count() > 0)
<div id="floating-player">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" id="player-drag-handle">
            <span><i class="fas fa-headphones mr-1"></i>Audio/Video</span>
            <button type="button" class="btn btn-tool btn-sm" id="btn-toggle-player" title="Minimizar/Expandir">
                <i class="fas fa-minus" id="icon-toggle-player"></i>
            </button>
        </div>
        <div class="card-body" id="player-body">
            @foreach($entrevista->rel_adjuntos as $adjunto)
            <div class="media-item">
                <label title="{{ $adjunto->nombre_original }}">{{ $adjunto->nombre_original }}</label>
                @if(strpos($adjunto->tipo_mime, 'audio') !== false)
                <audio controls preload="metadata" id="media-{{ $adjunto->id_adjunto }}" class="w-100">
                    <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                </audio>
                @elseif(strpos($adjunto->tipo_mime, 'video') !== false)
                <video controls preload="metadata" style="max-height: 160px;" id="media-{{ $adjunto->id_adjunto }}"
                    @if($adjunto->tipo_mime === 'video/x-flv') data-flv-src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" @endif>
                    @if($adjunto->tipo_mime !== 'video/x-flv')
                    <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                    @endif
                </video>
                <div class="flv-error-msg" id="flv-error-{{ $adjunto->id_adjunto }}" style="display:none; color:#ff6b6b; padding:8px; text-align:center; background:#1a1a1a;"></div>
                @endif
                <div class="d-flex justify-content-between mt-1">
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $adjunto->id_adjunto }}', -5)" title="Retroceder 5s">
                            <i class="fas fa-backward"></i> -5s
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $adjunto->id_adjunto }}', 5)" title="Avanzar 5s">
                            +5s <i class="fas fa-forward"></i>
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Editor principal (ancho completo) --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-edit mr-2"></i>Transcripcion (Editable)</h3>
                <span class="badge badge-warning">
                    <i class="fas fa-clipboard-check mr-1"></i>En Revision &mdash; {{ $entrevista->entrevista_codigo }}
                    @if($asignacion->id_adjunto && $asignacion->rel_adjunto)
                        &mdash; <i class="fas fa-file-audio"></i> {{ \Illuminate\Support\Str::limit($asignacion->rel_adjunto->nombre_original, 30) }}
                    @endif
                </span>
            </div>
            <form action="{{ route('procesamientos.guardar-asignacion', $asignacion->id_asignacion) }}" method="POST" id="formTranscripcion">
                @csrf
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'transcripcion'])
                    <textarea name="transcripcion" id="transcripcion" class="form-control"
                              placeholder="Edite la transcripcion...">{{ $asignacion->transcripcion_editada }}</textarea>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Tarjetas de información y decisión (parte inferior) --}}
<div class="row info-cards-row">

    {{-- Información de la asignación --}}
    <div class="col-md-3">
        <div class="card card-warning h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-1"></i>Revision</h3>
            </div>
            <div class="card-body py-2">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Transcriptor:</dt>
                    <dd class="col-sm-7">{{ $asignacion->rel_transcriptor->rel_usuario->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Asignada por:</dt>
                    <dd class="col-sm-7">{{ $asignacion->rel_asignado_por->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Fecha Asig.:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5">Fecha Envio:</dt>
                    <dd class="col-sm-7">
                        @if($asignacion->fecha_envio_revision)
                            {{ $asignacion->fecha_envio_revision->format('d/m/Y H:i') }}
                        @else
                            &mdash;
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Información de la entrevista --}}
    <div class="col-md-3">
        <div class="card h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i>Entrevista</h3>
            </div>
            <div class="card-body py-2">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codigo:</dt>
                    <dd class="col-sm-8"><code>{{ $entrevista->entrevista_codigo }}</code></dd>

                    <dt class="col-sm-4">Titulo:</dt>
                    <dd class="col-sm-8">{{ $entrevista->titulo }}</dd>

                    <dt class="col-sm-4">Fecha:</dt>
                    <dd class="col-sm-8">{{ $entrevista->entrevista_fecha ? \Carbon\Carbon::parse($entrevista->entrevista_fecha)->format('d/m/Y') : '-' }}</dd>
                </dl>
                <hr class="my-1">
                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-sm btn-outline-info" target="_blank">
                    <i class="fas fa-external-link-alt mr-1"></i> Ver entrevista
                </a>
            </div>
        </div>
    </div>

    {{-- Botones de decisión --}}
    <div class="col-md-6">
        <div class="card card-outline card-primary h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-gavel mr-1"></i>Decision</h3>
            </div>
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-6">
                        <form action="{{ route('procesamientos.aprobar-asignacion', $asignacion->id_asignacion) }}" method="POST">
                            @csrf
                            <div class="form-group mb-2">
                                <label class="small mb-1">Comentario (opcional)</label>
                                <textarea name="comentario" class="form-control form-control-sm" rows="2" placeholder="Comentario de aprobacion..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-success btn-block" onclick="return confirm('¿Aprobar esta transcripcion como version final?')">
                                <i class="fas fa-check mr-1"></i> Aprobar Transcripcion
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 d-flex flex-column justify-content-end">
                        <button type="button" class="btn btn-danger btn-block mb-2" data-toggle="modal" data-target="#modalRechazar">
                            <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                        </button>
                        <a href="{{ route('procesamientos.edicion') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-arrow-left mr-1"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Panel de anotaciones del revisor --}}
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <i class="fas fa-highlighter mr-2"></i>Anotaciones para el transcriptor
                    <small class="text-muted font-weight-normal ml-2">(selecciona texto y aplica color para marcar errores)</small>
                </h3>
                <span id="anotaciones-guardado" class="text-success small" style="opacity:0">
                    <i class="fas fa-check mr-1"></i>Guardado
                </span>
            </div>
            <div class="card-body p-0">
                <div class="anotacion-toolbar">
                    <div class="btn-group btn-group-sm mr-2">
                        <button type="button" class="btn btn-warning btn-sm" onclick="resaltar('amarillo')" title="Marcar en amarillo (atención)">
                            <i class="fas fa-highlighter"></i> Atención
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" onclick="resaltar('rojo')" title="Marcar en rojo (error)">
                            <i class="fas fa-highlighter"></i> Error
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="resaltar('verde')" title="Marcar en verde (correcto)">
                            <i class="fas fa-highlighter"></i> OK
                        </button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm mr-2" onclick="limpiarMarca()" title="Quitar marca de la selección">
                        <i class="fas fa-eraser"></i> Quitar marca
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="guardarAnotaciones()" id="btnGuardarAnotaciones">
                        <i class="fas fa-save mr-1"></i>Guardar anotaciones
                    </button>
                </div>
                <div id="anotaciones-editor"
                     contenteditable="true"
                     spellcheck="true">{!! $asignacion->transcripcion_anotada
                        ? $asignacion->transcripcion_anotada
                        : nl2br(e($asignacion->transcripcion_editada)) !!}</div>
            </div>
        </div>
    </div>
</div>

{{-- Comparar con transcripción automática --}}
@php
    // Para asignaciones por audio: mostrar solo el texto de ese adjunto como referencia
    $transcripcionAuto = ($asignacion->id_adjunto && $asignacion->rel_adjunto)
        ? $asignacion->rel_adjunto->texto_extraido
        : $entrevista->getTextoParaProcesamiento();
@endphp
@if($transcripcionAuto)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-robot mr-1"></i>Transcripcion Automatica Original (Comparar)</h3>
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
    </div>
</div>
@endif

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
// Conversión FLV → MP4 y reproducción
function iniciarConversionFlv(videoEl, errEl, adjuntoId) {
    var urlConvertir = '{{ route("adjuntos.flv_convertir", "") }}/' + adjuntoId;
    var urlEstado    = '{{ route("adjuntos.flv_estado", "") }}/' + adjuntoId;
    var urlPlay      = '{{ route("adjuntos.flv_play", "") }}/' + adjuntoId;

    errEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Códec antiguo detectado. Convirtiendo video, espere...';
    errEl.style.display = 'block';

    $.ajax({
        url: urlConvertir, type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(resp) {
            if (resp.status === 'ready') {
                reproducirMp4Convertido(videoEl, errEl, urlPlay);
            } else {
                pollEstadoFlv(videoEl, errEl, urlEstado, urlPlay);
            }
        },
        error: function() { errEl.textContent = 'Error al iniciar la conversión del video.'; }
    });
}

function pollEstadoFlv(videoEl, errEl, urlEstado, urlPlay) {
    var intentos = 0, maxIntentos = 120;
    var timer = setInterval(function() {
        if (++intentos > maxIntentos) { clearInterval(timer); errEl.textContent = 'La conversión tardó demasiado. Intente más tarde.'; return; }
        $.get(urlEstado, function(resp) { if (resp.status === 'ready') { clearInterval(timer); reproducirMp4Convertido(videoEl, errEl, urlPlay); } });
    }, 5000);
}

function reproducirMp4Convertido(videoEl, errEl, urlPlay) {
    errEl.style.display = 'none';
    videoEl.src = urlPlay;
    videoEl.type = 'video/mp4';
    videoEl.load();
    videoEl.play().catch(function() {});
}

// Inicializar flv.js para archivos .flv con fallback de conversión
document.querySelectorAll('video[data-flv-src]').forEach(function(videoEl) {
    var adjuntoId = videoEl.id.replace('media-', '');
    var errEl = document.getElementById('flv-error-' + adjuntoId);
    if (typeof flvjs === 'undefined' || !flvjs.isSupported()) {
        if (errEl) { errEl.textContent = 'Su navegador no soporta la reproducción de archivos FLV.'; errEl.style.display = 'block'; }
        return;
    }
    var player = flvjs.createPlayer({ type: 'flv', url: videoEl.getAttribute('data-flv-src'), isLive: false, hasAudio: true, hasVideo: true });
    player.on(flvjs.Events.ERROR, function(errType, errDetail) {
        var detailStr = JSON.stringify(errDetail || {});
        if (detailStr.includes('CodecUnsupported') || detailStr.includes('CODEC_UNSUPPORTED')) {
            player.destroy();
            iniciarConversionFlv(videoEl, errEl, adjuntoId);
        } else if (errEl) {
            errEl.textContent = 'Error al reproducir: ' + errType + ' — ' + detailStr;
            errEl.style.display = 'block';
        }
    });
    player.attachMediaElement(videoEl);
    player.load();
});
function skipMedia(id, seconds) {
    var media = document.getElementById(id);
    if (media) media.currentTime = Math.max(0, media.currentTime + seconds);
}

// ── Resaltador de anotaciones ──────────────────────────────────────────────
function resaltar(color) {
    var sel = window.getSelection();
    if (!sel || sel.isCollapsed) { return; }
    var range = sel.getRangeAt(0);
    // Verificar que la selección está dentro del editor
    var editor = document.getElementById('anotaciones-editor');
    if (!editor.contains(range.commonAncestorContainer)) { return; }

    var mark = document.createElement('mark');
    mark.className = color;
    try {
        range.surroundContents(mark);
    } catch (e) {
        // Si hay nodos parciales, extraer y envolver
        var fragment = range.extractContents();
        mark.appendChild(fragment);
        range.insertNode(mark);
    }
    sel.removeAllRanges();
}

function limpiarMarca() {
    var sel = window.getSelection();
    if (!sel || sel.isCollapsed) { return; }
    var range = sel.getRangeAt(0);
    var editor = document.getElementById('anotaciones-editor');
    if (!editor.contains(range.commonAncestorContainer)) { return; }

    // Buscar mark ancestro y quitarlo
    var node = range.commonAncestorContainer;
    while (node && node !== editor) {
        if (node.nodeName === 'MARK') {
            var parent = node.parentNode;
            while (node.firstChild) parent.insertBefore(node.firstChild, node);
            parent.removeChild(node);
            break;
        }
        node = node.parentNode;
    }
}

function guardarAnotaciones() {
    var html = document.getElementById('anotaciones-editor').innerHTML;
    var btn = $('#btnGuardarAnotaciones');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');

    $.ajax({
        url: '{{ route("procesamientos.anotar", $asignacion->id_asignacion) }}',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', anotaciones: html },
        success: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar anotaciones');
            var $aviso = $('#anotaciones-guardado');
            $aviso.css('opacity', 1);
            setTimeout(function() { $aviso.css('opacity', 0); }, 2500);
        },
        error: function() {
            btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Guardar anotaciones');
            alert('Error al guardar las anotaciones.');
        }
    });
}

$(document).ready(function() {
    // Atajos de teclado para reproductor
    $(document).on('keydown', function(e) {
        var getMedia = function() { return $('audio, video').first()[0]; };
        if (e.ctrlKey && e.key === ' ') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.paused ? m.play() : m.pause();
        }
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.currentTime = Math.max(0, m.currentTime - 5);
        }
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.currentTime += 5;
        }
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

    // Validación del motivo de rechazo antes de enviar
    $('#modalRechazar form').on('submit', function(e) {
        var comentario = $(this).find('textarea[name="comentario"]').val().trim();
        var $error = $(this).find('.rechazo-error');
        if (comentario.length < 10) {
            e.preventDefault();
            if ($error.length === 0) {
                $(this).find('.form-group').append(
                    '<div class="rechazo-error alert alert-warning mt-2 mb-0 py-2">' +
                    '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                    'El motivo del rechazo debe tener al menos 10 caracteres (actualmente: ' + comentario.length + ').' +
                    '</div>'
                );
            } else {
                $error.html(
                    '<i class="fas fa-exclamation-triangle mr-1"></i>' +
                    'El motivo del rechazo debe tener al menos 10 caracteres (actualmente: ' + comentario.length + ').'
                );
            }
            $(this).find('textarea[name="comentario"]').focus();
        }
    });

    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'Tiene cambios sin guardar. ¿Desea salir de la pagina?';
        }
    });

    // ── Reproductor flotante: toggle minimizar ──────────────────────────
    $('#btn-toggle-player').on('click', function() {
        var $body = $('#player-body');
        var $icon = $('#icon-toggle-player');
        if ($body.is(':visible')) {
            $body.slideUp(150);
            $icon.removeClass('fa-minus').addClass('fa-plus');
        } else {
            $body.slideDown(150);
            $icon.removeClass('fa-plus').addClass('fa-minus');
        }
    });

    // ── Reproductor flotante: arrastrar ────────────────────────────────
    var $player   = $('#floating-player');
    var $handle   = $('#player-drag-handle');
    var dragging  = false;
    var startX, startY, origLeft, origTop;

    $handle.on('mousedown', function(e) {
        // Ignorar clic en el botón de minimizar
        if ($(e.target).closest('#btn-toggle-player').length) return;

        dragging = true;
        startX   = e.clientX;
        startY   = e.clientY;

        var pos  = $player.position();
        // Asegurarnos de trabajar en coordenadas de ventana (fixed)
        var rect = $player[0].getBoundingClientRect();
        origLeft = rect.left;
        origTop  = rect.top;

        // Convertir a posicionamiento fixed explícito
        $player.css({ position: 'fixed', left: origLeft, top: origTop, right: 'auto' });

        e.preventDefault();
    });

    $(document).on('mousemove', function(e) {
        if (!dragging) return;
        var dx = e.clientX - startX;
        var dy = e.clientY - startY;
        $player.css({
            left: Math.max(0, origLeft + dx),
            top:  Math.max(0, origTop  + dy)
        });
    });

    $(document).on('mouseup', function() {
        dragging = false;
    });
});
</script>
@endsection
