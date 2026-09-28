{{-- Tarjeta "Etiquetas asignadas" + modal para unir etiquetas (compartido con el editor de anonimizacion) --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Etiquetas asignadas</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" id="btn-expandir-etiquetas" title="Expandir/contraer">
                <i class="fas fa-expand-alt"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0" id="card-body-etiquetas" style="max-height: 300px; overflow-y: auto;">
        <div id="resumen-entidades">
            <!-- Se llena dinamicamente -->
        </div>
    </div>
</div>

<div class="modal fade" id="modalUnirEtiqueta" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-link mr-2"></i>Unir etiqueta</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Unir <span id="unir-origen"></span></p>
                <div class="form-group mb-1">
                    <label for="unir-destino">con la etiqueta:</label>
                    <select class="form-control" id="unir-destino"></select>
                </div>
                <small class="text-muted">
                    Todas sus instancias pasan a tener el mismo reemplazo que la etiqueta elegida
                    (ej. "Aleja" y "Alejandra Hernández" &rarr; la misma PERSONA). Se puede deshacer.
                </small>
                <p class="text-muted mb-0 mt-2 d-none" id="unir-sin-opciones">
                    No hay otras etiquetas de este tipo con las cuales unir.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btn-confirmar-unir">
                    <i class="fas fa-link mr-1"></i> Unir
                </button>
            </div>
        </div>
    </div>
</div>
