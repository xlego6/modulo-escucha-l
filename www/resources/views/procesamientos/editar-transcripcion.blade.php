@extends('layouts.app')

@section('title', 'Editar Transcripcion')
@section('content_header')
Editar Transcripcion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    #editor-transcripcion {
        min-height: calc(100vh - 300px);
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.6;
        resize: vertical;
        border-radius: 0 0 4px 4px !important;
    }
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
    #tab-transcripciones .nav-link {
        border-radius: 0;
        padding: 0.5rem 0.75rem;
        font-size: 0.85rem;
        border-bottom: 2px solid transparent;
    }
    #tab-transcripciones .nav-link.active {
        background-color: #007bff;
        color: white;
    }
    #tab-transcripciones .nav-link:not(.active):hover {
        background-color: #e9ecef;
    }
    /* Vista previa de transcripcion */
    .preview-content {
        border: 1px solid #dee2e6;
        border-radius: 0 0 4px 4px;
        padding: 12px 16px;
        font-family: 'Courier New', monospace;
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

    /* Reproductor flotante */
    #floating-player {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1050;
        width: 360px;
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
        max-height: 70vh;
        overflow-y: auto;
    }
    #floating-player audio,
    #floating-player video { width: 100%; }
    #floating-player .audio-player {
        transition: all 0.2s;
        border-radius: 4px;
        padding: 6px;
        cursor: pointer;
    }
    #floating-player .audio-player:hover { background: #f8f9fa; }
    #floating-player .audio-player.border-primary {
        border: 2px solid #007bff !important;
    }
    #floating-player .audio-player + .audio-player {
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px solid #dee2e6;
    }
    #floating-player .audio-player label {
        font-size: 0.8em;
        color: #495057;
        margin-bottom: 3px;
        display: block;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        cursor: default;
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
</style>
@endsection

@section('content')

@php
    $medios = $entrevista->rel_adjuntos->filter(function($a) {
        $tipo = $a->tipo_mime ?? '';
        return strpos($tipo, 'audio') !== false || strpos($tipo, 'video') !== false;
    });
    $tieneMultiplesAudios = $medios->count() > 1;
@endphp

