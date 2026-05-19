@extends('layouts.app')

@section('title', 'Editar Transcripcion')
@section('content_header')
Editar Transcripcion: {{ $entrevista->entrevista_codigo }}
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
    #floating-player .card-header:active { cursor: grabbing; }
    #floating-player .card-header .btn-tool { color: #adb5bd; }
    #floating-player .card-header .btn-tool:hover { color: #fff; }
    #floating-player .card-body {
        padding: 0.5rem;
        background: #fff;
    }
    #floating-player audio,
    #floating-player video { width: 100%; }
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
    #floating-player .media-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 4px;
    }
    #floating-player .media-controls .btn {
        padding: 0.15rem 0.4rem;
        font-size: 0.78em;
    }

    /* Tarjetas de info compactas abajo */
    .info-cards-row .card { margin-bottom: 0; }
    .info-cards-row dl.row dt,
    .info-cards-row dl.row dd {
        font-size: 0.85em;
        margin-bottom: 0.2rem;
    }

    /* Anotaciones del revisor (panel de referencia) */
    .anotaciones-ref { font-family: 'Barlow', sans-serif; font-size: 14px; line-height: 1.8; white-space: pre-wrap; }
    .anotaciones-ref mark.amarillo { background: #fff3cd; color: #856404; padding: 1px 2px; border-radius: 2px; }
    .anotaciones-ref mark.rojo     { background: #f8d7da; color: #721c24; padding: 1px 2px; border-radius: 2px; }
    .anotaciones-ref mark.verde    { background: #d4edda; color: #155724; padding: 1px 2px; border-radius: 2px; }

    /* Calificación con estrellas */
    .star-rating { display: inline-flex; gap: 4px; }
    .star-rating .star {
        font-size: 2rem;
        cursor: pointer;
        color: #dee2e6;
        transition: color 0.15s, transform 0.1s;
        line-height: 1;
        user-select: none;
    }
    .star-rating .star:hover,
    .star-rating .star.hovered { color: #f0c75f; transform: scale(1.15); }
    .star-rating .star.selected { color: #ebc01a; }
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
                <audio controls controlsList="nodownload" preload="metadata" id="media-{{ $adjunto->id_adjunto }}" class="w-100">
                    <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                </audio>
                @elseif(strpos($adjunto->tipo_mime, 'video') !== false)
                <video controls controlsList="nodownload" preload="metadata" style="max-height: 160px;" id="media-{{ $adjunto->id_adjunto }}" class="w-100"
                    @if($adjunto->tipo_mime === 'video/x-flv') data-flv-src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" @endif>
                    @if($adjunto->tipo_mime !== 'video/x-flv')
                    <source src="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}" type="{{ $adjunto->tipo_mime }}">
                    @endif
                </video>
                <div class="flv-error-msg" id="flv-error-{{ $adjunto->id_adjunto }}" style="display:none; color:#ff6b6b; padding:8px; text-align:center; background:#1a1a1a;"></div>
                @endif
                <div class="media-controls">
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $adjunto->id_adjunto }}', -5)">
                            <i class="fas fa-backward"></i> -5s
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $adjunto->id_adjunto }}', 5)">
                            +5s <i class="fas fa-forward"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeSpeed('media-{{ $adjunto->id_adjunto }}')">
                            <i class="fas fa-tachometer-alt"></i> <span class="speed-label">1x</span>
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
                <h3 class="card-title mb-0"><i class="fas fa-keyboard mr-2"></i>Transcripcion</h3>
                <span class="badge badge-{{ $asignacion->estado == 'aprobada' ? 'success' : ($asignacion->estado == 'rechazada' ? 'danger' : 'info') }}">
                    {{ $asignacion->fmt_estado }} &mdash; {{ $entrevista->entrevista_codigo }}
                    @if($asignacion->id_adjunto && $asignacion->rel_adjunto)
                        &mdash; <i class="fas fa-file-audio"></i> {{ \Illuminate\Support\Str::limit($asignacion->rel_adjunto->nombre_original, 30) }}
                    @endif
                </span>
            </div>

            @if($asignacion->historial_comentarios && count($asignacion->historial_comentarios) > 0)
            <div class="card-body py-2 px-3 border-bottom">
                <h6 class="mb-2"><i class="fas fa-history mr-1 text-secondary"></i>Historial de revision</h6>
                @foreach(array_reverse($asignacion->historial_comentarios) as $entrada)
                @php
                    $esRechazo = $entrada['accion'] === 'rechazada';
                    $fechaFmt = \Carbon\Carbon::parse($entrada['fecha'])->format('d/m/Y H:i');
                @endphp
                <div class="alert {{ $esRechazo ? 'alert-danger' : 'alert-success' }} py-2 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>
                            <i class="fas fa-{{ $esRechazo ? 'times-circle' : 'check-circle' }} mr-1"></i>
                            {{ $esRechazo ? 'Rechazada' : 'Aprobada' }} por {{ $entrada['revisor'] }}
                        </strong>
                        <small>{{ $fechaFmt }}</small>
                    </div>
                    @if(!empty($entrada['comentario']))
                    <div class="mt-1">{{ $entrada['comentario'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
            @elseif($asignacion->estado == 'rechazada' && $asignacion->comentario_revision)
            <div class="card-body py-2 px-3 border-bottom">
                <div class="alert alert-danger mb-0">
                    <strong><i class="fas fa-exclamation-triangle mr-1"></i> Motivo del rechazo:</strong>
                    {{ $asignacion->comentario_revision }}
                </div>
            </div>
            @endif

            @if(!($soloLectura ?? false))
            <form action="{{ route('procesamientos.guardar-asignacion', $asignacion->id_asignacion) }}" method="POST" id="formTranscripcion">
                @csrf
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'transcripcion'])
                    <textarea name="transcripcion" id="transcripcion" class="form-control"
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
            @else
            {{-- Modo solo lectura: transcripción aprobada dentro de la ventana de 7 días --}}
            <div class="card-body p-2">
                <div class="alert alert-success py-2 mb-2">
                    <i class="fas fa-lock mr-1"></i>
                    <strong>Transcripción aprobada</strong> — acceso de lectura disponible hasta
                    {{ $asignacion->fecha_revision->addDays(7)->format('d/m/Y') }}.
                </div>
                <div class="border rounded p-3 bg-light" style="min-height: 200px; font-family: monospace; white-space: pre-wrap; font-size: 13px;">{{ $asignacion->transcripcion_editada }}</div>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.edicion') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Tarjetas de información (parte inferior) --}}
<div class="row info-cards-row">

    {{-- Estado de la asignación --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2 bg-{{ $asignacion->estado == 'rechazada' ? 'danger' : 'info' }}">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i>Estado de la Asignacion</h3>
            </div>
            <div class="card-body py-2">
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
            </div>
        </div>
    </div>

    {{-- Información de la entrevista --}}
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-file-alt mr-1"></i>Datos de la Entrevista</h3>
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
            </div>
        </div>
    </div>

    {{-- Transcripción automática (acceso rápido) --}}
    @php $transcripcionAuto = $entrevista->getTextoParaProcesamiento(); @endphp
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-keyboard mr-1"></i>Atajos de Teclado</h3>
            </div>
            <div class="card-body py-2">
                <ul class="list-unstyled mb-0 small">
                    <li><kbd>Ctrl</kbd> + <kbd>S</kbd> &mdash; Guardar borrador</li>
                    <li><kbd>Ctrl</kbd> + <kbd>Space</kbd> &mdash; Play/Pause</li>
                    <li><kbd>Ctrl</kbd> + <kbd>←</kbd> &mdash; Retroceder 5s</li>
                    <li><kbd>Ctrl</kbd> + <kbd>→</kbd> &mdash; Avanzar 5s</li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Anotaciones del revisor (solo visible si rechazada y hay anotaciones) --}}
@if($asignacion->transcripcion_anotada)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-warning">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-highlighter mr-2 text-warning"></i>Anotaciones del revisor</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    <i class="fas fa-info-circle mr-1"></i>
                    El revisor marcó las secciones que requieren corrección. Úsalo como referencia mientras editas tu transcripción.
                </p>
                <div class="anotaciones-ref">{!! $asignacion->transcripcion_anotada !!}</div>
                <div class="mt-2 small">
                    <span class="badge" style="background:#fff3cd;color:#856404;border:1px solid #ffc107">Atención</span>
                    <span class="badge ml-1" style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb">Error</span>
                    <span class="badge ml-1" style="background:#d4edda;color:#155724;border:1px solid #c3e6cb">OK</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Transcripción automática colapsada --}}
