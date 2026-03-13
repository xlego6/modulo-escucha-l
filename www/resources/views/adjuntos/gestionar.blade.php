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
    /* Visor PDF.js */
    .visor-pdf-wrapper {
        position: relative;
        background: #525659;
        min-height: 300px;
    }
    #visor-pdf-pages {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 10px 0;
        overflow-y: auto;
        max-height: 750px;
    }
    #visor-pdf-pages canvas {
        display: block;
        box-shadow: 0 0 8px rgba(0,0,0,0.6);
        margin: 8px auto;
    }
    .pdf-page-num {
        color: #ccc;
        font-size: 12px;
        text-align: center;
        margin-bottom: 4px;
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
    /* Visor de documentos (TXT, DOCX, etc.) */
    .visor-documento {
        background: #fafafa;
        color: #333;
        font-family: 'Georgia', 'Times New Roman', serif;
        font-size: 15px;
        line-height: 1.8;
        padding: 20px;
        max-height: 600px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-wrap: break-word;
        position: relative;
    }
    .visor-documento .documento-header {
        background: #4a6785;
        margin: -20px -20px 20px -20px;
        padding: 15px 20px;
        border-bottom: 1px solid #3b5570;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .visor-documento .documento-header h5 {
        margin: 0;
        color: #fff;
    }
    .visor-documento .documento-stats {
        font-size: 12px;
        color: #ccc;
    }
    /* Proteccion anti-copia */
    .no-copiar {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
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
                                        $tieneTextoExtraido = !$esTranscripcion && !$adjunto->es_audio && !$adjunto->es_video && !empty($adjunto->texto_extraido);
                                        $puedeReproducirAdj = $adjunto->es_audio || $adjunto->es_video ||
                                            strpos($adjunto->tipo_mime, 'pdf') !== false ||
                                            strpos($adjunto->tipo_mime, 'image') !== false ||
                                            $esTranscripcion || $tieneTextoExtraido;
                                    @endphp
                                    @if($puedeReproducirAdj && $puedeVer)
                                    <button type="button" class="btn btn-info btn-reproducir"
                                            data-id="{{ $adjunto->id_adjunto }}"
                                            data-nombre="{{ $adjunto->nombre_original }}"
                                            data-url="{{ route('adjuntos.ver', $adjunto->id_adjunto) }}"
                                            data-tipo="{{ $adjunto->tipo_mime }}"
                                            data-es-audio="{{ $adjunto->es_audio ? '1' : '0' }}"
                                            data-es-video="{{ $adjunto->es_video ? '1' : '0' }}"
                                            data-es-transcripcion="{{ $esTranscripcion ? '1' : '0' }}"
                                            data-tiene-texto="{{ $tieneTextoExtraido ? '1' : '0' }}"
                                            title="{{ $esTranscripcion ? 'Ver Transcripcion' : ($tieneTextoExtraido ? 'Ver Documento' : 'Ver/Reproducir') }}">
                                        <i class="fas {{ $esTranscripcion ? 'fa-eye' : ($tieneTextoExtraido ? 'fa-eye' : 'fa-play') }}"></i>
                                    </button>
                                    @elseif($puedeReproducirAdj && !$puedeVer)
                                    <span class="btn btn-secondary" title="Sin permiso para reproducir" disabled>
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    @endif
                                    @if($adjunto->existe_archivo && $puedeGestionar && (Auth::user()->id_nivel == 1 || (!$adjunto->es_audio && !$adjunto->es_video)))
                                    <a href="{{ route('adjuntos.descargar', $adjunto->id_adjunto) }}" class="btn btn-success" title="Descargar archivo original">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @endif
                                    @if($esTranscripcion && !empty($adjunto->texto_extraido) && $puedeGestionar)
                                    @php
                                        $tipoFormTR = $esTranscripcionFinal ? 'final' : 'auto';
                                    @endphp
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-toggle="dropdown" title="Descargar transcripción">
                                            <i class="fas fa-file-download"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ route('adjuntos.formtr', [$entrevista->id_e_ind_fvt, $tipoFormTR, 'docx']) }}">
                                                <i class="fas fa-file-word text-primary mr-2"></i>Descargar .docx
                                            </a>
                                            <a class="dropdown-item" href="{{ route('adjuntos.formtr', [$entrevista->id_e_ind_fvt, $tipoFormTR, 'pdf']) }}">
                                                <i class="fas fa-file-pdf text-danger mr-2"></i>Descargar .pdf
                                            </a>
                                        </div>
                                    </div>
                                    @endif
                                    @if($puedeGestionar)
                                    <form action="{{ route('adjuntos.eliminar', $adjunto->id_adjunto) }}" method="POST" style="display:inline" onsubmit="return confirm('Esta seguro de eliminar este archivo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
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
        @if($puedeGestionar)
        <!-- Formulario de subida -->
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-upload"></i> Subir Archivo</h3>
            </div>
            <form id="form-subir-archivo" enctype="multipart/form-data">
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

                    <!-- Indicador de progreso -->
                    <div id="upload-progress-container" style="display:none;">
                        <div class="mb-1 d-flex justify-content-between">
                            <small id="upload-status-text" class="text-muted">Subiendo archivo...</small>
                            <small id="upload-percent-text" class="font-weight-bold">0%</small>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div id="upload-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small id="upload-detail-text" class="text-muted"></small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-block" id="btn-subir">
                        <i class="fas fa-upload"></i> Subir Archivo
                    </button>
                </div>
            </form>
        </div>
        @endif

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
<script src="{{ url('js/pdf.js') }}"></script>
<script>

