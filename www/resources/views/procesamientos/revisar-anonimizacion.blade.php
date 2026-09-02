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
    .entity-NUMERO { background-color: #f8d7da; }
    .entity-PERSONA { background-color: #cce5ff; }
    .entity-ORGANIZACION { background-color: #e2d9f3; }
    .entity-LUGAR { background-color: #d4edda; }
    .entity-GENTILICIO { background-color: #dcdcf7; }
    .entity-FECHA { background-color: #e2e3e5; }
    .entity-EDAD { background-color: #ffe5d0; }
    .entity-OCUPACION { background-color: #d1ecf1; }
    .entity-GRUPO_ARMADO { background-color: #e8c4c4; }
    .entity-ROL_ARMADO { background-color: #fff3cd; }
    .entity-ETNICO { background-color: #c8f0d4; }

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
    }
    .entity-descubierta.entity-NUMERO { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    .entity-descubierta.entity-PERSONA { background-color: #cce5ff; border: 1px solid #b8daff; color: #004085; }
    .entity-descubierta.entity-ORGANIZACION { background-color: #e2d9f3; border: 1px solid #d4c5ec; color: #4a2a7a; }
    .entity-descubierta.entity-LUGAR { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .entity-descubierta.entity-GENTILICIO { background-color: #dcdcf7; border: 1px solid #c7c7f0; color: #34348a; }
    .entity-descubierta.entity-FECHA { background-color: #e2e3e5; border: 1px solid #d6d8db; color: #383d41; }
    .entity-descubierta.entity-EDAD { background-color: #ffe5d0; border: 1px solid #ffd8b8; color: #7a4a12; }
    .entity-descubierta.entity-OCUPACION { background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    .entity-descubierta.entity-GRUPO_ARMADO { background-color: #e8c4c4; border: 1px solid #dba8a8; color: #5c1f1f; }
    .entity-descubierta.entity-ROL_ARMADO { background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; }
    .entity-descubierta.entity-ETNICO { background-color: #c8f0d4; border: 1px solid #a8e6bc; color: #1e5c34; }
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
        cursor: context-menu;
    }
    .entity-original-label {
        font-size: 9px;
        font-weight: bold;
        vertical-align: super;
        margin-left: 2px;
        opacity: 0.8;
    }
    .entity-original.entity-NUMERO { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .entity-original.entity-PERSONA { background-color: #cce5ff; border: 1px solid #b8daff; }
    .entity-original.entity-ORGANIZACION { background-color: #e2d9f3; border: 1px solid #d4c5ec; }
    .entity-original.entity-LUGAR { background-color: #d4edda; border: 1px solid #c3e6cb; }
    .entity-original.entity-GENTILICIO { background-color: #dcdcf7; border: 1px solid #c7c7f0; }
    .entity-original.entity-FECHA { background-color: #e2e3e5; border: 1px solid #d6d8db; }
    .entity-original.entity-EDAD { background-color: #ffe5d0; border: 1px solid #ffd8b8; }
    .entity-original.entity-OCUPACION { background-color: #d1ecf1; border: 1px solid #bee5eb; }
    .entity-original.entity-GRUPO_ARMADO { background-color: #e8c4c4; border: 1px solid #dba8a8; }
    .entity-original.entity-ROL_ARMADO { background-color: #fff3cd; border: 1px solid #ffeeba; }
    .entity-original.entity-ETNICO { background-color: #c8f0d4; border: 1px solid #a8e6bc; }
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
    /* Estado activo de los botones de modo (Editor visual/Editar texto) y de cobertura (Todas/Ninguna) */
    .card-tools .btn.active,
    .btn-cobertura.active {
        background-color: #343a40;
        border-color: #343a40;
        color: #fff;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.25);
    }
    .etiqueta-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 4px 8px;
        border-bottom: 1px solid #f1f1f1;
        font-size: 12px;
    }
    .etiqueta-item:last-child { border-bottom: none; }
    .etiqueta-item .btn-eliminar-etiqueta {
        padding: 0 4px;
        line-height: 1;
    }
</style>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        @php $transcripcionOriginal = $entrevista->getTextoParaProcesamiento(); @endphp

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Anonimizacion (Editable)</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-default active" onclick="mostrarVista('visual')">
                            <i class="fas fa-mouse-pointer"></i> Editor visual
                        </button>
                        <button type="button" class="btn btn-default" onclick="mostrarVista('editar')">
                            <i class="fas fa-edit"></i> Editar texto
                        </button>
                    </div>
                </div>
            </div>
            <form action="{{ route('procesamientos.guardar-anonimizacion-asignada', $asignacion->id_asignacion) }}" method="POST" id="formAnonimizacion">
                @csrf
                <input type="hidden" name="entidades_manuales" id="input_entidades_manuales">
                <input type="hidden" name="estado_entidades" id="input_estado_entidades">
                <input type="hidden" name="entidades_eliminadas" id="input_entidades_eliminadas">
                <div class="card-body p-2">
                    {{-- Vista Edicion (texto liquido) --}}
                    <div id="vista-editar" style="display: none;">
                        <textarea name="texto_anonimizado" id="texto_anonimizado" class="form-control"
                                  style="min-height: 500px; resize: vertical; font-family: monospace;">{{ $asignacion->texto_anonimizado }}</textarea>
                    </div>

                    {{-- Vista Visual con comparacion (entidades clicables) --}}
                    <div id="vista-visual" class="p-2">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">
                                    <i class="fas fa-file-alt mr-1"></i>Texto Etiquetado
                                    <small class="text-secondary">(seleccione texto para etiquetar)</small>
                                </h6>
                                <div class="editor-visual-container texto-seleccionable" id="texto-original-marcado" style="background: #fffbea;">
                                    {{-- Texto original con entidades resaltadas --}}
                                </div>
                            </div>

                            {{-- Menu contextual para agregar entidades --}}
                            <div class="entity-menu" id="entity-menu">
                                <div class="entity-menu-header">Etiquetar como:</div>
                                <div class="entity-menu-item" onclick="agregarEntidad('NUMERO')">
                                    <span class="badge entity-NUMERO">NUMERO</span> Número/identificador
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('PERSONA')">
                                    <span class="badge entity-PERSONA">PERSONA</span> Persona
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('ORGANIZACION')">
                                    <span class="badge entity-ORGANIZACION">ORGANIZACION</span> Organización
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('LUGAR')">
                                    <span class="badge entity-LUGAR">LUGAR</span> Lugar
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('GENTILICIO')">
                                    <span class="badge entity-GENTILICIO">GENTILICIO</span> Gentilicio
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('FECHA')">
                                    <span class="badge entity-FECHA">FECHA</span> Fecha
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('EDAD')">
                                    <span class="badge entity-EDAD">EDAD</span> Edad
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('OCUPACION')">
                                    <span class="badge entity-OCUPACION">OCUPACION</span> Ocupación
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('GRUPO_ARMADO')">
                                    <span class="badge entity-GRUPO_ARMADO">GRUPO_ARMADO</span> Grupo armado
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('ROL_ARMADO')">
                                    <span class="badge entity-ROL_ARMADO">ROL_ARMADO</span> Rol en grupo armado
                                </div>
                                <div class="entity-menu-item" onclick="agregarEntidad('ETNICO')">
                                    <span class="badge entity-ETNICO">ETNICO</span> Étnico
                                </div>
                            </div>

                            {{-- Menu contextual (clic derecho) sobre entidades --}}
                            <div class="entity-menu" id="entity-context-menu">
                                <div class="entity-menu-header" id="ctx-header">Entidad</div>
                                <div class="entity-menu-item" id="ctx-cubrir-todas">
                                    <i class="fas fa-eye-slash mr-1 text-dark"></i> Cubrir todas las instancias
                                </div>
                                <div class="entity-menu-item" id="ctx-descubrir-todas">
                                    <i class="fas fa-eye mr-1 text-secondary"></i> Descubrir todas las instancias
                                </div>
                            </div>

                            {{-- Menu contextual (clic derecho) sobre entidades ya etiquetadas del texto original --}}
                            <div class="entity-menu" id="entity-original-context-menu">
                                <div class="entity-menu-header" id="ctx-original-header">Entidad</div>
                                <div class="entity-menu-item text-danger" id="ctx-quitar-etiqueta">
                                    <i class="fas fa-trash-alt mr-1"></i> Quitar etiqueta
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0">
                                        <i class="fas fa-user-secret mr-1"></i>Texto Anonimizado
                                        <small class="text-secondary">(clic para editar)</small>
                                    </h6>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-dark mr-1 btn-cobertura" id="btn-cubrir-todas" onclick="cubrirTodas()" title="Cubrir todas las entidades">
                                            <i class="fas fa-eye-slash"></i> Todas
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-cobertura" id="btn-descubrir-todas" onclick="descubrirTodas()" title="Descubrir todas las entidades">
                                            <i class="fas fa-eye"></i> Ninguna
                                        </button>
                                    </div>
                                </div>
                                <div class="editor-visual-container" id="editor-visual">
                                    {{-- Se llena dinamicamente con entidades clicables --}}
                                </div>
                                <div class="mt-2">
                                    <div class="leyenda-entidades mb-1">
                                        <span class="leyenda-item"><span class="entity-cubierta" style="font-size:11px">[PERSONA]</span> Cubierta</span>
                                        <span class="leyenda-item"><span class="entity-descubierta entity-PERSONA" style="font-size:11px">Juan</span> Visible</span>
                                        <span class="text-muted small ml-2">| Clic en entidad para cubrir/descubrir</span>
                                    </div>
                                    <span class="badge badge-dark" id="contador-cubiertas">0</span> cubiertas
                                    <span class="badge badge-secondary ml-2" id="contador-descubiertas">0</span> visibles
                                </div>
                            </div>
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

{{-- Tarjetas de información y decisión (parte inferior) --}}
<div class="row">
    <div class="col-md-3">
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
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-3">
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
    </div>

    <div class="col-md-3">
        {{-- Etiquetas asignadas (lista completa, con opcion de eliminar) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Etiquetas asignadas</h3>
            </div>
            <div class="card-body p-0" style="max-height: 260px; overflow-y: auto;">
                <div id="resumen-entidades">
                    <!-- Se llena dinamicamente -->
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
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
    $tiposAnonimizar = $asignacion->tipos_anonimizar ?? implode(',', \App\Models\EntidadDetectada::tiposPorDefecto());
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

// Para menu contextual y scroll sincronizado
var entidadContextual = null;
var entidadOriginalContextual = null;
var menuEtiquetarRecienAbierto = false;
var syncingScroll = false;

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

        // Asegurar que el textarea refleja el estado visual actual
        if ($('#vista-visual').is(':visible')) {
            sincronizarConTextarea();
        }

        // Guardar entidades manuales
        var entidadesManuales = estadoEntidades.filter(function(ent) {
            return ent.manual === true;
        });
        $('#input_entidades_manuales').val(JSON.stringify(entidadesManuales));

        // Estado de todas las entidades (para saber cuales estan descubiertas)
        var estadoParaGuardar = estadoEntidades.map(function(ent) {
            return {
                id: ent.id,
                db_id: ent.db_id || null,
                text: ent.text,
                start: ent.start,
                end: ent.end,
                cubierta: ent.cubierta
            };
        });
        $('#input_estado_entidades').val(JSON.stringify(estadoParaGuardar));

        // Entidades eliminadas: las que estaban en entidades originales pero ya no en estadoEntidades
        var activosKey = new Set(estadoEntidades.map(function(e) {
            return (e.start || 0) + '-' + e.text + '-' + e.type;
        }));
        var eliminadas = entidades.filter(function(ent) {
            return !activosKey.has((ent.start || 0) + '-' + ent.text + '-' + ent.type);
        });
        $('#input_entidades_eliminadas').val(JSON.stringify(eliminadas));
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
                var menuWidth = 160;
                var menuHeight = 280;
                var posX = e.clientX + 5;
                var posY = e.clientY + 5;

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
                // El mismo gesto de seleccion dispara un "click" justo despues del
                // mouseup: se absorbe una sola vez para que no cierre el menu que
                // acaba de abrirse, sin bloquear clics posteriores para cerrarlo.
                menuEtiquetarRecienAbierto = true;
            }
        }
    });

    // Ocultar menus al hacer clic fuera
    $(document).on('click', function(e) {
        if (menuEtiquetarRecienAbierto) {
            menuEtiquetarRecienAbierto = false;
        } else if (!$(e.target).closest('#entity-menu').length) {
            $('#entity-menu').removeClass('show');
            seleccionActual = null;
        }
        if (!$(e.target).closest('#entity-context-menu').length) {
            $('#entity-context-menu').removeClass('show');
        }
        if (!$(e.target).closest('#entity-original-context-menu').length) {
            $('#entity-original-context-menu').removeClass('show');
        }
    });

    // Scroll sincronizado entre columnas del editor visual
    $('#texto-original-marcado').on('scroll', function() {
        if (syncingScroll) return;
        syncingScroll = true;
        var pct = this.scrollTop / (this.scrollHeight - this.clientHeight) || 0;
        var ot = $('#editor-visual')[0];
        ot.scrollTop = pct * (ot.scrollHeight - ot.clientHeight);
        syncingScroll = false;
    });
    $('#editor-visual').on('scroll', function() {
        if (syncingScroll) return;
        syncingScroll = true;
        var pct = this.scrollTop / (this.scrollHeight - this.clientHeight) || 0;
        var ot = $('#texto-original-marcado')[0];
        ot.scrollTop = pct * (ot.scrollHeight - ot.clientHeight);
        syncingScroll = false;
    });

    // Menu contextual (clic derecho) sobre entidades
    $(document).on('contextmenu', '#editor-visual .entity-clickable', function(e) {
        e.preventDefault();
        var id = parseInt($(this).data('id'));
        var ent = estadoEntidades.find(function(en) { return en.id === id; });
        if (!ent) return;
        entidadContextual = { text: ent.text, type: ent.type };
        $('#ctx-header').html('<span class="badge entity-' + ent.type + '">' + ent.type + '</span> &ldquo;' + escapeHtml(ent.text) + '&rdquo;');
        var posX = e.clientX + 5, posY = e.clientY + 5;
        if (posX + 230 > window.innerWidth) posX = e.clientX - 235;
        if (posY + 130 > window.innerHeight) posY = e.clientY - 135;
        $('#entity-context-menu').css({ top: posY, left: posX }).addClass('show');
    });

    $('#ctx-cubrir-todas').on('click', function() {
        if (!entidadContextual) return;
        estadoEntidades.forEach(function(ent) {
            if (ent.text === entidadContextual.text && ent.type === entidadContextual.type) ent.cubierta = true;
        });
        $('#entity-context-menu').removeClass('show');
        renderizarEditorVisual();
        sincronizarConTextarea();
    });

    $('#ctx-descubrir-todas').on('click', function() {
        if (!entidadContextual) return;
        estadoEntidades.forEach(function(ent) {
            if (ent.text === entidadContextual.text && ent.type === entidadContextual.type) ent.cubierta = false;
        });
        $('#entity-context-menu').removeClass('show');
        renderizarEditorVisual();
        sincronizarConTextarea();
    });

    // Menu contextual (clic derecho) sobre entidades ya etiquetadas en "Texto Original"
    $(document).on('contextmenu', '#texto-original-marcado .entity-original', function(e) {
        e.preventDefault();
        var texto = $(this).data('text');
        var tipo = $(this).data('type');
        entidadOriginalContextual = { text: String(texto), type: tipo };
        $('#ctx-original-header').html('<span class="badge entity-' + tipo + '">' + tipo + '</span> &ldquo;' + escapeHtml(String(texto)) + '&rdquo;');
        var posX = e.clientX + 5, posY = e.clientY + 5;
        if (posX + 200 > window.innerWidth) posX = e.clientX - 205;
        if (posY + 80 > window.innerHeight) posY = e.clientY - 85;
        $('#entity-original-context-menu').css({ top: posY, left: posX }).addClass('show');
    });

    $('#ctx-quitar-etiqueta').on('click', function() {
        if (!entidadOriginalContextual) return;
        $('#entity-original-context-menu').removeClass('show');
        eliminarEtiqueta(entidadOriginalContextual.text, entidadOriginalContextual.type);
    });

    // "Eliminar etiqueta" tambien esta disponible en la tarjeta "Etiquetas asignadas"
    // (delegado porque la lista se re-renderiza dinamicamente, ver eliminarEtiqueta()).
    $('#resumen-entidades').on('click', '.btn-eliminar-etiqueta', function() {
        eliminarEtiqueta($(this).data('text'), $(this).data('type'));
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
            db_id: ent.id || null,
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

        var textoOriginalEscapadoIzq = escapeHtml(ent.text).replace(/"/g, '&quot;');
        var span = '<span class="entity-original entity-' + ent.type + '" ' +
                   'data-text="' + textoOriginalEscapadoIzq + '" ' +
                   'data-type="' + ent.type + '" ' +
                   'title="Clic derecho para quitar etiqueta">' +
                   escapeHtml(ent.text) +
                   '<sup class="entity-original-label">' + ent.type + '</sup>' +
                   '</span>';

        htmlOriginal = antes + span + despues;
    });

    // Escapar el texto restante (no entidades) y convertir saltos de linea
    htmlEditor = formatearTextoAnonimizado(htmlEditor);
    htmlOriginal = formatearTextoAnonimizado(htmlOriginal);

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

    $span.removeClass('entity-cubierta entity-descubierta entity-NUMERO entity-PERSONA entity-ORGANIZACION entity-LUGAR entity-GENTILICIO entity-FECHA entity-EDAD entity-OCUPACION entity-GRUPO_ARMADO entity-ROL_ARMADO entity-ETNICO');
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

function actualizarBotonesCobertura() {
    var todasCubiertas = estadoEntidades.length > 0 && estadoEntidades.every(function(e) { return e.cubierta; });
    var ningunaCubierta = estadoEntidades.length > 0 && estadoEntidades.every(function(e) { return !e.cubierta; });
    $('#btn-cubrir-todas').toggleClass('active', todasCubiertas);
    $('#btn-descubrir-todas').toggleClass('active', ningunaCubierta);
}

function eliminarEtiqueta(texto, tipo) {
    if (!confirm('¿Eliminar todas las instancias de "' + texto + '" (' + tipo + ')?\nEsta accion no se puede deshacer.')) return;
    estadoEntidades = estadoEntidades.filter(function(ent) {
        return !(ent.text === texto && ent.type === tipo);
    });
    renderizarEditorVisual();
    sincronizarConTextarea();
    if (typeof toastr !== 'undefined') toastr.info('Etiqueta "' + texto + '" (' + tipo + ') eliminada');
}

function actualizarResumen() {
    // Lista de etiquetas distintas (agrupadas por texto+tipo) con su conteo de
    // ocurrencias y un boton para eliminarlas todas.
    var grupos = {};
    var orden = [];

    estadoEntidades.forEach(function(ent) {
        var key = ent.type + '::' + ent.text;
        if (!grupos[key]) {
            grupos[key] = { text: ent.text, type: ent.type, count: 0 };
            orden.push(key);
        }
        grupos[key].count++;
    });

    if (orden.length === 0) {
        $('#resumen-entidades').html('<p class="text-muted text-center small py-3 mb-0">Sin etiquetas</p>');
        return;
    }

    var html = '';
    orden.forEach(function(key) {
        var g = grupos[key];
        html += '<div class="etiqueta-item">' +
                    '<span><span class="badge entity-' + g.type + '">' + g.type + '</span> ' +
                    escapeHtml(g.text) + ' <span class="text-muted">(' + g.count + ')</span></span>' +
                    '<button type="button" class="btn btn-sm btn-link text-danger btn-eliminar-etiqueta" ' +
                        'data-text="' + escapeHtml(g.text).replace(/"/g, '&quot;') + '" data-type="' + g.type + '" title="Eliminar etiqueta">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>';
    });

    $('#resumen-entidades').html(html);
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
            db_id: null,
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
// Un caracter "de palabra" incluye letras acentuadas/eñes (\p{L} es Unicode,
// a diferencia de \w de JS que solo reconoce ASCII) para que el chequeo de
// limite de palabra funcione bien en español.
function esCaracterDePalabra(c) {
    return c !== undefined && /[\p{L}\p{N}_]/u.test(c);
}

function encontrarTodasInstancias(texto, buscar) {
    var instancias = [];
    var pos = 0;

    while (true) {
        var idx = texto.indexOf(buscar, pos);
        if (idx === -1) break;

        // Solo contar coincidencias de palabra completa: evita que "Flor"
        // marque tambien dentro de "Floreciendo" o "Florero".
        var charAntes = idx > 0 ? texto[idx - 1] : undefined;
        var charDespues = idx + buscar.length < texto.length ? texto[idx + buscar.length] : undefined;
        if (!esCaracterDePalabra(charAntes) && !esCaracterDePalabra(charDespues)) {
            instancias.push({
                start: idx,
                end: idx + buscar.length
            });
        }

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
    actualizarBotonesCobertura();
    actualizarResumen();
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

// Renderiza encabezados/listas/parrafos markdown sobre texto que ya trae
// spans de entidades insertados (igual formato que el editor de transcripcion).
function formatearTextoAnonimizado(html) {
    var lineas = html.split('\n');
    var out = '';
    var enLista = false;

    lineas.forEach(function(linea) {
        var m;
        if ((m = linea.match(/^### (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h4 class="preview-h4">' + formatearInlineAnonimizado(m[1]) + '</h4>';
        } else if ((m = linea.match(/^## (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h3 class="preview-h3">' + formatearInlineAnonimizado(m[1]) + '</h3>';
        } else if ((m = linea.match(/^# (.*)$/))) {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += '<h2 class="preview-h2">' + formatearInlineAnonimizado(m[1]) + '</h2>';
        } else if ((m = linea.match(/^[-*] (.*)$/))) {
            if (!enLista) { out += '<ul class="preview-ul">'; enLista = true; }
            out += '<li>' + formatearInlineAnonimizado(m[1]) + '</li>';
        } else {
            if (enLista) { out += '</ul>'; enLista = false; }
            out += linea.trim() === '' ? '<br>' : '<p>' + formatearInlineAnonimizado(linea) + '</p>';
        }
    });

    if (enLista) out += '</ul>';
    return out;
}

function formatearInlineAnonimizado(linea) {
    // Solo formatea los fragmentos de texto, nunca las etiquetas HTML de los
    // spans de entidades ya insertados (evita, p. ej., que un guion bajo dentro
    // de una clase como "entity-GRUPO_ARMADO" se confunda con subrayado).
    return linea.split(/(<[^>]+>)/g).map(function(parte, i) {
        if (i % 2 === 1) return parte;
        return parte
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/\b_(.+?)_\b/g, '<u>$1</u>');
    }).join('');
}

function mostrarVista(vista) {
    if (vista === 'editar') {
        sincronizarConTextarea();
        $('#vista-visual').hide();
        $('#vista-editar').show();
    } else if (vista === 'visual') {
        renderizarEditorVisual();
        $('#vista-editar').hide();
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