@if($transcripcionAuto && $asignacion->transcripcion_editada != $transcripcionAuto)
<div class="row mt-3">
    <div class="col-12">
        <div class="card card-outline card-secondary collapsed-card">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-robot mr-1"></i>Transcripcion Automatica (Referencia)</h3>
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
    </div>
</div>
@endif

{{-- Modal confirmar envío --}}
<div class="modal fade" id="modalEnviar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success">
                <h5 class="modal-title"><i class="fas fa-paper-plane mr-2"></i>Enviar a Revisión</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('procesamientos.enviar-revision', $asignacion->id_asignacion) }}" method="POST" id="formEnviarRevision">
                @csrf
                <div class="modal-body">
                    <p>¿Está seguro de enviar la transcripción a revisión?</p>
                    <p class="text-muted small mb-3">
                        Una vez enviada, no podrá editarla hasta que sea revisada por un líder.
                        Si es rechazada, podrá corregirla y enviarla nuevamente.
                    </p>

                    <div class="form-group">
                        <label class="font-weight-bold">
                            Califique la calidad del audio
                            <span class="text-danger">*</span>
                        </label>
                        <div class="star-rating mt-1" id="starRating">
                            @for($i = 1; $i <= 5; $i++)
                            <span class="star" data-value="{{ $i }}" title="{{ $i }} estrella{{ $i > 1 ? 's' : '' }}">&#9733;</span>
                            @endfor
                        </div>
                        <input type="hidden" name="calificacion_audio" id="calificacionAudio" value="">
                        <small class="text-muted" id="starLabel">Seleccione una calificación (1 a 5)</small>
                    </div>

                    <div class="form-group mb-0">
                        <label for="observacionesEnvio">Observaciones <span class="text-muted font-weight-normal">(opcional)</span></label>
                        <textarea name="observaciones_envio" id="observacionesEnvio" class="form-control" rows="3"
                                  placeholder="Notas sobre la calidad del audio, dificultades, etc."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="btnConfirmarEnvio" disabled>
                        <i class="fas fa-paper-plane mr-1"></i> Confirmar Envío
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
    if (media) media.currentTime += seconds;
}