// Dibujar marca de agua diagonal directamente sobre un canvas ya renderizado
function dibujarMarcaAguaCanvas(canvas, texto) {
    var ctx = canvas.getContext('2d');
    var w = canvas.width;
    var h = canvas.height;
    var fontSize = Math.max(14, Math.min(22, w / 28));

    ctx.save();
    ctx.globalAlpha = 0.18;
    ctx.fillStyle = '#555555';
    ctx.font = 'bold ' + fontSize + 'px "Barlow", "Source Sans Pro", Arial, sans-serif';
    ctx.textAlign = 'left';
    ctx.textBaseline = 'middle';

    var angle = -Math.PI / 5; // ~-36 grados
    var stepY = fontSize * 7;
    var stepX = fontSize * 18;

    for (var row = -2; row * stepY < h + stepY * 2; row++) {
        for (var col = -1; col * stepX < w + stepX * 2; col++) {
            ctx.save();
            ctx.translate(col * stepX, row * stepY);
            ctx.rotate(angle);
            ctx.fillText(texto, 0, 0);
            ctx.restore();
        }
    }
    ctx.restore();
}

// Renderizar PDF usando PDF.js en el panel inline
function renderizarPdfJs(pdfUrl) {
    var container = document.getElementById('visor-pdf-pages');
    if (!container) return;

    var pdfjsLib = window['pdfjs-dist/build/pdf'];
    if (!pdfjsLib) {
        container.innerHTML = '<p class="text-danger text-center py-4"><i class="fas fa-exclamation-triangle mr-2"></i>PDF.js no disponible.</p>';
        return;
    }

    pdfjsLib.GlobalWorkerOptions.workerSrc = '{{ url("js/pdf.worker.js") }}';

    var marcaPdfTexto = '{{ addslashes(Auth::user()->name) }}  |  ' + new Date().toLocaleString('es-CO');

    var loadingTask = pdfjsLib.getDocument(pdfUrl);
    loadingTask.promise.then(function(pdf) {
        container.innerHTML = '';
        var numPages = pdf.numPages;

        for (var i = 1; i <= numPages; i++) {
            (function(pageNum) {
                pdf.getPage(pageNum).then(function(page) {
                    var scale = 1.3;
                    var viewport = page.getViewport({ scale: scale });

                    var numLabel = document.createElement('p');
                    numLabel.className = 'pdf-page-num';
                    numLabel.textContent = 'Página ' + pageNum + ' de ' + numPages;

                    var canvas = document.createElement('canvas');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;

                    container.appendChild(numLabel);
                    container.appendChild(canvas);

                    var renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport });
                    renderTask.promise.then(function() {
                        dibujarMarcaAguaCanvas(canvas, marcaPdfTexto);
                    });
                });
            })(i);
        }
    }, function(reason) {
        container.innerHTML = '<p class="text-danger text-center py-4"><i class="fas fa-exclamation-triangle mr-2"></i>Error al cargar el PDF.</p>';
        console.error('PDF.js error:', reason);
    });
}