{{-- Reproductor flotante --}}
@if($medios->count() > 0)
<div id="floating-player">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" id="player-drag-handle">
            <span>
                <i class="fas fa-headphones mr-1"></i>Audio/Video
                <span class="badge badge-secondary ml-1">{{ $medios->count() }}</span>
            </span>
            <button type="button" class="btn btn-tool btn-sm" id="btn-toggle-player" title="Minimizar/Expandir">
                <i class="fas fa-minus" id="icon-toggle-player"></i>
            </button>
        </div>
        <div class="card-body" id="player-body">
            @foreach($medios as $index => $medio)
            <div class="audio-player" id="player-wrap-{{ $medio->id_adjunto }}">
                <label title="{{ $medio->nombre_original }}">
                    {{ $medio->nombre_original }}
                    <span class="badge badge-secondary ml-1">{{ $index + 1 }}/{{ $medios->count() }}</span>
                </label>
                @if(strpos($medio->tipo_mime ?? '', 'video') !== false)
                <video controls class="w-100" id="media-{{ $medio->id_adjunto }}" style="max-height: 150px;">
                    <source src="{{ route('adjuntos.ver', $medio->id_adjunto) }}" type="{{ $medio->tipo_mime }}">
                </video>
                @else
                <audio controls class="w-100" id="media-{{ $medio->id_adjunto }}">
                    <source src="{{ route('adjuntos.ver', $medio->id_adjunto) }}" type="{{ $medio->tipo_mime }}">
                </audio>
                @endif
                <div class="media-controls">
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $medio->id_adjunto }}', -10)">
                            <i class="fas fa-backward"></i> -10s
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="skipMedia('media-{{ $medio->id_adjunto }}', 10)">
                            +10s <i class="fas fa-forward"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="changeSpeed('media-{{ $medio->id_adjunto }}')">
                            <i class="fas fa-tachometer-alt"></i> <span class="speed-label">1x</span>
                        </button>
                    </div>
                    <button class="btn btn-sm btn-info btn-transcribir-adjunto"
                            data-id="{{ $medio->id_adjunto }}"
                            data-nombre="{{ $medio->nombre_original }}"
                            title="Transcribir este audio">
                        <i class="fas fa-microphone"></i>
                        @if($medio->texto_extraido)
                        <i class="fas fa-redo ml-1"></i>
                        @endif
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Selector de transcripciones (si hay múltiples audios) --}}
@if($tieneMultiplesAudios)
<div class="row mb-2">
    <div class="col-12">
        <div class="card card-outline card-info mb-0">
            <div class="card-header py-2">
                <h3 class="card-title text-sm"><i class="fas fa-list-alt mr-2"></i>Transcripciones por Archivo</h3>
            </div>
            <div class="card-body p-0">
                <ul class="nav nav-pills nav-justified" id="tab-transcripciones">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="pill" href="#tab-completa" data-audio="">
                            <i class="fas fa-file-alt mr-1"></i>Completa
                        </a>
                    </li>
                    @foreach($medios as $index => $medio)
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="pill" href="#tab-audio-{{ $medio->id_adjunto }}"
                           data-audio="media-{{ $medio->id_adjunto }}">
                            <i class="fas fa-{{ strpos($medio->tipo_mime, 'video') !== false ? 'video' : 'music' }} mr-1"></i>
                            {{ \Illuminate\Support\Str::limit($medio->nombre_original, 15) }}
                            @if($medio->texto_extraido)
                            <span class="badge badge-success badge-sm ml-1">
                                {{ number_format(strlen($medio->texto_extraido)) }}
                            </span>
                            @else
                            <span class="badge badge-secondary badge-sm ml-1">Sin transcripcion</span>
                            @endif
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Editor principal (ancho completo) --}}
<div class="row mb-3">
    <div class="col-12">
        <form action="{{ route('procesamientos.guardar-transcripcion', $entrevista->id_e_ind_fvt) }}" method="POST" id="form-transcripcion">
            @csrf
            <input type="hidden" name="id_adjunto" id="input-id-adjunto" value="">

            <div class="card card-success">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-file-alt mr-2"></i>
                        <span id="titulo-editor">Transcripcion Completa</span>
                    </h3>
                    <span class="badge badge-light" id="char-count">0 caracteres</span>
                </div>
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'editor-transcripcion'])
                    <textarea name="transcripcion" id="editor-transcripcion" class="form-control"
                              placeholder="Escriba o pegue la transcripcion aqui...">{{ $entrevista->getTextoParaProcesamiento() ?? '' }}</textarea>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                    <button type="button" class="btn btn-primary" onclick="guardarYAprobar()">
                        <i class="fas fa-check-double mr-2"></i>Guardar y Aprobar
                    </button>
                    <a href="{{ route('procesamientos.edicion') }}" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left mr-2"></i>Volver
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Tarjetas de información (parte inferior) --}}
<div class="row info-cards-row">

    {{-- Info de la entrevista --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i>Informacion</h3>
            </div>
            <div class="card-body py-2">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codigo:</dt>
                    <dd class="col-sm-8"><code>{{ $entrevista->entrevista_codigo }}</code></dd>

                    <dt class="col-sm-4">Titulo:</dt>
                    <dd class="col-sm-8">{{ $entrevista->titulo ?: 'Sin titulo' }}</dd>

                    <dt class="col-sm-4">Fecha:</dt>
                    <dd class="col-sm-8">
                        @if($entrevista->fecha_entrevista)
                            {{ \Carbon\Carbon::parse($entrevista->fecha_entrevista)->format('d/m/Y') }}
                        @else
                            -
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    {{-- Atajos de teclado --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-keyboard mr-1"></i>Atajos de Teclado</h3>
            </div>
            <div class="card-body py-2">
                <ul class="list-unstyled mb-0 small">
                    <li><kbd>Ctrl</kbd> + <kbd>S</kbd> &mdash; Guardar</li>
                    <li><kbd>Ctrl</kbd> + <kbd>Space</kbd> &mdash; Play/Pause</li>
                    <li><kbd>Ctrl</kbd> + <kbd>←</kbd> &mdash; Retroceder 10s</li>
                    <li><kbd>Ctrl</kbd> + <kbd>→</kbd> &mdash; Avanzar 10s</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
@include('partials.editor-toolbar-js')
<script>
// Transcripciones por adjunto
var transcripciones = {
    'completa': @json($entrevista->getTextoParaProcesamiento() ?? ''),
    @foreach($medios as $medio)
    '{{ $medio->id_adjunto }}': @json($medio->texto_extraido ?? ''),
    @endforeach
};

var transcripcionActual = 'completa';
var cambiosPendientes = {};

function updateCharCount() {
    var count = $('#editor-transcripcion').val().length;
    $('#char-count').text(count.toLocaleString() + ' caracteres');
}

$(document).ready(function() {
    updateCharCount();

    $('#editor-transcripcion').on('input', function() {
        updateCharCount();
        cambiosPendientes[transcripcionActual] = $(this).val();
    });

    // Cambio de pestanas de transcripcion
    $('#tab-transcripciones a').on('click', function(e) {
        e.preventDefault();

        cambiosPendientes[transcripcionActual] = $('#editor-transcripcion').val();

        var $tab   = $(this);
        var href   = $tab.attr('href');
        var audioId = $tab.data('audio');

        if (href === '#tab-completa') {
            transcripcionActual = 'completa';
            $('#input-id-adjunto').val('');
            $('#titulo-editor').text('Transcripcion Completa');
        } else {
            var idAdjunto = href.replace('#tab-audio-', '');
            transcripcionActual = idAdjunto;
            $('#input-id-adjunto').val(idAdjunto);
            $('#titulo-editor').text($tab.text().trim());
        }

        var texto = cambiosPendientes[transcripcionActual] !== undefined
            ? cambiosPendientes[transcripcionActual]
            : transcripciones[transcripcionActual] || '';
        $('#editor-transcripcion').val(texto);
        updateCharCount();

        // Resaltar reproductor correspondiente
        $('#floating-player .audio-player').removeClass('border-primary');
        if (audioId) {
            $('#media-' + audioId.replace('media-', '')).closest('.audio-player').addClass('border-primary');
        }

        $('#tab-transcripciones a').removeClass('active');
        $tab.addClass('active');
    });

    // Clic en reproductor → seleccionar su transcripcion
    $('#floating-player .audio-player').on('click', function(e) {
        if ($(e.target).is('button, audio, video') || $(e.target).closest('button, audio, video').length) return;
        var mediaId = $(this).find('audio, video').attr('id');
        if (mediaId) {
            var $tab = $('#tab-transcripciones a[data-audio="' + mediaId + '"]');
            if ($tab.length) $tab.click();
        }
    });

    // Atajos de teclado
    $(document).on('keydown', function(e) {
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#form-transcripcion').submit();
        }
        var getActiveMedia = function() {
            var mediaId = transcripcionActual !== 'completa' ? 'media-' + transcripcionActual : null;
            return mediaId ? document.getElementById(mediaId) : $('audio, video').first()[0];
        };
        if (e.ctrlKey && e.key === ' ') {
            e.preventDefault();
            var m = getActiveMedia();
            if (m) m.paused ? m.play() : m.pause();
        }
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            var m = getActiveMedia();
            if (m) m.currentTime -= 10;
        }
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            var m = getActiveMedia();
            if (m) m.currentTime += 10;
        }
    });

    // Transcribir adjunto individual
    $('.btn-transcribir-adjunto').on('click', function(e) {
        e.stopPropagation();
        var $btn     = $(this);
        var idAdjunto = $btn.data('id');
        var nombre   = $btn.data('nombre');

        if (!confirm('¿Transcribir el audio "' + nombre + '"?\n\nEste proceso puede tomar varios minutos.')) return;

        var htmlOriginal = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("procesamientos/transcripcion/adjunto") }}/' + idAdjunto,
            method: 'POST',
            timeout: 600000,
            data: { _token: '{{ csrf_token() }}', diarizar: 1 },
            success: function(response) {
                if (response.success) {
                    transcripciones[idAdjunto]    = response.text;
                    cambiosPendientes[idAdjunto]  = response.text;

                    if (transcripcionActual == idAdjunto) {
                        $('#editor-transcripcion').val(response.text);
                        updateCharCount();
                    }

                    $('a[href="#tab-audio-' + idAdjunto + '"]').find('.badge')
                        .removeClass('badge-secondary').addClass('badge-success')
                        .text(response.text_length.toLocaleString());

                    $btn.removeClass('btn-info').addClass('btn-success')
                        .html('<i class="fas fa-check"></i> <i class="fas fa-redo"></i>');

                    alert('Transcripcion completada: ' + response.text_length.toLocaleString() + ' caracteres');

                    if (confirm('¿Recargar pagina para ver la transcripcion completa actualizada?')) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (response.error || 'Error desconocido'));
                    $btn.prop('disabled', false).html(htmlOriginal);
                }
            },
            error: function(xhr) {
                var errorMsg = xhr.responseJSON?.error || 'Error de conexion. La transcripcion puede estar en proceso.';
                alert('Error: ' + errorMsg);
                $btn.prop('disabled', false).html(htmlOriginal);
            }
        });
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
        $(media).closest('.audio-player').find('.speed-label').text(speeds[speedIndex[id]] + 'x');
    }
}

function guardarYAprobar() {
    if (confirm('¿Guardar y aprobar esta transcripcion?')) {
        $.ajax({
            url: '{{ route("procesamientos.guardar-transcripcion", $entrevista->id_e_ind_fvt) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                transcripcion: $('#editor-transcripcion').val()
            },
            success: function() {
                window.location.href = '{{ route("procesamientos.aprobar-transcripcion", $entrevista->id_e_ind_fvt) }}';
            },
            error: function() {
                alert('Error al guardar la transcripcion');
            }
        });
    }
}
</script>
@endsection
