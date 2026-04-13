@extends('layouts.app')

@section('title', 'Nueva importación masiva')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Nueva importación — Paso 1 de 4</h1>
                <p class="text-muted mb-0">Subir CSV y configurar acceso al NAS</p>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('importacion.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al listado
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    {{-- Barra de progreso de pasos --}}
    @include('importacion._pasos', ['paso_actual' => 1])

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('importacion.subir') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="modo" id="modo_hidden" value="crear">

        <!-- Selector de modo -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-dark">
                    <div class="card-header"><h3 class="card-title">Modo de importación</h3></div>
                    <div class="card-body pb-2">
                        <div class="d-flex">
                            <div class="custom-control custom-radio mr-4">
                                <input type="radio" id="modo_crear" name="modo" class="custom-control-input" value="crear" checked>
                                <label class="custom-control-label" for="modo_crear">
                                    <strong>Crear expedientes nuevos</strong>
                                    <small class="text-muted d-block">El sistema genera un código nuevo por cada fila del CSV.</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="modo_actualizar" name="modo" class="custom-control-input" value="actualizar">
                                <label class="custom-control-label" for="modo_actualizar">
                                    <strong>Actualizar expedientes existentes</strong>
                                    <small class="text-muted d-block">
                                        El CSV debe incluir el código de entrevista en la <strong>columna 79</strong>
                                        (p.&nbsp;ej. <code>TES-0001-001</code>). Se actualizan metadatos y se agregan archivos nuevos.
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Columna izquierda: CSV y entrevistador -->
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header"><h3 class="card-title">Archivo y entrevistador</h3></div>
                    <div class="card-body">

                        <div class="form-group">
                            <label for="archivo_csv">Archivo CSV <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo_csv" name="archivo_csv"
                                    accept=".csv,.txt" required>
                                <label class="custom-file-label" for="archivo_csv">Seleccionar archivo…</label>
                            </div>
                            <small class="form-text text-muted">
                                Separador: <code>;</code> · Codificación: UTF-8 · Máx. 20 MB<br>
                                Las primeras dos filas se tratan como encabezados y se omiten.
                            </small>
                        </div>

                        <div class="form-group" id="grupo_entrevistador">
                            <label for="id_entrevistador">Entrevistador para asignar expedientes <span class="text-danger js-req-marker">*</span></label>
                            <select class="form-control select2" id="id_entrevistador" name="id_entrevistador" required>
                                <option value="">— Seleccionar —</option>
                                @foreach($entrevistadores as $ent)
                                <option value="{{ $ent->id_entrevistador }}">
                                    {{ $ent->fmt_numero_entrevistador }} — {{ $ent->rel_usuario->name ?? '(sin usuario)' }}
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                El código de cada expediente se generará usando el número de este entrevistador.
                            </small>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Columna derecha: mapeo de rutas NAS -->
            <div class="col-md-6">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Mapeo de rutas NAS → Linux</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Las rutas del CSV usan formato Windows UNC (<code>\\servidor\share\…</code>).
                            Define aquí la correspondencia con la ruta de montaje en el servidor Linux.
                        </p>

                        <div id="mappings-container">
                            <div class="mapping-row row mb-2">
                                <div class="col-5">
                                    <input type="text" class="form-control form-control-sm"
                                        name="path_mappings[0][unc]"
                                        placeholder="\\oceano\Oceano-2"
                                        value="\\oceano\Oceano-2">
                                </div>
                                <div class="col-1 text-center pt-1"><i class="fas fa-arrow-right"></i></div>
                                <div class="col-5">
                                    <input type="text" class="form-control form-control-sm"
                                        name="path_mappings[0][linux]"
                                        placeholder="/mnt/oceano">
                                </div>
                                <div class="col-1"></div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="btn-add-mapping">
                            <i class="fas fa-plus"></i> Agregar otro prefijo
                        </button>

                        <hr>
                        <div class="alert alert-warning alert-sm mb-0 small">
                            <i class="fas fa-info-circle"></i>
                            Si el NAS aún no está montado, deja el campo Linux en blanco.
                            Los expedientes se crearán igualmente pero los archivos quedarán
                            pendientes (se mostrará advertencia).
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila completa: archivos locales (transcripciones, consentimientos, otros) -->
        <div class="col-12">
            <div class="card card-secondary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-folder-open"></i>
                        Archivos locales (transcripciones, consentimientos, otros)
                    </h3>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        Para archivos <strong>no</strong> almacenados en el NAS (p.&nbsp;ej. transcripciones .txt
                        copiadas directamente al servidor), indica la carpeta del servidor donde están depositados:
                    </p>
                    <div class="form-group mb-2">
                        <input type="text" class="form-control form-control-sm font-monospace"
                            id="dir_local" name="dir_local"
                            value="{{ $dirTranscripciones }}"
                            placeholder="{{ $dirTranscripciones }}">
                        <small class="form-text text-muted">
                            Ruta absoluta en el servidor Linux. Déjala como está si copiaste los archivos a la carpeta por defecto.
                        </small>
                    </div>
                    <p class="text-muted small mb-0">
                        En el CSV pon <strong>solo el nombre del archivo</strong> (sin ruta) en las columnas de
                        Transcripción, Consentimiento u Otros archivos.
                        El sistema lo buscará automáticamente en esa carpeta.<br>
                        Los archivos de audio/video (columna «Ruta de resguardo») siguen requiriendo el mapeo NAS de arriba.
                    </p>

                    <hr>

                    <div class="form-group mb-0">
                        <label><i class="fas fa-file-alt"></i> Tratamiento de archivos de transcripción (col. 77)</label>
                        <div class="mt-1">
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" id="tr_adjunto" name="tratamiento_transcripcion"
                                    class="custom-control-input" value="adjunto">
                                <label class="custom-control-label" for="tr_adjunto">
                                    Guardar como adjunto documental
                                    <small class="text-muted d-block">El archivo queda disponible para descarga, sin ingresar su contenido.</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mt-2">
                                <input type="radio" id="tr_automatizada" name="tratamiento_transcripcion"
                                    class="custom-control-input" value="automatizada" checked>
                                <label class="custom-control-label" for="tr_automatizada">
                                    Ingestar como transcripción automatizada
                                    <small class="text-muted d-block">El contenido del .txt se guarda en el campo de transcripción automatizada del expediente.</small>
                                </label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mt-2">
                                <input type="radio" id="tr_ambos" name="tratamiento_transcripcion"
                                    class="custom-control-input" value="ambos">
                                <label class="custom-control-label" for="tr_ambos">
                                    Ambos
                                    <small class="text-muted d-block">Guarda el archivo como adjunto y además ingesta su contenido como transcripción automatizada.</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mt-2 mb-4">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-upload"></i> Subir y continuar
            </button>
        </div>
    </form>

</div>
</section>
@endsection

@push('scripts')
<script>
// Custom file input label
$('#archivo_csv').on('change', function () {
    var name = this.files[0] ? this.files[0].name : 'Seleccionar archivo…';
    $(this).siblings('.custom-file-label').first().text(name);
});

// Agregar filas de mapeo dinámicamente
var mappingIdx = 1;
document.getElementById('btn-add-mapping').addEventListener('click', function () {
    var container = document.getElementById('mappings-container');
    var div = document.createElement('div');
    div.className = 'mapping-row row mb-2';
    div.innerHTML = `
        <div class="col-5">
            <input type="text" class="form-control form-control-sm"
                name="path_mappings[${mappingIdx}][unc]" placeholder="\\\\servidor\\share">
        </div>
        <div class="col-1 text-center pt-1"><i class="fas fa-arrow-right"></i></div>
        <div class="col-5">
            <input type="text" class="form-control form-control-sm"
                name="path_mappings[${mappingIdx}][linux]" placeholder="/mnt/punto">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-xs btn-outline-danger mt-1 btn-remove-mapping">
                <i class="fas fa-times"></i>
            </button>
        </div>`;
    container.appendChild(div);
    mappingIdx++;
});

document.getElementById('mappings-container').addEventListener('click', function (e) {
    if (e.target.closest('.btn-remove-mapping')) {
        e.target.closest('.mapping-row').remove();
    }
});

// Modo crear/actualizar
function aplicarModo(modo) {
    var $grp = $('#grupo_entrevistador');
    var $sel = $('#id_entrevistador');
    if (modo === 'actualizar') {
        $grp.hide();
        $sel.prop('required', false);
    } else {
        $grp.show();
        $sel.prop('required', true);
    }
}
$('input[name="modo"]').on('change', function () { aplicarModo(this.value); });
aplicarModo($('input[name="modo"]:checked').val());

// Select2
$(document).ready(function () {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
});
</script>
@endpush
