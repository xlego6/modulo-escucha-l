@extends('layouts.app')

@section('title', 'Editar Transcripcion')
@section('content_header')
Editar Transcripcion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    #editor-transcripcion {
        min-height: 400px;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        line-height: 1.6;
    }
    .audio-player {
        position: sticky;
        top: 0;
        z-index: 100;
        background: #f4f6f9;
        padding: 10px;
        border-radius: 4px;
    }
    .speaker-tag {
        background: #e9ecef;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        color: #495057;
    }
    .timestamp {
        color: #6c757d;
        font-size: 12px;
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
    .audio-player {
        cursor: pointer;
        transition: all 0.2s;
    }
    .audio-player:hover {
        background-color: #f8f9fa;
    }
    .audio-player.border-primary {
        border: 2px solid #007bff !important;
        border-radius: 4px;
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
</style>
@endsection

@section('content')
<div class="row">
    <!-- Panel de audio -->
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-headphones mr-2"></i>Reproductor de Audio</h3>
            </div>
            <div class="card-body">
                @php
                    $medios = $entrevista->rel_adjuntos->filter(function($a) {
                        $tipo = $a->tipo_mime ?? '';
                        return strpos($tipo, 'audio') !== false || strpos($tipo, 'video') !== false;
                    });
                @endphp
                @if($medios->count() > 0)
                    <p class="text-muted small mb-2">
                        <i class="fas fa-info-circle mr-1"></i>{{ $medios->count() }} archivo(s) de audio/video
                    </p>
                    @foreach($medios as $index => $medio)
                    <div class="audio-player mb-3 {{ $index > 0 ? 'border-top pt-3' : '' }}">
                        <p class="mb-1">
                            <strong>{{ $medio->nombre_original }}</strong>
                            <span class="badge badge-secondary ml-1">{{ $index + 1 }}/{{ $medios->count() }}</span>
                        </p>
                        @if(strpos($medio->tipo_mime ?? '', 'video') !== false)
                        <video controls class="w-100" id="media-{{ $medio->id_adjunto }}" style="max-height: 200px;">
                            <source src="{{ route('adjuntos.ver', $medio->id_adjunto) }}" type="{{ $medio->tipo_mime }}">
                            Tu navegador no soporta video HTML5.
                        </video>
                        @else
                        <audio controls class="w-100" id="media-{{ $medio->id_adjunto }}">
                            <source src="{{ route('adjuntos.ver', $medio->id_adjunto) }}" type="{{ $medio->tipo_mime }}">
                            Tu navegador no soporta audio HTML5.
                        </audio>
                        @endif
                        <div class="mt-2 d-flex justify-content-between align-items-center">
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
                @else
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-volume-mute fa-2x mb-2"></i>
                        <p>No hay archivos de audio/video</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Info de la entrevista -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informacion</h3>
            </div>
            <div class="card-body">
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

        <!-- Atajos de teclado -->
        <div class="card card-secondary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-keyboard mr-2"></i>Atajos de Teclado</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><kbd>Ctrl</kbd> + <kbd>S</kbd> - Guardar</li>
                    <li><kbd>Ctrl</kbd> + <kbd>Space</kbd> - Play/Pause</li>
                    <li><kbd>Ctrl</kbd> + <kbd>←</kbd> - Retroceder 10s</li>
                    <li><kbd>Ctrl</kbd> + <kbd>→</kbd> - Avanzar 10s</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Editor de transcripción -->
    <div class="col-md-8">
        @php
            $mediosConTranscripcion = $entrevista->rel_adjuntos->filter(function($a) {
                $tipo = $a->tipo_mime ?? '';
                return strpos($tipo, 'audio') !== false || strpos($tipo, 'video') !== false;
            });
            $tieneMultiplesAudios = $mediosConTranscripcion->count() > 1;
        @endphp

        @if($tieneMultiplesAudios)
        <!-- Selector de transcripciones por audio -->
        <div class="card card-outline card-info mb-3">
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
                    @foreach($mediosConTranscripcion as $index => $medio)
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
        @endif

        <form action="{{ route('procesamientos.guardar-transcripcion', $entrevista->id_e_ind_fvt) }}" method="POST" id="form-transcripcion">
            @csrf
            <input type="hidden" name="id_adjunto" id="input-id-adjunto" value="">

            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-alt mr-2"></i>
                        <span id="titulo-editor">Transcripcion Completa</span>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-light" id="char-count">0 caracteres</span>
                    </div>
                </div>
                <div class="card-body p-2">
                    @include('partials.editor-toolbar', ['targetId' => 'editor-transcripcion'])
                    <textarea name="transcripcion" id="editor-transcripcion" class="form-control"
                              style="border-radius: 0 0 4px 4px;"
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
@endsection

@section('js')
@include('partials.editor-toolbar-js')
<script>
// Transcripciones por adjunto
var transcripciones = {
    'completa': @json($entrevista->getTextoParaProcesamiento() ?? ''),
    @foreach($mediosConTranscripcion as $medio)
    '{{ $medio->id_adjunto }}': @json($medio->texto_extraido ?? ''),
    @endforeach
};

var transcripcionActual = 'completa';
var cambiosPendientes = {};

// Contador de caracteres (global para acceso desde callbacks)
function updateCharCount() {
    var count = $('#editor-transcripcion').val().length;
    $('#char-count').text(count.toLocaleString() + ' caracteres');
}

$(document).ready(function() {
    updateCharCount();
    $('#editor-transcripcion').on('input', function() {
        updateCharCount();
        // Marcar cambios pendientes
        cambiosPendientes[transcripcionActual] = $(this).val();
    });

    // Cambio de pestanas de transcripcion
    $('#tab-transcripciones a').on('click', function(e) {
        e.preventDefault();

        // Guardar cambios actuales antes de cambiar
        cambiosPendientes[transcripcionActual] = $('#editor-transcripcion').val();

        var $tab = $(this);
        var href = $tab.attr('href');
        var audioId = $tab.data('audio');

        // Determinar cual transcripcion cargar
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

        // Cargar transcripcion (con cambios pendientes si existen)
        var texto = cambiosPendientes[transcripcionActual] !== undefined
            ? cambiosPendientes[transcripcionActual]
            : transcripciones[transcripcionActual] || '';
        $('#editor-transcripcion').val(texto);
        updateCharCount();

        // Resaltar reproductor correspondiente
        $('.audio-player').removeClass('border-primary');
        if (audioId) {
            $('#' + audioId).closest('.audio-player').addClass('border-primary border-2');
        }

        // Actualizar pestana activa
        $('#tab-transcripciones a').removeClass('active');
        $tab.addClass('active');
    });

    // Al hacer clic en un reproductor, seleccionar su transcripcion
    $('.audio-player').on('click', function(e) {
        if ($(e.target).is('button') || $(e.target).closest('button').length) return;

        var mediaId = $(this).find('audio, video').attr('id');
        if (mediaId) {
            var $tab = $('#tab-transcripciones a[data-audio="' + mediaId + '"]');
            if ($tab.length) {
                $tab.click();
            }
        }
    });

    // Atajos de teclado
    $(document).on('keydown', function(e) {
        // Ctrl + S - Guardar
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            $('#form-transcripcion').submit();
        }
        // Ctrl + Space - Play/Pause del audio activo
        if (e.ctrlKey && e.key === ' ') {
            e.preventDefault();
            var mediaId = transcripcionActual !== 'completa' ? 'media-' + transcripcionActual : null;
            var media = mediaId ? document.getElementById(mediaId) : $('audio, video').first()[0];
            if (media) {
                media.paused ? media.play() : media.pause();
            }
        }
        // Ctrl + Left - Retroceder
        if (e.ctrlKey && e.key === 'ArrowLeft') {
            e.preventDefault();
            var mediaId = transcripcionActual !== 'completa' ? 'media-' + transcripcionActual : null;
            var media = mediaId ? document.getElementById(mediaId) : $('audio, video').first()[0];
            if (media) media.currentTime -= 10;
        }
        // Ctrl + Right - Avanzar
        if (e.ctrlKey && e.key === 'ArrowRight') {
            e.preventDefault();
            var mediaId = transcripcionActual !== 'completa' ? 'media-' + transcripcionActual : null;
            var media = mediaId ? document.getElementById(mediaId) : $('audio, video').first()[0];
            if (media) media.currentTime += 10;
        }
    });

    // Transcribir adjunto individual
    $('.btn-transcribir-adjunto').on('click', function(e) {
        e.stopPropagation();
        var $btn = $(this);
        var idAdjunto = $btn.data('id');
        var nombre = $btn.data('nombre');

        if (!confirm('¿Transcribir el audio "' + nombre + '"?\n\nEste proceso puede tomar varios minutos.')) return;

        var htmlOriginal = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("procesamientos/transcripcion/adjunto") }}/' + idAdjunto,
            method: 'POST',
            timeout: 600000, // 10 minutos
            data: {
                _token: '{{ csrf_token() }}',
                diarizar: 1
            },
            success: function(response) {
                if (response.success) {
                    // Actualizar la transcripcion en memoria
                    transcripciones[idAdjunto] = response.text;
                    cambiosPendientes[idAdjunto] = response.text;

                    // Si estamos viendo este adjunto, actualizar el editor
                    if (transcripcionActual == idAdjunto) {
                        $('#editor-transcripcion').val(response.text);
                        updateCharCount();
                    }

                    // Actualizar badge en la pestana
                    var $tab = $('a[href="#tab-audio-' + idAdjunto + '"]');
                    $tab.find('.badge').removeClass('badge-secondary').addClass('badge-success')
                        .text(response.text_length.toLocaleString());

                    // Actualizar boton
                    $btn.removeClass('btn-info').addClass('btn-success')
                        .html('<i class="fas fa-check"></i> <i class="fas fa-redo"></i>');

                    alert('Transcripcion completada: ' + response.text_length.toLocaleString() + ' caracteres');

                    // Recargar para actualizar transcripcion completa
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
        // Primero guardar
        $.ajax({
            url: '{{ route("procesamientos.guardar-transcripcion", $entrevista->id_e_ind_fvt) }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                transcripcion: $('#editor-transcripcion').val()
            },
            success: function() {
                // Luego aprobar
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