// Iniciar conversión FLV → MP4 en el servidor y reproducir al terminar
function iniciarConversionFlvYReproducir(videoEl, errEl, adjuntoId) {
    var urlConvertir = '{{ route("adjuntos.flv_convertir", "") }}/' + adjuntoId;
    var urlEstado    = '{{ route("adjuntos.flv_estado", "") }}/' + adjuntoId;
    var urlPlay      = '{{ route("adjuntos.flv_play", "") }}/' + adjuntoId;

    errEl.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Códec antiguo detectado. Convirtiendo video, espere...';
    errEl.style.display = 'block';

    $.ajax({
        url: urlConvertir,
        type: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        success: function(resp) {
            if (resp.status === 'ready') {
                reproducirMp4Convertido(videoEl, errEl, urlPlay);
            } else {
                pollEstadoFlv(videoEl, errEl, urlEstado, urlPlay);
            }
        },
        error: function() {
            errEl.textContent = 'Error al iniciar la conversión del video.';
        }
    });
}

function pollEstadoFlv(videoEl, errEl, urlEstado, urlPlay) {
    var intentos = 0;
    var maxIntentos = 120; // 10 minutos máximo (cada 5s)
    var timer = setInterval(function() {
        intentos++;
        if (intentos > maxIntentos) {
            clearInterval(timer);
            errEl.textContent = 'La conversión tardó demasiado. Intente más tarde.';
            return;
        }
        $.get(urlEstado, function(resp) {
            if (resp.status === 'ready') {
                clearInterval(timer);
                reproducirMp4Convertido(videoEl, errEl, urlPlay);
            }
            // Si 'converting' o 'pending', sigue esperando
        });
    }, 5000);
}

function reproducirMp4Convertido(videoEl, errEl, urlPlay) {
    errEl.style.display = 'none';
    videoEl.src = urlPlay;
    videoEl.type = 'video/mp4';
    videoEl.load();
    videoEl.play().catch(function() {
        // El navegador puede bloquear autoplay; los controles ya están visibles
    });
}