var speeds = [1, 1.25, 1.5, 1.75, 2, 0.75];
var speedIndex = {};
function changeSpeed(id) {
    if (!speedIndex[id]) speedIndex[id] = 0;
    speedIndex[id] = (speedIndex[id] + 1) % speeds.length;
    var media = document.getElementById(id);
    if (media) {
        media.playbackRate = speeds[speedIndex[id]];
        $(media).closest('.media-item').find('.speed-label').text(speeds[speedIndex[id]] + 'x');
    }
}

function enviarARevision() {
    var texto = $('#transcripcion').val().trim();
    if (texto.length < 50) {
        alert('La transcripción debe tener al menos 50 caracteres');
        return;
    }

    guardarTiempoMedia();
    // Guardar borrador primero, luego abrir modal
    $.ajax({
        url: $('#formTranscripcion').attr('action'),
        method: 'POST',
        data: $('#formTranscripcion').serialize(),
        success: function() {
            // Resetear estrellas al abrir
            $('#starRating .star').removeClass('selected hovered');
            $('#calificacionAudio').val('');
            $('#starLabel').text('Seleccione una calificación (1 a 5)');
            $('#btnConfirmarEnvio').prop('disabled', true);
            $('#observacionesEnvio').val('');
            $('#modalEnviar').modal('show');
        },
        error: function() {
            alert('Error al guardar. Intente nuevamente.');
        }
    });
}

