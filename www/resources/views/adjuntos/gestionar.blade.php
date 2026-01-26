@extends('layouts.app')

@section('title', 'Gestionar Adjuntos')
@section('content_header', 'Gestionar Archivos Adjuntos')

@section('css')
<style>
    #visor-container {
        display: none;
        margin-bottom: 20px;
    }
    #visor-container.active {
        display: block;
    }
    .visor-content {
        background: #000;
        border-radius: 5px;
        overflow: hidden;
        position: relative;
    }
    .visor-content audio {
        width: 100%;
        margin-top: 10px;
    }
    .visor-content video {
        width: 100%;
        max-height: 500px;
    }
    .visor-content iframe {
        width: 100%;
        height: 600px;
        border: none;
    }
    .visor-content img {
        width: 100%;
        max-height: 500px;
        object-fit: contain;
    }
    /* Visor de transcripción */
    .visor-transcripcion {
        background: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Consolas', 'Monaco', monospace;
        font-size: 14px;
        line-height: 1.6;
        padding: 20px;
        max-height: 600px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .visor-transcripcion .transcripcion-header {
        background: #333;
        margin: -20px -20px 20px -20px;
        padding: 15px 20px;
        border-bottom: 1px solid #444;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .visor-transcripcion .transcripcion-header h5 {
        margin: 0;
        color: #4fc3f7;
    }
    .visor-transcripcion .transcripcion-stats {
        font-size: 12px;
        color: #888;
    }
    .btn-reproducir.active {
        background-color: #17a2b8;
        border-color: #17a2b8;
    }
    .archivo-activo {
        background-color: #e3f2fd !important;
    }
    /* Marca de agua overlay */
    .marca-agua-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 100;
        background-repeat: repeat;
        background-size: 300px auto;
        opacity: 0.5;
    }
    /* Marca de agua CSS (fallback) */
    .marca-agua-css {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        pointer-events: none;
        z-index: 100;
        overflow: hidden;
    }
    .marca-agua-css::before {
        content: attr(data-marca);
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-45deg);
        font-size: 24px;
        color: rgba(128, 128, 128, 0.4);
        white-space: nowrap;
        font-weight: bold;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }
    .marca-agua-css .marca-pattern {
        position: absolute;
        top: -100%;
        left: -100%;
        right: -100%;
        bottom: -100%;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        align-content: space-around;
        transform: rotate(-30deg);
    }
    .marca-agua-css .marca-item {
        padding: 40px;
        color: rgba(128, 128, 128, 0.35);
        font-size: 14px;
        white-space: nowrap;
        font-weight: 500;
    }
    .visor-con-marca {
        position: relative;
    }
    .visor-con-marca iframe,
    .visor-con-marca img {
        position: relative;
        z-index: 1;
    }
    /* Info de marca de agua en la cabecera */
    .marca-info {
        font-size: 11px;
        color: #aaa;
        margin-left: 15px;
    }
    @media print {
        .marca-agua-overlay, .marca-agua-css {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            opacity: 0.8;
        }
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Info de entrevista -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-microphone"></i>
                    Entrevista: {{ $entrevista->entrevista_codigo }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver a Entrevista
                    </a>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Titulo:</strong> {{ $entrevista->titulo }}</p>
                <p><strong>Fecha:</strong> {{ $entrevista->fmt_fecha }}</p>
            </div>
        </div>

        <!-- Visor embebido -->
        <div class="card" id="visor-container">
            <div class="card-header bg-dark text-white">
                <h3 class="card-title" id="visor-titulo">
                    <i class="fas fa-play-circle"></i> <span>Reproductor</span>
                    <span class="marca-info" id="marca-info">
                        <i class="fas fa-user-shield"></i> {{ Auth::user()->name }} - <span id="fecha-consulta"></span>
                    </span>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool text-white" id="btn-cerrar-visor" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0 visor-content" id="visor-content">
                <!-- El contenido se carga dinamicamente -->
            </div>
        </div>

        <!-- Lista de adjuntos -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-paperclip"></i>
                    Archivos Adjuntos ({{ $entrevista->rel_adjuntos->count() }})
                </h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px"></th>
                            <th>Nombre</th>
                            <th style="width: 150px">Tipo</th>
                            <th style="width: 100px">Tamano</th>
                            <th style="width: 100px">Duracion</th>
                            <th style="width: 150px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entrevista->rel_adjuntos as $adjunto)
                        @php
                            $esTranscripcionAuto = $adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA;
                            $esTranscripcionFinal = $adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL;
                            $esTranscripcion = $esTranscripcionAuto || $esTranscripcionFinal;
                        @endphp
                        <tr id="fila-{{ $adjunto->id_adjunto }}">
                            <td class="text-center">
                                @if($esTranscripcionFinal)
                                    <i class="fas fa-file-signature fa-lg text-success" title="Transcripcion Final"></i>
                                @elseif($esTranscripcionAuto)
                                    <i class="fas fa-robot fa-lg text-primary" title="Transcripcion Automatizada"></i>
                                @elseif($adjunto->es_audio)
                                    <i class="fas fa-file-audio fa-lg text-info"></i>
                                @elseif($adjunto->es_video)
                                    <i class="fas fa-file-video fa-lg text-danger"></i>
                                @elseif($adjunto->es_documento)
                                    <i class="fas fa-file-pdf fa-lg text-warning"></i>
                                @elseif(strpos($adjunto->tipo_mime, 'image') !== false)
                                    <i class="fas fa-file-image fa-lg text-success"></i>
                                @else
                                    <i class="fas fa-file fa-lg text-secondary"></i>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $adjunto->nombre_original }}</strong>
                                <br><small class="text-muted">{{ $adjunto->tipo_mime }}</small>
                            </td>
                            <td>
                                @if($adjunto->rel_tipo)
                                    <span class="badge badge-info">{{ $adjunto->rel_tipo->descripcion }}</span>
                                @else
                                    <span class="badge badge-secondary">Sin tipo</span>
                                @endif
                            </td>
                            <td>{{ $adjunto->fmt_tamano }}</td>
                            <td>
                                @if($adjunto->es_audio || $adjunto->es_video)
                                    {{ $adjunto->fmt_duracion }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @php
                                        $puedeReproducir = $adjunto->es_audio || $adjunto->es_video ||
                                            strpos($adjunto->tipo_mime, 'pdf') !== false ||
                                            strpos($adjunto->tipo_mime, 'image') !== false ||
                                            $esTranscripcion;
                                    @endphp
                                    @if($puedeReproducir)
                                    <button type="button" class="btn btn-info btn-reproducir"
                                            data-id="{{ $adjunto->id_adjunto }}"
                                            data-nombre="{{ $adjunto->nombre_original }}"
                                            data-url="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}"
                                            data-tipo="{{ $adjunto->tipo_mime }}"
                                            data-es-audio="{{ $adjunto->es_audio ? '1' : '0' }}"
                                            data-es-video="{{ $adjunto->es_video ? '1' : '0' }}"
                                            data-es-transcripcion="{{ $esTranscripcion ? '1' : '0' }}"
                                            title="{{ $esTranscripcion ? 'Ver Transcripcion' : 'Ver/Reproducir' }}">
                                        <i class="fas {{ $esTranscripcion ? 'fa-eye' : 'fa-play' }}"></i>
                                    </button>
                                    @endif
                                    @if($adjunto->existe_archivo)
                                    <a href="{{ route('adjuntos.descargar', $adjunto->id_adjunto) }}" class="btn btn-success" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @endif
                                    <form action="{{ route('adjuntos.eliminar', $adjunto->id_adjunto) }}" method="POST" style="display:inline" onsubmit="return confirm('Esta seguro de eliminar este archivo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p>No hay archivos adjuntos</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Formulario de subida -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-upload"></i> Subir Archivo</h3>
            </div>
            <form action="{{ route('adjuntos.subir', $entrevista->id_e_ind_fvt) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="id_tipo">Tipo de Archivo <span class="text-danger">*</span></label>
                        <select name="id_tipo" id="id_tipo" class="form-control" required>
                            <option value="">-- Seleccione --</option>
                            @foreach($tipos as $id => $nombre)
                            <option value="{{ $id }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="archivo">Archivo <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="archivo" name="archivo" required>
                            <label class="custom-file-label" for="archivo" data-browse="Buscar">Seleccionar archivo...</label>
                        </div>
                        <small class="text-muted">Maximo 500MB. Formatos: audio, video, PDF, imagenes, documentos.</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-upload"></i> Subir Archivo
                    </button>
                </div>
            </form>
        </div>

        <!-- Resumen -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Resumen</h3>
            </div>
            <div class="card-body">
                @php
                    $total = $entrevista->rel_adjuntos->count();
                    $audios = $entrevista->rel_adjuntos->filter(fn($a) => $a->es_audio)->count();
                    $videos = $entrevista->rel_adjuntos->filter(fn($a) => $a->es_video)->count();
                    $docs = $entrevista->rel_adjuntos->filter(fn($a) => $a->es_documento)->count();
                    $transcAuto = $entrevista->rel_adjuntos->filter(fn($a) => $a->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)->count();
                    $transcFinal = $entrevista->rel_adjuntos->filter(fn($a) => $a->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL)->count();
                    $otros = $total - $audios - $videos - $docs - $transcAuto - $transcFinal;
                    $tamano_total = $entrevista->rel_adjuntos->sum('tamano');
                @endphp
                <table class="table table-sm table-borderless">
                    <tr>
                        <td><i class="fas fa-file-audio text-info"></i> Audios</td>
                        <td class="text-right"><strong>{{ $audios }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-video text-danger"></i> Videos</td>
                        <td class="text-right"><strong>{{ $videos }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-pdf text-warning"></i> Documentos</td>
                        <td class="text-right"><strong>{{ $docs }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-robot text-primary"></i> Transc. Auto</td>
                        <td class="text-right"><strong>{{ $transcAuto }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file-signature text-success"></i> Transc. Final</td>
                        <td class="text-right"><strong>{{ $transcFinal }}</strong></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-file text-secondary"></i> Otros</td>
                        <td class="text-right"><strong>{{ $otros }}</strong></td>
                    </tr>
                    <tr class="border-top">
                        <td><strong>Total</strong></td>
                        <td class="text-right"><strong>{{ $total }}</strong></td>
                    </tr>
                    <tr>
                        <td>Tamano total</td>
                        <td class="text-right">
                            @if($tamano_total >= 1073741824)
                                {{ number_format($tamano_total / 1073741824, 2) }} GB
                            @elseif($tamano_total >= 1048576)
                                {{ number_format($tamano_total / 1048576, 2) }} MB
                            @elseif($tamano_total >= 1024)
                                {{ number_format($tamano_total / 1024, 2) }} KB
                            @else
                                {{ $tamano_total }} bytes
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // URL de la marca de agua generada (puede ser null si GD no está disponible)
    const marcaAguaUrl = '{{ $marcaAgua ? asset($marcaAgua) : "" }}';
    const userName = '{{ Auth::user()->name }}';
    const usarMarcaCSS = !marcaAguaUrl;

    // Textos de transcripciones (cargados desde PHP)
    const transcripciones = {
        @foreach($entrevista->rel_adjuntos as $adjunto)
            @if(($adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA || $adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL) && $adjunto->texto_extraido)
                {{ $adjunto->id_adjunto }}: @json($adjunto->texto_extraido),
            @endif
        @endforeach
    };

    // Tipos de transcripción
    const tiposTranscripcion = {
        @foreach($entrevista->rel_adjuntos as $adjunto)
            @if($adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA)
                {{ $adjunto->id_adjunto }}: 'automatizada',
            @elseif($adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL)
                {{ $adjunto->id_adjunto }}: 'final',
            @endif
        @endforeach
    };

    // Funcion para escapar HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Mostrar nombre del archivo seleccionado
    document.getElementById('archivo').addEventListener('change', function(e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : 'Seleccionar archivo...';
        var label = this.nextElementSibling;
        label.textContent = fileName;
    });

    // Funcion para obtener fecha/hora actual formateada
    function getFechaHoraActual() {
        let now = new Date();
        let fecha = now.toLocaleDateString('es-CO');
        let hora = now.toLocaleTimeString('es-CO');
        return fecha + ' ' + hora;
    }

    // Funcion para generar marca de agua (PNG o CSS fallback)
    function generarMarcaAgua() {
        let fechaHora = getFechaHoraActual();
        let textoMarca = userName + ' - ' + fechaHora;

        if (marcaAguaUrl) {
            // Usar imagen PNG generada por el servidor
            return `<div class="marca-agua-overlay" style="background-image: url('${marcaAguaUrl}');"></div>`;
        } else {
            // Fallback: usar marca de agua CSS
            // Crear patron repetido para cubrir todo el visor
            let patronHtml = '';
            for (let i = 0; i < 20; i++) {
                patronHtml += `<span class="marca-item">${textoMarca}</span>`;
            }
            return `
                <div class="marca-agua-css" data-marca="${textoMarca}">
                    <div class="marca-pattern">
                        ${patronHtml}
                    </div>
                </div>
            `;
        }
    }

    // Variables para el visor
    let currentId = null;

    // Boton reproducir
    $('.btn-reproducir').on('click', function() {
        let btn = $(this);
        let id = btn.data('id');
        let nombre = btn.data('nombre');
        let url = btn.data('url');
        let tipo = btn.data('tipo');
        let esAudio = btn.data('es-audio') === 1 || btn.data('es-audio') === '1';
        let esVideo = btn.data('es-video') === 1 || btn.data('es-video') === '1';
        let esTranscripcion = btn.data('es-transcripcion') === 1 || btn.data('es-transcripcion') === '1';

        // Si ya esta abierto el mismo, cerrarlo
        if (currentId === id && $('#visor-container').hasClass('active')) {
            cerrarVisor();
            return;
        }

        // Marcar fila activa
        $('tr').removeClass('archivo-activo');
        $('#fila-' + id).addClass('archivo-activo');

        // Marcar boton activo
        $('.btn-reproducir').removeClass('active');
        btn.addClass('active');

        // Actualizar titulo
        $('#visor-titulo span:first').text(nombre);

        // Actualizar fecha/hora de consulta
        $('#fecha-consulta').text(getFechaHoraActual());

        // Generar contenido segun tipo
        let contenido = '';
        let necesitaMarca = false;

        if (esTranscripcion) {
            // Mostrar transcripción (automatizada o final)
            let texto = transcripciones[id] || 'Sin contenido';
            let caracteres = texto.length;
            let palabras = texto.split(/\s+/).filter(w => w.length > 0).length;
            let tipoTrans = tiposTranscripcion[id] || 'automatizada';
            let esFinal = tipoTrans === 'final';
            let icono = esFinal ? 'fa-file-signature' : 'fa-robot';
            let titulo = esFinal ? 'Transcripcion Final' : 'Transcripcion Automatizada';
            let colorHeader = esFinal ? '#2e7d32' : '#333';

            contenido = `
                <div class="visor-transcripcion">
                    <div class="transcripcion-header" style="background: ${colorHeader};">
                        <h5><i class="fas ${icono} mr-2"></i>${titulo}</h5>
                        <div class="transcripcion-stats">
                            <span class="mr-3"><i class="fas fa-font mr-1"></i>${caracteres.toLocaleString()} caracteres</span>
                            <span><i class="fas fa-paragraph mr-1"></i>${palabras.toLocaleString()} palabras</span>
                        </div>
                    </div>
                    <div class="transcripcion-texto">${escapeHtml(texto)}</div>
                </div>
            `;
        } else if (esAudio) {
            contenido = `
                <div class="p-4 text-center">
                    <i class="fas fa-music fa-4x text-info mb-3"></i>
                    <h5 class="text-white mb-3">${nombre}</h5>
                    <audio controls autoplay class="w-100">
                        <source src="${url}" type="${tipo}">
                        Su navegador no soporta la reproduccion de audio.
                    </audio>
                </div>
            `;
        } else if (esVideo) {
            contenido = `
                <video controls autoplay>
                    <source src="${url}" type="${tipo}">
                    Su navegador no soporta la reproduccion de video.
                </video>
            `;
        } else if (tipo.includes('pdf')) {
            necesitaMarca = true;
            contenido = `
                <div class="visor-con-marca">
                    <iframe src="${url}#toolbar=0&navpanes=0&scrollbar=1"></iframe>
                    ${generarMarcaAgua()}
                </div>
            `;
        } else if (tipo.includes('image')) {
            necesitaMarca = true;
            contenido = `
                <div class="visor-con-marca p-3 text-center" style="background: #333;">
                    <img src="${url}" alt="${nombre}" class="img-fluid">
                    ${generarMarcaAgua()}
                </div>
            `;
        } else {
            // Otros documentos - intentar mostrar en iframe con marca
            necesitaMarca = true;
            contenido = `
                <div class="visor-con-marca">
                    <iframe src="${url}"></iframe>
                    ${generarMarcaAgua()}
                </div>
            `;
        }

        $('#visor-content').html(contenido);
        $('#visor-container').addClass('active');

        // Mostrar/ocultar info de marca segun tipo
        if (necesitaMarca) {
            $('#marca-info').show();
        } else {
            $('#marca-info').hide();
        }

        currentId = id;

        // Scroll al visor
        $('html, body').animate({
            scrollTop: $('#visor-container').offset().top - 100
        }, 300);
    });

    // Cerrar visor
    $('#btn-cerrar-visor').on('click', function() {
        cerrarVisor();
    });

    function cerrarVisor() {
        // Detener audio/video antes de cerrar
        $('#visor-content audio, #visor-content video').each(function() {
            this.pause();
        });

        $('#visor-container').removeClass('active');
        $('#visor-content').html('');
        $('tr').removeClass('archivo-activo');
        $('.btn-reproducir').removeClass('active');
        currentId = null;
    }

    // Cerrar con tecla Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#visor-container').hasClass('active')) {
            cerrarVisor();
        }
    });
});
</script>
@endsection
