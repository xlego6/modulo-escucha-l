{{--
    Editor visual de anonimizacion (compartido: editar-anonimizacion-asignada / revisar-anonimizacion).
    Parametros: $asignacion, $tituloEditor, $iconoEditor
    El texto anonimizado se reconstruye siempre desde las etiquetas (no hay
    edicion de texto libre: cambiar la longitud del texto invalidaria las
    posiciones de las etiquetas).
--}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas {{ $iconoEditor }} mr-2"></i>{{ $tituloEditor }}</h3>
        <div class="card-tools">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-default" id="btn-deshacer" onclick="deshacer()" title="Deshacer (Ctrl+Z)" disabled>
                    <i class="fas fa-undo"></i> Deshacer
                </button>
                <button type="button" class="btn btn-default" id="btn-rehacer" onclick="rehacer()" title="Rehacer (Ctrl+Y / Ctrl+Shift+Z)" disabled>
                    <i class="fas fa-redo"></i> Rehacer
                </button>
            </div>
        </div>
    </div>
    <form action="{{ route('procesamientos.guardar-anonimizacion-asignada', $asignacion->id_asignacion) }}"
          method="POST" id="formAnonimizacion">
        @csrf
        <input type="hidden" name="texto_anonimizado" id="texto_anonimizado" value="{{ $asignacion->texto_anonimizado ?? '' }}">
        <input type="hidden" name="entidades_manuales" id="input_entidades_manuales">
        <input type="hidden" name="estado_entidades" id="input_estado_entidades">
        <input type="hidden" name="entidades_eliminadas" id="input_entidades_eliminadas">

        <div class="card-body p-2">
            <div id="vista-visual" class="p-2">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">
                            <i class="fas fa-file-alt mr-1"></i>Texto Etiquetado
                            <small class="text-secondary">(seleccione texto para etiquetar)</small>
                        </h6>
                        <div class="editor-visual-container texto-seleccionable" id="texto-original-marcado" style="background: #f0f6f7;">
                            {{-- Texto original con entidades resaltadas --}}
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-muted mb-0">
                                <i class="fas fa-user-secret mr-1"></i>Texto Anonimizado
                                <small class="text-secondary">(clic para anonimizar)</small>
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
                                <span class="leyenda-item"><span class="entity-cubierta" style="font-size:11px">[PERSONA_1]</span> Cubierta</span>
                                <span class="leyenda-item"><span class="entity-descubierta entity-PERSONA" style="font-size:11px">Juan</span> Visible</span>
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
                <span id="charCount">{{ mb_strlen($asignacion->texto_anonimizado ?? '') }}</span> caracteres
            </span>
        </div>
    </form>
</div>

{{-- Menu para etiquetar el texto seleccionado --}}
<div class="entity-menu" id="entity-menu">
    <div class="entity-menu-header">Etiquetar como:</div>
    @foreach(\App\Models\EntidadDetectada::tipos() as $tipo => $descripcion)
        <div class="entity-menu-item" onclick="agregarEntidad('{{ $tipo }}')">
            <span class="badge entity-{{ $tipo }}">{{ $tipo }}</span> {{ $descripcion }}
        </div>
    @endforeach
</div>

{{-- Menu contextual (clic derecho) sobre entidades del Texto Anonimizado --}}
<div class="entity-menu" id="entity-context-menu">
    <div class="entity-menu-header" id="ctx-header">Entidad</div>
    <div class="entity-menu-item" id="ctx-cubrir-todas">
        <i class="fas fa-eye-slash mr-1 text-dark"></i> Cubrir todas las instancias
    </div>
    <div class="entity-menu-item" id="ctx-descubrir-todas">
        <i class="fas fa-eye mr-1 text-secondary"></i> Descubrir todas las instancias
    </div>
    <div class="entity-menu-item ctx-unir">
        <i class="fas fa-link mr-1 text-primary"></i> Unir con otra etiqueta...
    </div>
</div>

{{-- Menu contextual (clic derecho) sobre entidades del Texto Etiquetado --}}
<div class="entity-menu" id="entity-original-context-menu">
    <div class="entity-menu-header" id="ctx-original-header">Entidad</div>
    <div class="entity-menu-item ctx-unir">
        <i class="fas fa-link mr-1 text-primary"></i> Unir con otra etiqueta...
    </div>
    <div class="entity-menu-item text-danger" id="ctx-quitar-etiqueta">
        <i class="fas fa-trash-alt mr-1"></i> Quitar etiqueta
    </div>
</div>
