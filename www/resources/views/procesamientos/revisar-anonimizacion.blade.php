@extends('layouts.app')

@section('title', 'Revisar Anonimizacion')
@section('content_header')
Revisar Anonimizacion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    .entity-badge {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 2px;
        display: inline-block;
    }
    .entity-PER { background-color: #cce5ff; }
    .entity-LOC { background-color: #d4edda; }
    .entity-ORG { background-color: #d1ecf1; }
    .entity-DATE { background-color: #e2e3e5; }
    .entity-EVENT { background-color: #fff3cd; }
    .entity-GUN { background-color: #f8d7da; }
    .entity-MISC { background-color: #d6d8d9; }

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
    .entity-original {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
    }
    .entity-original.entity-PER { background-color: #cce5ff; border: 1px solid #b8daff; }
    .entity-original.entity-LOC { background-color: #d4edda; border: 1px solid #c3e6cb; }
    .entity-original.entity-ORG { background-color: #d1ecf1; border: 1px solid #bee5eb; }
    .entity-original.entity-DATE { background-color: #e2e3e5; border: 1px solid #d6d8db; }
    .entity-original.entity-EVENT { background-color: #fff3cd; border: 1px solid #ffeeba; }
    .entity-original.entity-GUN { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .entity-original.entity-MISC { background-color: #d6d8d9; border: 1px solid #c6c8ca; }
    /* Menu contextual para agregar entidades */
    .entity-menu {
        position: absolute;
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
    {{-- Panel izquierdo: Informacion --}}
    <div class="col-md-4">
        {{-- Informacion de la asignacion --}}
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Revision Pendiente</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Anonimizador:</dt>
                    <dd class="col-sm-7">
                        <i class="fas fa-user mr-1"></i>
                        {{ $asignacion->rel_anonimizador->rel_usuario->name ?? 'N/A' }}
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

                    <dt class="col-sm-5">Tipos:</dt>
                    <dd class="col-sm-7">
                        @foreach(explode(',', $asignacion->tipos_anonimizar ?? 'PER,LOC') as $tipo)
                            <span class="badge badge-dark">{{ $tipo }}</span>
                        @endforeach
                    </dd>

                    <dt class="col-sm-5">Formato:</dt>
                    <dd class="col-sm-7"><code>{{ $asignacion->formato_reemplazo }}</code></dd>
                </dl>
            </div>
        </div>

        {{-- Informacion de la entrevista --}}
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

        {{-- Resumen de entidades --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Entidades Detectadas</h3>
            </div>
            <div class="card-body">
                @php
                    $entidadesPorTipo = collect($entidades)->groupBy('type');
                @endphp
                @foreach($entidadesPorTipo as $tipo => $items)
                <div class="mb-2">
                    <strong class="badge badge-dark">{{ $tipo }}</strong>
                    <span class="text-muted">({{ $items->count() }})</span>
                    <div class="mt-1">
                        @foreach($items->take(5) as $ent)
                            <span class="entity-badge entity-{{ $tipo }}">{{ $ent['text'] }}</span>
                        @endforeach
                        @if($items->count() > 5)
                            <span class="text-muted">+{{ $items->count() - 5 }} mas</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Botones de accion --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-2"></i>Decision</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('procesamientos.aprobar-anonimizacion', $asignacion->id_asignacion) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <label>Comentario (opcional)</label>
                        <textarea name="comentario" class="form-control" rows="2" placeholder="Comentario de aprobacion..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" onclick="return confirm('¿Aprobar esta anonimizacion como version final?')">
                        <i class="fas fa-check mr-1"></i> Aprobar Anonimizacion
                    </button>
                </form>

                <hr>

                <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalRechazar">
                    <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                </button>

                <hr>

                <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    {{-- Panel derecho: Anonimizacion (Editable) --}}
    <div class="col-md-8">
        @php $transcripcionOriginal = $entrevista->getTextoParaProcesamiento(); @endphp

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Anonimizacion (Editable)</h3>
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
            <form action="{{ route('procesamientos.guardar-anonimizacion-asignada', $asignacion->id_asignacion) }}" method="POST" id="formAnonimizacion">
                @csrf
                <input type="hidden" name="entidades_manuales" id="input_entidades_manuales">
                <input type="hidden" name="estado_entidades" id="input_estado_entidades">
                <div class="card-body p-2">
                    {{-- Vista Edicion (texto liquido) --}}
                    <div id="vista-editar" style="display: none;">
                        <textarea name="texto_anonimizado" id="texto_anonimizado" class="form-control"
                                  style="min-height: 500px; resize: vertical; font-family: monospace;">{{ $asignacion->texto_anonimizado }}</textarea>
                    </div>

                    {{-- Vista Visual con comparacion (entidades clicables) --}}
                    <div id="vista-visual" class="p-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="leyenda-entidades">
                                <span class="leyenda-item"><span class="entity-cubierta" style="font-size:11px">[PER]</span> Cubierta</span>
                                <span class="leyenda-item"><span class="entity-descubierta entity-PER" style="font-size:11px;text-decoration:line-through">Juan</span> Visible</span>
                                <span class="text-muted small ml-2">| Clic en entidad para cubrir/descubrir</span>
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
    </div>
</div>

{{-- Modal Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-times mr-2"></i>Rechazar Anonimizacion</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formRechazar" action="{{ route('procesamientos.rechazar-anonimizacion', $asignacion->id_asignacion) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                    @endif
                    <p class="text-muted">
                        Indique el motivo del rechazo. El anonimizador recibira este comentario
                        y podra corregir la anonimizacion.
                    </p>
                    <div class="form-group">
                        <label>Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="comentario" id="comentarioRechazo" class="form-control" rows="4" required
                                  minlength="10"
                                  placeholder="Ej: Algunas entidades no fueron anonimizadas correctamente...">{{ old('comentario') }}</textarea>
                        <small class="text-muted">Minimo 10 caracteres</small>
                    </div>
                    <div id="errorRechazo" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnRechazar">
                        <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
@php
    $textoParaProcesar = $entrevista->getTextoParaProcesamiento() ?? '';
    $tiposAnonimizar = $asignacion->tipos_anonimizar ?? 'PER,LOC';
    $formatoReemplazo = $asignacion->formato_reemplazo ?? 'numbered';
@endphp
<script>
var entidades = @json($entidades);
var textoOriginal = @json($textoParaProcesar);
var textoAnonimizadoGuardado = @json($asignacion->texto_anonimizado ?? '');
var tiposActivos = @json(explode(',', $tiposAnonimizar));
var formatoActivo = @json($formatoReemplazo);

// Estado de las entidades en el editor visual
var estadoEntidades = [];

// Variables para seleccion de texto
var seleccionActual = null;

$(document).ready(function() {
    // Inicializar editor visual
    inicializarEditorVisual();

    // Actualizar contador de caracteres
    $('#texto_anonimizado').on('input', function() {
        $('#charCount').text($(this).val().length);
    });

    // Confirmacion antes de salir si hay cambios sin guardar
    var originalContent = $('#texto_anonimizado').val();
    var hasChanges = false;

    $('#texto_anonimizado').on('input', function() {
        hasChanges = ($(this).val() !== originalContent);
    });

    $('#formAnonimizacion').on('submit', function() {
        hasChanges = false;
        // Guardar entidades manuales
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

    $(window).on('beforeunload', function() {
        if (hasChanges) {
            return 'Tiene cambios sin guardar. ¿Desea salir de la pagina?';
        }
    });

    // Detectar seleccion de texto en el panel original
    $('#texto-original-marcado').on('mouseup', function(e) {
        var selection = window.getSelection();
        var selectedText = selection.toString().trim();

        if (selectedText.length > 0) {
            var startPos = textoOriginal.indexOf(selectedText);
            if (startPos !== -1) {
                seleccionActual = {
                    text: selectedText,
                    start: startPos,
                    end: startPos + selectedText.length
                };

                var menu = $('#entity-menu');
                menu.css({
                    top: e.pageY + 5,
                    left: e.pageX + 5
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
});

// =====================================================
// EDITOR VISUAL - Entidades clicables
// =====================================================

function inicializarEditorVisual() {
    var formato = formatoActivo;

    // Filtrar y preparar entidades
    estadoEntidades = [];
    var contadores = {};
    var idCounter = 0;

    // Ordenar por posicion ascendente para procesar en orden
    // En revision, mostrar TODAS las entidades (no filtrar por tipo)
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
        // excluir = true significa que NO se cubre (descubierta)
        var estaCubierta = !ent.excluir;

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

    // === COLUMNA IZQUIERDA: Original con entidades resaltadas ===
    var htmlOriginal = textoOriginal;

    entidadesUnicas.forEach(function(ent) {
        var antes = htmlOriginal.substring(0, ent.start);
        var despues = htmlOriginal.substring(ent.end);

        var span = '<span class="entity-original entity-' + ent.type + '">' +
                   escapeHtml(ent.text) +
                   '</span>';

        htmlOriginal = antes + span + despues;
    });

    // Escapar el texto restante (no entidades) y convertir saltos de linea
    htmlEditor = htmlEditor.replace(/\n/g, '<br>');
    htmlOriginal = htmlOriginal.replace(/\n/g, '<br>');

    $('#editor-visual').html(htmlEditor);
    $('#texto-original-marcado').html(htmlOriginal);

    // Agregar event listeners al editor
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

    var formato = formatoActivo;
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

    estadoEntidades.sort((a, b) => a.start - b.start);

    $('#entity-menu').removeClass('show');
    seleccionActual = null;
    window.getSelection().removeAllRanges();

    renderizarEditorVisual();
    sincronizarConTextarea();

    if (typeof toastr !== 'undefined') {
        if (entidadesAgregadas === 1) {
            toastr.success('Entidad "' + textoABuscar + '" agregada como ' + tipo);
        } else {
            toastr.success(entidadesAgregadas + ' instancias de "' + textoABuscar + '" agregadas como ' + tipo);
        }
    }
}

// Obtener el numero para una palabra de un tipo dado
function obtenerNumeroParaPalabra(tipo, texto) {
    var existente = estadoEntidades.find(function(ent) {
        return ent.type === tipo && ent.text === texto;
    });

    if (existente && existente.reemplazo) {
        var match = existente.reemplazo.match(/\[.*?_(\d+)\]/);
        if (match) {
            return parseInt(match[1]);
        }
    }

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
    var texto = textoOriginal;

    var posicionesUsadas = new Set();
    var entidadesUnicas = [];

    estadoEntidades.forEach(function(ent) {
        var key = ent.start + '-' + ent.end;
        if (!posicionesUsadas.has(key)) {
            posicionesUsadas.add(key);
            entidadesUnicas.push(ent);
        }
    });

    entidadesUnicas.sort((a, b) => b.start - a.start);

    entidadesUnicas.forEach(function(ent) {
        if (ent.cubierta) {
            var antes = texto.substring(0, ent.start);
            var despues = texto.substring(ent.end);
            texto = antes + ent.reemplazo + despues;
        }
    });

    $('#texto_anonimizado').val(texto);
    $('#charCount').text(texto.length);
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

// Manejar envio del formulario de rechazo
$('#formRechazar').on('submit', function(e) {
    var comentario = $('#comentarioRechazo').val().trim();

    if (comentario.length < 10) {
        e.preventDefault();
        $('#errorRechazo').removeClass('d-none').text('El comentario debe tener al menos 10 caracteres');
        return false;
    }

    $('#btnRechazar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...');
    return true;
});

// Abrir modal si hay errores de validacion
@if($errors->any())
$('#modalRechazar').modal('show');
@endif
</script>
@endsection