// Lógica de estrellas
$(document).on('mouseenter', '#starRating .star', function() {
    var val = parseInt($(this).data('value'));
    $('#starRating .star').each(function() {
        $(this).toggleClass('hovered', parseInt($(this).data('value')) <= val);
    });
}).on('mouseleave', '#starRating .star', function() {
    $('#starRating .star').removeClass('hovered');
}).on('click', '#starRating .star', function() {
    var val = parseInt($(this).data('value'));
    var labels = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
    $('#calificacionAudio').val(val);
    $('#starRating .star').each(function() {
        $(this).toggleClass('selected', parseInt($(this).data('value')) <= val);
    });
    $('#starLabel').text(val + ' / 5 — ' + labels[val]);
    $('#btnConfirmarEnvio').prop('disabled', false);
});

// Clave localStorage para preservar tiempo de reproducción
var _draftTimeKey = 'draft_time_{{ $asignacion->id_asignacion }}';

function guardarTiempoMedia() {
    var m = $('audio, video').first()[0];
    if (m && m.currentTime > 0) {
        localStorage.setItem(_draftTimeKey, m.currentTime);
    }
}

function restaurarTiempoMedia() {
    var t = parseFloat(localStorage.getItem(_draftTimeKey) || '0');
    if (t > 0) {
        $('audio, video').first().on('loadedmetadata', function() {
            this.currentTime = t;
        });
    }
}

// Auto-guardar cada 60 segundos
setInterval(function() {
    guardarTiempoMedia();
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

$(document).ready(function() {
    // Restaurar tiempo de reproducción del borrador anterior
    restaurarTiempoMedia();

    // Guardar tiempo al enviar el formulario
    $('#formTranscripcion').on('submit', function() {
        guardarTiempoMedia();
    });

    // Atajos de teclado
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            guardarTiempoMedia();
            $('#formTranscripcion').submit();
        }
        var getMedia = function() { return $('audio, video').first()[0]; };
        if (e.ctrlKey && e.key === ' ') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.paused ? m.play() : m.pause();
        }
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.currentTime -= 5;
        }
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            var m = getMedia();
            if (m) m.currentTime += 5;
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
    var $player  = $('#floating-player');
    var $handle  = $('#player-drag-handle');
    var dragging = false;
    var startX, startY, origLeft, origTop;

    $handle.on('mousedown', function(e) {
        if ($(e.target).closest('#btn-toggle-player').length) return;
        dragging = true;
        startX   = e.clientX;
        startY   = e.clientY;
        var rect = $player[0].getBoundingClientRect();
        origLeft = rect.left;
        origTop  = rect.top;
        $player.css({ position: 'fixed', left: origLeft, top: origTop, right: 'auto' });
        e.preventDefault();
    });

    $(document).on('mousemove', function(e) {
        if (!dragging) return;
        $player.css({
            left: Math.max(0, origLeft + e.clientX - startX),
            top:  Math.max(0, origTop  + e.clientY - startY)
        });
    });

    $(document).on('mouseup', function() { dragging = false; });
});

@if(!($soloLectura ?? false))
// Heartbeat de tiempo activo de edición
(function () {
    const heartbeatUrl = '{{ route("procesamientos.heartbeat", $asignacion->id_asignacion) }}';
    const token = '{{ csrf_token() }}';
    let activo = !document.hidden;

    document.addEventListener('visibilitychange', function () {
        activo = !document.hidden;
    });

    setInterval(function () {
        if (!activo) return;
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token, 'Content-Type': 'application/json' },
            keepalive: true,
        });
    }, 30000);
})();
@endif
</script>
@endsection