$(document).ready(function() {
    // URL de la marca de agua generada (puede ser null si GD no está disponible)
    const marcaAguaUrl = '{{ $marcaAgua ? asset($marcaAgua) : "" }}';
    const userName = '{{ Auth::user()->name }}';
    const esAdmin = {{ Auth::user()->id_nivel == 1 ? 'true' : 'false' }};
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

    // Textos de documentos (TXT, DOCX, etc.) con texto extraido
    const documentosTexto = {
        @foreach($entrevista->rel_adjuntos as $adjunto)
            @php
                $esTransAdj = $adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA || $adjunto->id_tipo == \App\Models\Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL;
            @endphp
            @if(!$esTransAdj && !empty($adjunto->texto_extraido))
                {{ $adjunto->id_adjunto }}: @json($adjunto->texto_extraido),
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
    var archivoInput = document.getElementById('archivo');
    if (archivoInput) {
        archivoInput.addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : 'Seleccionar archivo...';
            var label = this.nextElementSibling;
            label.textContent = fileName;
        });
    }

    // Formatear bytes a unidad legible
    function formatBytes(bytes) {
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' bytes';
    }

    // Subida con indicador de progreso
    $('#form-subir-archivo').on('submit', function(e) {
        e.preventDefault();

        var fileInput = document.getElementById('archivo');
        var tipoSelect = document.getElementById('id_tipo');

        if (!tipoSelect.value) {
            alert('Debe seleccionar un tipo de archivo.');
            return;
        }
        if (!fileInput.files.length) {
            alert('Debe seleccionar un archivo.');
            return;
        }

        var formData = new FormData(this);
        var fileSize = fileInput.files[0].size;
        var startTime = Date.now();

        // Mostrar barra de progreso, deshabilitar boton
        $('#upload-progress-container').show();
        $('#btn-subir').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Subiendo...');
        $('#upload-progress-bar').css('width', '0%').removeClass('bg-danger').addClass('bg-success progress-bar-animated');
        $('#upload-status-text').text('Subiendo archivo...');
        $('#upload-percent-text').text('0%');
        $('#upload-detail-text').text('');

        var xhr = new XMLHttpRequest();

        xhr.upload.addEventListener('progress', function(evt) {
            if (evt.lengthComputable) {
                var percent = Math.round((evt.loaded / evt.total) * 100);
                $('#upload-progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);
                $('#upload-percent-text').text(percent + '%');

                // Calcular velocidad y tiempo restante
                var elapsed = (Date.now() - startTime) / 1000;
                if (elapsed > 0.5) {
                    var speed = evt.loaded / elapsed;
                    var remaining = (evt.total - evt.loaded) / speed;
                    var detalle = formatBytes(evt.loaded) + ' / ' + formatBytes(evt.total);
                    if (remaining > 0 && percent < 100) {
                        if (remaining >= 60) {
                            detalle += ' — ~' + Math.ceil(remaining / 60) + ' min restante(s)';
                        } else {
                            detalle += ' — ~' + Math.ceil(remaining) + ' seg restante(s)';
                        }
                        detalle += ' (' + formatBytes(speed) + '/s)';
                    }
                    $('#upload-detail-text').text(detalle);
                }

                if (percent >= 100) {
                    $('#upload-status-text').text('Procesando archivo en el servidor...');
                    $('#upload-progress-bar').removeClass('progress-bar-animated');
                    $('#upload-detail-text').text('');
                }
            }
        });

        xhr.addEventListener('load', function() {
            if (xhr.status >= 200 && xhr.status < 400) {
                $('#upload-status-text').text('Archivo subido correctamente.');
                $('#upload-progress-bar').css('width', '100%');
                $('#upload-percent-text').text('100%');
                $('#upload-detail-text').text('');
                // Recargar pagina para mostrar el nuevo adjunto
                setTimeout(function() {
                    window.location.reload();
                }, 500);
            } else {
                var msg = 'Error al subir el archivo.';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.message) msg = resp.message;
                } catch(e) {}
                $('#upload-status-text').text(msg);
                $('#upload-progress-bar').removeClass('bg-success progress-bar-animated').addClass('bg-danger').css('width', '100%');
                $('#upload-percent-text').text('Error');
                $('#upload-detail-text').text('');
                $('#btn-subir').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Archivo');
            }
        });

        xhr.addEventListener('error', function() {
            $('#upload-status-text').text('Error de conexion al subir el archivo.');
            $('#upload-progress-bar').removeClass('bg-success progress-bar-animated').addClass('bg-danger').css('width', '100%');
            $('#upload-percent-text').text('Error');
            $('#upload-detail-text').text('');
            $('#btn-subir').prop('disabled', false).html('<i class="fas fa-upload"></i> Subir Archivo');
        });

        xhr.open('POST', '{{ route("adjuntos.subir", $entrevista->id_e_ind_fvt) }}');
        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
        xhr.send(formData);
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
        let tieneTexto = btn.data('tiene-texto') === 1 || btn.data('tiene-texto') === '1';

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

        // Los PDFs e imágenes siempre se visualizan como archivo, aunque tengan texto extraído
        if (tipo.includes('pdf')) {
            necesitaMarca = true;
            const pdfUrl = '{{ route("adjuntos.ver_pdf", "") }}/' + id;
            contenido = `
                <div class="visor-pdf-wrapper">
                    <div id="visor-pdf-pages">
                        <p class="text-white text-center py-5">
                            <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                            <span class="mt-2 d-block">Cargando PDF...</span>
                        </p>
                    </div>
                </div>
            `;
            window._pendingPdfUrl = pdfUrl;
        } else if (tipo.includes('image')) {
            necesitaMarca = true;
            contenido = `
                <div class="visor-con-marca p-3 text-center" style="background: #333;">
                    <img src="${url}" alt="${nombre}" class="img-fluid">
                    ${generarMarcaAgua()}
                </div>
            `;
        } else if (tieneTexto && !esTranscripcion) {
            // Mostrar documento con texto extraido (TXT, DOCX, etc.)
            let texto = documentosTexto[id] || 'Sin contenido';
            let caracteres = texto.length;
            let palabras = texto.split(/\s+/).filter(w => w.length > 0).length;
            necesitaMarca = true;

            contenido = `
                <div class="visor-documento visor-con-marca">
                    <div class="documento-header">
                        <h5><i class="fas fa-file-alt mr-2"></i>${escapeHtml(nombre)}</h5>
                        <div class="documento-stats">
                            <span class="mr-3"><i class="fas fa-font mr-1"></i>${caracteres.toLocaleString()} caracteres</span>
                            <span><i class="fas fa-paragraph mr-1"></i>${palabras.toLocaleString()} palabras</span>
                        </div>
                    </div>
                    <div class="no-copiar documento-texto" oncontextmenu="return false">${escapeHtml(texto)}</div>
                    ${generarMarcaAgua()}
                </div>
            `;
        } else if (esTranscripcion) {
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
            const audioControls = esAdmin ? 'controls autoplay' : 'controls autoplay controlsList="nodownload"';
            contenido = `
                <div class="p-4 text-center">
                    <i class="fas fa-music fa-4x text-info mb-3"></i>
                    <h5 class="text-white mb-3">${nombre}</h5>
                    <audio ${audioControls} class="w-100">
                        <source src="${url}" type="${tipo}">
                        Su navegador no soporta la reproduccion de audio.
                    </audio>
                </div>
            `;
        } else if (esVideo) {
            const videoControls = esAdmin ? 'controls autoplay' : 'controls autoplay controlsList="nodownload"';
            const esFlv = tipo.includes('flv');
            if (esFlv) {
                contenido = `
                    <div style="background:#000; padding: 4px;">
                        <video ${videoControls} id="flv-visor-player" data-flv-src="${url}" style="width:100%; max-height:500px; display:block;"></video>
                        <div id="flv-error-msg" style="display:none; color:#ff6b6b; padding:12px; text-align:center;"></div>
                    </div>
                `;
            } else {
                contenido = `
                    <video ${videoControls}>
                        <source src="${url}" type="${tipo}">
                        Su navegador no soporta la reproduccion de video.
                    </video>
                `;
            }
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

        // Renderizar PDF.js si aplica
        if (window._pendingPdfUrl) {
            const pdfUrl = window._pendingPdfUrl;
            window._pendingPdfUrl = null;
            renderizarPdfJs(pdfUrl);
        }

        // Inicializar flv.js si hay video FLV en el visor
        var flvEl = document.getElementById('flv-visor-player');
        if (flvEl) {
            var flvErrEl = document.getElementById('flv-error-msg');
            var mostrarErrorFlv = function(msg) {
                if (flvErrEl) { flvErrEl.textContent = msg; flvErrEl.style.display = 'block'; }
                console.error('flv.js:', msg);
            };

            if (typeof flvjs === 'undefined') {
                mostrarErrorFlv('La librería flv.js no está disponible (verifique conexión a CDN).');
            } else if (!flvjs.isSupported()) {
                mostrarErrorFlv('Su navegador no soporta la reproducción de archivos FLV (se requiere Media Source Extensions).');
            } else {
                if (window._flvVisorPlayer) {
                    window._flvVisorPlayer.destroy();
                    window._flvVisorPlayer = null;
                }
                var flvPlayer = flvjs.createPlayer({
                    type: 'flv',
                    url: flvEl.getAttribute('data-flv-src'),
                    isLive: false,
                    hasAudio: true,
                    hasVideo: true,
                });
                flvPlayer.on(flvjs.Events.ERROR, function(errType, errDetail) {
                    var detailStr = JSON.stringify(errDetail || {});
                    // Codec antiguo (Sorenson/VP6): convertir a MP4 con ffmpeg
                    if (detailStr.includes('CodecUnsupported') || detailStr.includes('CODEC_UNSUPPORTED')) {
                        flvPlayer.destroy();
                        window._flvVisorPlayer = null;
                        iniciarConversionFlvYReproducir(flvEl, flvErrEl, id);
                    } else {
                        mostrarErrorFlv('Error al reproducir: ' + errType + ' — ' + detailStr);
                    }
                });
                flvPlayer.attachMediaElement(flvEl);
                flvPlayer.load();
                flvPlayer.play();
                window._flvVisorPlayer = flvPlayer;
            }
        }

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
        if (window._flvVisorPlayer) {
            window._flvVisorPlayer.destroy();
            window._flvVisorPlayer = null;
        }

        $('#visor-container').removeClass('active');
        $('#visor-content').html('');
        $('tr').removeClass('archivo-activo');
        $('.btn-reproducir').removeClass('active');
        currentId = null;
    }

    // Cerrar con tecla Escape + bloquear atajos de copiar en visor de documentos
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#visor-container').hasClass('active')) {
            cerrarVisor();
        }
        // Bloquear Ctrl+C, Ctrl+A dentro del visor de documentos
        if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'a' || e.key === 'C' || e.key === 'A')) {
            if ($('.visor-documento').length > 0 && $('#visor-container').hasClass('active')) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>
@endsection
