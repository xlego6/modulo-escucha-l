@extends('layouts.app')

@section('title', 'Editar Anonimizacion')
@section('content_header')
Anonimizar: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    .entity-anonimizada {
        background-color: #343a40;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
    }
    .entity-original {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
    }
    .entity-PER { background-color: #cce5ff; border: 1px solid #b8daff; }
    .entity-LOC { background-color: #d4edda; border: 1px solid #c3e6cb; }
    .entity-ORG { background-color: #d1ecf1; border: 1px solid #bee5eb; }
    .entity-DATE { background-color: #e2e3e5; border: 1px solid #d6d8db; }
    .entity-EVENT { background-color: #fff3cd; border: 1px solid #ffeeba; }
    .entity-GUN { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .entity-MISC { background-color: #d6d8d9; border: 1px solid #c6c8ca; }
    .transcripcion-container {
        line-height: 2;
        font-size: 14px;
        max-height: 500px;
        overflow-y: auto;
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
    #texto_anonimizado {
        border-radius: 0 0 4px 4px !important;
    }
    /* Estilos para editor visual de entidades */
    .entity-clickable {
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .entity-clickable:hover {
        opacity: 0.8;
        transform: scale(1.02);
    }
    .entity-cubierta {
        background-color: #343a40;
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        display: inline;
    }
    .entity-descubierta {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        display: inline;
        text-decoration: line-through;
        opacity: 0.7;
    }
    .entity-descubierta.entity-PER { background-color: #cce5ff; border: 1px solid #b8daff; color: #004085; }
    .entity-descubierta.entity-LOC { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .entity-descubierta.entity-ORG { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    .entity-descubierta.entity-DATE { background-color: #e2e3e5; border: 1px solid #d6d8db; color: #383d41; }
    .entity-descubierta.entity-EVENT { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
    .entity-descubierta.entity-GUN { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .entity-descubierta.entity-MISC { background-color: #d6d8d9; border: 1px solid #c6c8ca; color: #1b1e21; }
    .editor-visual-container {
        line-height: 2.2;
        font-size: 14px;
        min-height: 400px;
        max-height: 600px;
        overflow-y: auto;
        padding: 15px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        white-space: pre-wrap;
    }
    .leyenda-entidades {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 10px;
    }
    .leyenda-item {
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .contador-entidades {
        font-size: 11px;
        background: #6c757d;
        color: #fff;
        padding: 1px 5px;
        border-radius: 10px;
        margin-left: 3px;
    }
    /* Menu contextual para agregar entidades */
    .entity-menu {
        position: fixed;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 8px 0;
        z-index: 1050;
        min-width: 160px;
        display: none;
    }
    .entity-menu.show {
        display: block;
    }
    .entity-menu-header {
        padding: 4px 12px;
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        border-bottom: 1px solid #eee;
        margin-bottom: 4px;
    }
    .entity-menu-item {
        padding: 6px 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
    }
    .entity-menu-item:hover {
        background: #f8f9fa;
    }
    .entity-menu-item .badge {
        min-width: 45px;
        text-align: center;
    }
    .texto-seleccionable {
        cursor: text;
    }
    .texto-seleccionable::selection {
        background: #ffc107;
        color: #000;
    }
</style>
@endsection

@section('content')
<div class="row">
    {{-- Panel izquierdo: Informacion y Configuracion --}}
    <div class="col-md-3">
        {{-- Estado de la asignacion --}}
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Asignacion</h3>
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
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y') }}</dd>
                </dl>

                @if($asignacion->estado == 'rechazada' && $asignacion->comentario_revision)
                <hr>
                <div class="alert alert-danger mb-0">
                    <strong><i class="fas fa-exclamation-circle mr-1"></i>Motivo del rechazo:</strong><br>
                    {{ $asignacion->comentario_revision }}
                </div>
                @endif
            </div>
        </div>

        {{-- Configuracion de tipos --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Configuracion</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Tipos a anonimizar:</p>
                @php
                    $tiposActivos = explode(',', $asignacion->tipos_anonimizar ?? 'PER,LOC');
                @endphp
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-PER" value="PER"
                               {{ in_array('PER', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-PER">
                            <span class="badge badge-primary">PER</span> Personas
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-LOC" value="LOC"
                               {{ in_array('LOC', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-LOC">
                            <span class="badge badge-success">LOC</span> Lugares
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-ORG" value="ORG"
                               {{ in_array('ORG', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-ORG">
                            <span class="badge badge-info">ORG</span> Organizaciones
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-DATE" value="DATE"
                               {{ in_array('DATE', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-DATE">
                            <span class="badge badge-secondary">DATE</span> Fechas
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-EVENT" value="EVENT"
                               {{ in_array('EVENT', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-EVENT">
                            <span class="badge badge-warning">EVENT</span> Eventos
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-GUN" value="GUN"
                               {{ in_array('GUN', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-GUN">
                            <span class="badge badge-danger">GUN</span> Armas
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-MISC" value="MISC"
                               {{ in_array('MISC', $tiposActivos) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="check-MISC">
                            <span class="badge badge-dark">MISC</span> Otros
                        </label>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <label class="small">Formato:</label>
                    <select class="form-control form-control-sm" id="formato">
                        <option value="brackets" {{ $asignacion->formato_reemplazo == 'brackets' ? 'selected' : '' }}>[TIPO]</option>
                        <option value="numbered" {{ $asignacion->formato_reemplazo == 'numbered' ? 'selected' : '' }}>[TIPO_1]</option>
                        <option value="redacted" {{ $asignacion->formato_reemplazo == 'redacted' ? 'selected' : '' }}>[REDACTADO]</option>
                        <option value="asterisks" {{ $asignacion->formato_reemplazo == 'asterisks' ? 'selected' : '' }}>***</option>
                    </select>
                </div>

                <button class="btn btn-outline-secondary btn-sm btn-block" onclick="generarAnonimizacion()">
                    <i class="fas fa-magic mr-1"></i>Generar Automatico
                </button>
            </div>
        </div>

        {{-- Resumen de entidades --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Entidades</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody id="resumen-entidades">
                        <!-- Se llena dinamicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Panel derecho: Editor --}}
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-secret mr-2"></i>Texto Anonimizado (Editable)</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-default" onclick="mostrarVista('editar')">
                            <i class="fas fa-edit"></i> Editar texto
                        </button>
                        <button type="button" class="btn btn-default active" onclick="mostrarVista('visual')">
                            <i class="fas fa-mouse-pointer"></i> Editor visual
                        </button>
                    </div>
                </div>
            </div>
            <form action="{{ route('procesamientos.guardar-anonimizacion-asignada', $asignacion->id_asignacion) }}"
                  method="POST" id="formAnonimizacion">
                @csrf
                <input type="hidden" name="tipos_anonimizar" id="input_tipos">
                <input type="hidden" name="formato_reemplazo" id="input_formato">
                <input type="hidden" name="entidades_manuales" id="input_entidades_manuales">
                <input type="hidden" name="estado_entidades" id="input_estado_entidades">

                <div class="card-body p-2">
                    {{-- Vista Edicion (texto liquido) --}}
                    <div id="vista-editar" style="display: none;">
                        @include('partials.editor-toolbar', ['targetId' => 'texto_anonimizado', 'showTimestamp' => false, 'showSpeakers' => false])
                        <textarea name="texto_anonimizado" id="texto_anonimizado" class="form-control"
                                  style="min-height: 500px; resize: vertical; font-family: monospace;">{{ $asignacion->texto_anonimizado ?? '' }}</textarea>
                    </div>

                    @php $transcripcionOriginal = $entrevista->getTextoParaProcesamiento(); @endphp

                    {{-- Vista Visual con comparacion (entidades clicables) --}}
                    <div id="vista-visual" class="p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="leyenda-entidades">
                                <span class="leyenda-item"><span class="entity-cubierta" style="font-size:11px">[PER]</span> Cubierta</span>
                                <span class="leyenda-item"><span class="entity-descubierta entity-PER" style="font-size:11px;text-decoration:line-through">Juan</span> Visible</span>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-dark mr-1" onclick="cubrirTodas()" title="Cubrir todas las entidades">
                                    <i class="fas fa-eye-slash"></i> Todas
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="descubrirTodas()" title="Descubrir todas las entidades">
                                    <i class="fas fa-eye"></i> Ninguna
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-file-alt mr-1"></i>Texto Original
                                    <small class="text-secondary">(seleccione texto para etiquetar)</small>
                                </h6>
                                <div class="editor-visual-container texto-seleccionable" id="texto-original-marcado" style="background: #fffbea;">
                                    {{-- Texto original con entidades resaltadas --}}
                                </div>
                            </div>

                        {{-- Menu contextual para agregar entidades --}}
                        <div class="entity-menu" id="entity-menu">
                            <div class="entity-menu-header">Etiquetar como:</div>
                            <div class="entity-menu-item" onclick="agregarEntidad('PER')">
                                <span class="badge badge-primary">PER</span> Persona
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('LOC')">
                                <span class="badge badge-success">LOC</span> Lugar
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('ORG')">
                                <span class="badge badge-info">ORG</span> Organizacion
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('DATE')">
                                <span class="badge badge-secondary">DATE</span> Fecha
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('EVENT')">
                                <span class="badge badge-warning">EVENT</span> Evento
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('GUN')">
                                <span class="badge badge-danger">GUN</span> Arma
                            </div>
                            <div class="entity-menu-item" onclick="agregarEntidad('MISC')">
                                <span class="badge badge-dark">MISC</span> Otros
                            </div>
                        </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-user-secret mr-1"></i>Anonimizado
                                    <small class="text-secondary">(clic para editar)</small>
                                </h6>
                                <div class="editor-visual-container" id="editor-visual">
                                    {{-- Se llena dinamicamente con entidades clicables --}}
                                </div>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="badge badge-dark" id="contador-cubiertas">0</span> cubiertas
                            <span class="badge badge-secondary ml-2" id="contador-descubiertas">0</span> visibles
                            <span class="text-muted small ml-3" id="sync-status"></span>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Guardar Cambios
                    </button>
                    <span class="text-muted ml-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        <span id="charCount">{{ strlen($asignacion->texto_anonimizado ?? '') }}</span> caracteres
                    </span>
                </div>
            </form>
        </div>

        {{-- Boton Enviar a Revision --}}
        <div class="card card-outline card-success">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">¿Finalizo la anonimizacion?</h5>
                        <p class="text-muted mb-0">
                            Una vez enviada, un revisor verificara su trabajo.
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <form action="{{ route('procesamientos.enviar-anonimizacion-revision', $asignacion->id_asignacion) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('¿Enviar a revision? No podra editarla hasta que sea revisada.')">
                                <i class="fas fa-paper-plane mr-1"></i> Enviar a Revision
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@endsection

@section('js')
@include('partials.editor-toolbar-js')
<script>
var entidades = @json($entidades);
var textoOriginal = @json($entrevista->getTextoParaProcesamiento() ?? '');

// Estado de las entidades en el editor visual
// Cada entidad tiene: {id, text, type, start, end, reemplazo, cubierta: true/false}
var estadoEntidades = [];

// Variables para seleccion de texto
var seleccionActual = null;

$(document).ready(function() {
    actualizarResumen();
    actualizarInputs();

    // Si no hay texto anonimizado, generar automaticamente
    if ($('#texto_anonimizado').val().trim() === '') {
        generarAnonimizacion();
    }

    // Inicializar editor visual
    inicializarEditorVisual();

    // Actualizar contador
    $('#texto_anonimizado').on('input', function() {
        $('#charCount').text($(this).val().length);
        $('#texto-preview').text($(this).val());
    });

    // Actualizar inputs al cambiar opciones
    $('.tipo-check, #formato').on('change', function() {
        actualizarInputs();
        inicializarEditorVisual();
    });

    // Detectar seleccion de texto en el panel original
    $('#texto-original-marcado').on('mouseup', function(e) {
        var selection = window.getSelection();
        var selectedText = selection.toString().trim();

        if (selectedText.length > 0) {
            // Buscar la posicion del texto seleccionado en el texto original
            var startPos = textoOriginal.indexOf(selectedText);
            if (startPos !== -1) {
                seleccionActual = {
                    text: selectedText,
                    start: startPos,
                    end: startPos + selectedText.length
                };

                // Mostrar menu contextual (position: fixed usa coordenadas del viewport)
                var menu = $('#entity-menu');
                var menuWidth = 160;
                var menuHeight = 280;
                var posX = e.clientX + 5;
                var posY = e.clientY + 5;

                // Evitar que el menu se salga de la pantalla
                if (posX + menuWidth > window.innerWidth) {
                    posX = e.clientX - menuWidth - 5;
                }
                if (posY + menuHeight > window.innerHeight) {
                    posY = e.clientY - menuHeight - 5;
                }

                menu.css({
                    top: posY,
                    left: posX
                }).addClass('show');
            }
        }
    });

    // Ocultar menu al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#entity-menu').length && !$(e.target).closest('#texto-original-marcado').length) {
            $('#entity-menu').removeClass('show');
            seleccionActual = null;
        }
    });

    // Antes de enviar el formulario, guardar las entidades manuales y el estado de todas
    $('#formAnonimizacion').on('submit', function() {
        // Entidades manuales nuevas
        var entidadesManuales = estadoEntidades.filter(function(ent) {
            return ent.manual === true;
        });
        $('#input_entidades_manuales').val(JSON.stringify(entidadesManuales));

        // Estado de todas las entidades (para saber cuales estan descubiertas)
        var estadoParaGuardar = estadoEntidades.map(function(ent) {
            return {
                id: ent.id,
                text: ent.text,
                start: ent.start,
                end: ent.end,
                cubierta: ent.cubierta
            };
        });
        $('#input_estado_entidades').val(JSON.stringify(estadoParaGuardar));
    });
});

function actualizarInputs() {
    var tipos = [];
    $('.tipo-check:checked').each(function() {
        tipos.push($(this).val());
    });
    $('#input_tipos').val(tipos.join(','));
    $('#input_formato').val($('#formato').val());
}

// =====================================================
// EDITOR VISUAL - Entidades clicables
// =====================================================

function inicializarEditorVisual() {
    var tiposSeleccionados = [];
    $('.tipo-check:checked').each(function() {
        tiposSeleccionados.push($(this).val());
    });

    var formato = $('#formato').val();

    // Preparar TODAS las entidades (no filtrar por tipo)
    estadoEntidades = [];
    var contadores = {};
    var idCounter = 0;

    // Ordenar por posicion ascendente para procesar en orden
    var entidadesOrdenadas = [...entidades]
        .filter(e => e.text)
        .sort((a, b) => (a.start || 0) - (b.start || 0));

    entidadesOrdenadas.forEach(function(ent) {
        // Contador por tipo
        if (!contadores[ent.type]) contadores[ent.type] = 0;
        contadores[ent.type]++;

        // Generar reemplazo segun formato
        var reemplazo = '';
        switch(formato) {
            case 'brackets':
                reemplazo = '[' + ent.type + ']';
                break;
            case 'numbered':
                reemplazo = '[' + ent.type + '_' + contadores[ent.type] + ']';
                break;
            case 'redacted':
                reemplazo = '[REDACTADO]';
                break;
            case 'asterisks':
                reemplazo = '*'.repeat(ent.text.length);
                break;
        }

        // Determinar si esta cubierta o descubierta
        // - Si el tipo NO esta seleccionado: siempre descubierta (no anonimizar)
        // - Si el tipo esta seleccionado: segun el estado guardado (excluir)
        var tipoSeleccionado = tiposSeleccionados.includes(ent.type);
        var estaCubierta = tipoSeleccionado ? !ent.excluir : false;

        estadoEntidades.push({
            id: idCounter++,
            text: ent.text,
            type: ent.type,
            start: ent.start || 0,
            end: ent.end || (ent.start + ent.text.length),
            reemplazo: reemplazo,
            cubierta: estaCubierta,
            manual: ent.manual || false
        });
    });

    renderizarEditorVisual();
}

function renderizarEditorVisual() {
    if (!textoOriginal) {
        $('#editor-visual').html('<p class="text-muted text-center py-5">No hay texto disponible</p>');
        $('#texto-original-marcado').html('<p class="text-muted text-center py-5">No hay texto disponible</p>');
        return;
    }

    // Crear mapa de posiciones de entidades (sin duplicados ni superposiciones)
    var entidadesUnicas = [];
    var posicionesOcupadas = [];

    // Ordenar por posicion ascendente primero
    var entidadesOrdenadas = [...estadoEntidades].sort((a, b) => a.start - b.start);

    entidadesOrdenadas.forEach(function(ent) {
        // Verificar que no se superponga con ninguna entidad ya agregada
        var superpone = posicionesOcupadas.some(function(pos) {
            return (ent.start < pos.end && ent.end > pos.start);
        });

        if (!superpone) {
            entidadesUnicas.push(ent);
            posicionesOcupadas.push({ start: ent.start, end: ent.end });
        }
    });

    // Ordenar por posicion descendente para reemplazar de atras hacia adelante
    entidadesUnicas.sort((a, b) => b.start - a.start);

    // === COLUMNA DERECHA: Editor con entidades clicables ===
    var htmlEditor = textoOriginal;

    entidadesUnicas.forEach(function(ent) {
        var antes = htmlEditor.substring(0, ent.start);
        var despues = htmlEditor.substring(ent.end);

        var claseEstado = ent.cubierta ? 'entity-cubierta' : 'entity-descubierta entity-' + ent.type;
        var textoMostrar = ent.cubierta ? ent.reemplazo : ent.text;
        var textoOriginalEscapado = escapeHtml(ent.text).replace(/"/g, '&quot;');
        var reemplazoEscapado = escapeHtml(ent.reemplazo).replace(/"/g, '&quot;');

        var span = '<span class="entity-clickable ' + claseEstado + '" ' +
                   'data-id="' + ent.id + '" ' +
                   'data-text="' + textoOriginalEscapado + '" ' +
                   'data-reemplazo="' + reemplazoEscapado + '" ' +
                   'data-type="' + ent.type + '" ' +
                   'data-cubierta="' + (ent.cubierta ? '1' : '0') + '">' +
                   escapeHtml(textoMostrar) +
                   '</span>';

        htmlEditor = antes + span + despues;
    });

    // === COLUMNA IZQUIERDA: Original con entidades resaltadas (no clicables) ===
    var htmlOriginal = textoOriginal;

    entidadesUnicas.forEach(function(ent) {
        var antes = htmlOriginal.substring(0, ent.start);
        var despues = htmlOriginal.substring(ent.end);

        var span = '<span class="entity-original entity-' + ent.type + '">' +
                   escapeHtml(ent.text) +
                   '</span>';

        htmlOriginal = antes + span + despues;
    });

    // Convertir saltos de linea
    htmlEditor = htmlEditor.replace(/\n/g, '<br>');
    htmlOriginal = htmlOriginal.replace(/\n/g, '<br>');

    $('#editor-visual').html(htmlEditor);
    $('#texto-original-marcado').html(htmlOriginal);

    // Agregar event listeners solo al editor
    $('.entity-clickable').on('click', function() {
        toggleEntidad($(this));
    });

    actualizarContadores();
}

function toggleEntidad($span) {
    var id = parseInt($span.data('id'));
    var ent = estadoEntidades.find(e => e.id === id);

    if (!ent) return;

    // Toggle estado
    ent.cubierta = !ent.cubierta;

    // Actualizar visualizacion del span
    var claseEstado = ent.cubierta ? 'entity-cubierta' : 'entity-descubierta entity-' + ent.type;
    var textoMostrar = ent.cubierta ? ent.reemplazo : ent.text;
    var tooltip = ent.cubierta
        ? 'Clic para descubrir: ' + ent.text
        : 'Clic para cubrir como: ' + ent.reemplazo;

    $span.removeClass('entity-cubierta entity-descubierta entity-PER entity-LOC entity-ORG entity-DATE entity-EVENT entity-GUN entity-MISC');
    $span.addClass('entity-clickable ' + claseEstado);
    $span.text(textoMostrar);
    $span.attr('title', tooltip);
    $span.data('cubierta', ent.cubierta ? '1' : '0');

    actualizarContadores();
    sincronizarConTextarea();
}

function cubrirTodas() {
    estadoEntidades.forEach(function(ent) {
        ent.cubierta = true;
    });
    renderizarEditorVisual();
    sincronizarConTextarea();
}

function descubrirTodas() {
    estadoEntidades.forEach(function(ent) {
        ent.cubierta = false;
    });
    renderizarEditorVisual();
    sincronizarConTextarea();
}

function agregarEntidad(tipo) {
    if (!seleccionActual) return;

    var formato = $('#formato').val();
    var textoABuscar = seleccionActual.text;
    var entidadesAgregadas = 0;

    // Buscar TODAS las instancias de este texto en el documento
    var instancias = encontrarTodasInstancias(textoOriginal, textoABuscar);

    // Filtrar instancias que ya tienen una entidad en esa posicion
    instancias = instancias.filter(function(inst) {
        return !existeEntidadEnPosicion(inst.start, inst.end);
    });

    if (instancias.length === 0) {
        $('#entity-menu').removeClass('show');
        seleccionActual = null;
        if (typeof toastr !== 'undefined') {
            toastr.warning('No se encontraron instancias nuevas de "' + textoABuscar + '"');
        }
        return;
    }

    // Para formato numbered: contar textos UNICOS de este tipo (no instancias)
    // Todas las instancias del mismo texto comparten el mismo numero
    var numeroParaEstaPalabra = obtenerNumeroParaPalabra(tipo, textoABuscar);

    // Generar reemplazo segun formato (mismo para todas las instancias)
    var reemplazo = '';
    switch(formato) {
        case 'brackets':
            reemplazo = '[' + tipo + ']';
            break;
        case 'numbered':
            reemplazo = '[' + tipo + '_' + numeroParaEstaPalabra + ']';
            break;
        case 'redacted':
            reemplazo = '[REDACTADO]';
            break;
        case 'asterisks':
            reemplazo = '*'.repeat(textoABuscar.length);
            break;
    }

    // Agregar cada instancia como entidad (todas con el mismo reemplazo)
    instancias.forEach(function(inst) {
        var nuevaEntidad = {
            id: Date.now() + entidadesAgregadas,
            text: textoABuscar,
            type: tipo,
            start: inst.start,
            end: inst.end,
            reemplazo: reemplazo,
            cubierta: true,
            manual: true
        };

        estadoEntidades.push(nuevaEntidad);
        entidadesAgregadas++;
    });

    // Ordenar por posicion
    estadoEntidades.sort((a, b) => a.start - b.start);

    // Ocultar menu
    $('#entity-menu').removeClass('show');
    seleccionActual = null;

    // Limpiar seleccion del navegador
    window.getSelection().removeAllRanges();

    // Re-renderizar
    renderizarEditorVisual();
    sincronizarConTextarea();
    actualizarResumen();

    // Notificar
    if (typeof toastr !== 'undefined') {
        if (entidadesAgregadas === 1) {
            toastr.success('Entidad "' + textoABuscar + '" agregada como ' + tipo);
        } else {
            toastr.success(entidadesAgregadas + ' instancias de "' + textoABuscar + '" agregadas como ' + tipo);
        }
    }
}

// Obtener el numero para una palabra de un tipo dado
// Si la palabra ya existe, devuelve su numero; si no, asigna uno nuevo
function obtenerNumeroParaPalabra(tipo, texto) {
    // Buscar si ya existe una entidad con este tipo y texto
    var existente = estadoEntidades.find(function(ent) {
        return ent.type === tipo && ent.text === texto;
    });

    if (existente && existente.reemplazo) {
        // Extraer el numero del reemplazo existente (ej: [PER_3] -> 3)
        var match = existente.reemplazo.match(/\[.*?_(\d+)\]/);
        if (match) {
            return parseInt(match[1]);
        }
    }

    // Si no existe, contar cuantos textos UNICOS de este tipo hay
    var textosUnicos = new Set();
    estadoEntidades.forEach(function(ent) {
        if (ent.type === tipo) {
            textosUnicos.add(ent.text);
        }
    });

    return textosUnicos.size + 1;
}

// Buscar todas las instancias de un texto en el documento
function encontrarTodasInstancias(texto, buscar) {
    var instancias = [];
    var pos = 0;

    while (true) {
        var idx = texto.indexOf(buscar, pos);
        if (idx === -1) break;

        instancias.push({
            start: idx,
            end: idx + buscar.length
        });

        pos = idx + 1;
    }

    return instancias;
}

// Verificar si ya existe una entidad en una posicion
function existeEntidadEnPosicion(start, end) {
    return estadoEntidades.some(function(ent) {
        // Verificar superposicion
        return (start < ent.end && end > ent.start);
    });
}

function actualizarContadores() {
    var cubiertas = estadoEntidades.filter(e => e.cubierta).length;
    var descubiertas = estadoEntidades.filter(e => !e.cubierta).length;

    $('#contador-cubiertas').text(cubiertas);
    $('#contador-descubiertas').text(descubiertas);
}

function sincronizarConTextarea() {
    // Generar texto final basado en el estado de las entidades
    var texto = textoOriginal;

    // Crear mapa de posiciones de entidades (sin duplicados)
    var posicionesUsadas = new Set();
    var entidadesUnicas = [];

    estadoEntidades.forEach(function(ent) {
        var key = ent.start + '-' + ent.end;
        if (!posicionesUsadas.has(key)) {
            posicionesUsadas.add(key);
            entidadesUnicas.push(ent);
        }
    });

    // Ordenar por posicion descendente
    entidadesUnicas.sort((a, b) => b.start - a.start);

    // Reemplazar solo las entidades cubiertas
    entidadesUnicas.forEach(function(ent) {
        if (ent.cubierta) {
            var antes = texto.substring(0, ent.start);
            var despues = texto.substring(ent.end);
            texto = antes + ent.reemplazo + despues;
        }
    });

    // Actualizar textarea
    $('#texto_anonimizado').val(texto);
    $('#charCount').text(texto.length);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// =====================================================
// FUNCIONES ORIGINALES
// =====================================================

function generarAnonimizacion() {
    var tiposSeleccionados = [];
    $('.tipo-check:checked').each(function() {
        tiposSeleccionados.push($(this).val());
    });

    var formato = $('#formato').val();
    var texto = textoOriginal;
    var contadores = {};

    // Ordenar entidades por posicion descendente para reemplazar de atras hacia adelante
    var entidadesOrdenadas = [...entidades].sort((a, b) => (b.start || 0) - (a.start || 0));

    // Reemplazar entidades
    entidadesOrdenadas.forEach(function(ent) {
        if (!tiposSeleccionados.includes(ent.type)) return;

        // Contador por tipo
        if (!contadores[ent.type]) contadores[ent.type] = 0;
        contadores[ent.type]++;

        // Generar reemplazo
        var reemplazo = '';
        switch(formato) {
            case 'brackets':
                reemplazo = '[' + ent.type + ']';
                break;
            case 'numbered':
                reemplazo = '[' + ent.type + '_' + contadores[ent.type] + ']';
                break;
            case 'redacted':
                reemplazo = '[REDACTADO]';
                break;
            case 'asterisks':
                reemplazo = '*'.repeat(ent.text.length);
                break;
        }

        // Reemplazar en texto
        if (ent.text) {
            var regex = new RegExp(escapeRegex(ent.text), 'gi');
            texto = texto.replace(regex, reemplazo);
        }
    });

    $('#texto_anonimizado').val(texto);
    $('#charCount').text(texto.length);
    $('#texto-preview').text(texto);

    actualizarResumen();

    // Re-inicializar editor visual
    inicializarEditorVisual();
}

function actualizarResumen() {
    var tiposSeleccionados = [];
    $('.tipo-check:checked').each(function() {
        tiposSeleccionados.push($(this).val());
    });

    var resumen = {};
    var total = 0;

    entidades.forEach(function(ent) {
        if (tiposSeleccionados.includes(ent.type)) {
            if (!resumen[ent.type]) resumen[ent.type] = 0;
            resumen[ent.type]++;
            total++;
        }
    });

    var html = '';
    for (var tipo in resumen) {
        html += '<tr><td><span class="badge badge-dark">' + tipo + '</span></td>' +
                '<td class="text-right">' + resumen[tipo] + '</td></tr>';
    }
    html += '<tr class="table-active"><td><strong>Total</strong></td>' +
            '<td class="text-right"><strong>' + total + '</strong></td></tr>';

    $('#resumen-entidades').html(html);
}

function mostrarVista(vista) {
    $('#vista-editar').hide();
    $('#vista-visual').hide();

    if (vista === 'editar') {
        $('#vista-editar').show();
    } else if (vista === 'visual') {
        $('#vista-visual').show();
    }

    $('.card-tools .btn').removeClass('active');
    $('.card-tools .btn[onclick*="' + vista + '"]').addClass('active');
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
</script>
@endsection
