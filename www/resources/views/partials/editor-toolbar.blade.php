{{--
    Barra de herramientas para editor de texto
    Uso: @include('partials.editor-toolbar', ['targetId' => 'id-del-textarea'])
--}}
@php
    $targetId = $targetId ?? 'editor-transcripcion';
    $showTimestamp = $showTimestamp ?? true;
    $showSpeakers = $showSpeakers ?? true;
@endphp

<div class="editor-toolbar btn-toolbar mb-2" role="toolbar" data-target="#{{ $targetId }}">
    {{-- Grupo: Formato --}}
    <div class="btn-group btn-group-sm mr-2" role="group" title="Formato">
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('bold', '{{ $targetId }}')" title="Negrita (Ctrl+B)">
            <i class="fas fa-bold"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('italic', '{{ $targetId }}')" title="Cursiva (Ctrl+I)">
            <i class="fas fa-italic"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('underline', '{{ $targetId }}')" title="Subrayado (Ctrl+U)">
            <i class="fas fa-underline"></i>
        </button>
    </div>

    {{-- Grupo: Estructura --}}
    <div class="btn-group btn-group-sm mr-2" role="group" title="Estructura">
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('heading', '{{ $targetId }}')" title="Encabezado">
            <i class="fas fa-heading"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('list-ul', '{{ $targetId }}')" title="Lista">
            <i class="fas fa-list-ul"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('quote', '{{ $targetId }}')" title="Cita">
            <i class="fas fa-quote-right"></i>
        </button>
    </div>

    @if($showTimestamp || $showSpeakers)
    {{-- Grupo: Transcripcion --}}
    <div class="btn-group btn-group-sm mr-2" role="group" title="Transcripcion">
        @if($showTimestamp)
        <button type="button" class="btn btn-outline-info" onclick="editorAction('timestamp', '{{ $targetId }}')" title="Insertar marca de tiempo">
            <i class="fas fa-clock"></i>
        </button>
        @endif
        @if($showSpeakers)
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-info dropdown-toggle" data-toggle="dropdown" title="Insertar hablante">
                <i class="fas fa-user"></i>
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#" onclick="editorInsert('[Entrevistador]: ', '{{ $targetId }}'); return false;">
                    <i class="fas fa-microphone mr-2"></i>Entrevistador
                </a>
                <a class="dropdown-item" href="#" onclick="editorInsert('[Entrevistado]: ', '{{ $targetId }}'); return false;">
                    <i class="fas fa-user mr-2"></i>Entrevistado
                </a>
                <a class="dropdown-item" href="#" onclick="editorInsert('[Testigo]: ', '{{ $targetId }}'); return false;">
                    <i class="fas fa-user-friends mr-2"></i>Testigo
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" onclick="editorInsertCustomSpeaker('{{ $targetId }}'); return false;">
                    <i class="fas fa-edit mr-2"></i>Otro...
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- Grupo: Marcas especiales --}}
    <div class="btn-group btn-group-sm mr-2" role="group" title="Marcas">
        <button type="button" class="btn btn-outline-warning" onclick="editorInsert('[inaudible]', '{{ $targetId }}')" title="Marcar inaudible">
            <i class="fas fa-volume-mute"></i>
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="editorInsert('[pausa]', '{{ $targetId }}')" title="Marcar pausa">
            <i class="fas fa-pause"></i>
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="editorInsert('[risas]', '{{ $targetId }}')" title="Marcar risas">
            <i class="fas fa-laugh"></i>
        </button>
        <button type="button" class="btn btn-outline-warning" onclick="editorInsert('[llanto]', '{{ $targetId }}')" title="Marcar llanto">
            <i class="fas fa-sad-tear"></i>
        </button>
    </div>
    @endif

    {{-- Grupo: Herramientas --}}
    <div class="btn-group btn-group-sm mr-2" role="group" title="Herramientas">
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('find', '{{ $targetId }}')" title="Buscar y reemplazar (Ctrl+H)">
            <i class="fas fa-search"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('undo', '{{ $targetId }}')" title="Deshacer (Ctrl+Z)">
            <i class="fas fa-undo"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="editorAction('redo', '{{ $targetId }}')" title="Rehacer (Ctrl+Y)">
            <i class="fas fa-redo"></i>
        </button>
    </div>

    {{-- Estadisticas --}}
    <div class="ml-auto">
        <small class="text-muted">
            <span id="{{ $targetId }}-words">0</span> palabras |
            <span id="{{ $targetId }}-chars">0</span> caracteres
        </small>
    </div>
</div>

{{-- Modal Buscar y Reemplazar --}}
<div class="modal fade" id="modalBuscarReemplazar-{{ $targetId }}" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="fas fa-search mr-2"></i>Buscar y Reemplazar</h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="small">Buscar:</label>
                    <input type="text" class="form-control form-control-sm" id="find-text-{{ $targetId }}" placeholder="Texto a buscar...">
                </div>
                <div class="form-group mb-2">
                    <label class="small">Reemplazar con:</label>
                    <input type="text" class="form-control form-control-sm" id="replace-text-{{ $targetId }}" placeholder="Nuevo texto...">
                </div>
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="case-sensitive-{{ $targetId }}">
                    <label class="custom-control-label small" for="case-sensitive-{{ $targetId }}">Coincidir mayusculas</label>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="editorFindNext('{{ $targetId }}')">
                    <i class="fas fa-search mr-1"></i>Siguiente
                </button>
                <button type="button" class="btn btn-sm btn-warning" onclick="editorReplaceAll('{{ $targetId }}')">
                    <i class="fas fa-exchange-alt mr-1"></i>Reemplazar todo
                </button>
            </div>
        </div>
    </div>
</div>
