@extends('layouts.app')

@section('title', 'Previsualizar Anonimizacion')
@section('content_header')
Previsualizar: {{ $entrevista->entrevista_codigo }}
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
    .comparacion-panel {
        border: 1px solid #dee2e6;
        border-radius: 4px;
        padding: 15px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Configuración -->
    <div class="col-md-3">
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Configuracion</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">Tipos a anonimizar:</p>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-PER" value="PER" checked>
                        <label class="custom-control-label" for="check-PER">
                            <span class="badge badge-primary">PER</span> Personas
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-LOC" value="LOC" checked>
                        <label class="custom-control-label" for="check-LOC">
                            <span class="badge badge-success">LOC</span> Lugares
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-ORG" value="ORG">
                        <label class="custom-control-label" for="check-ORG">
                            <span class="badge badge-info">ORG</span> Organizaciones
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-DATE" value="DATE">
                        <label class="custom-control-label" for="check-DATE">
                            <span class="badge badge-secondary">DATE</span> Fechas
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-EVENT" value="EVENT">
                        <label class="custom-control-label" for="check-EVENT">
                            <span class="badge badge-warning">EVENT</span> Eventos
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input tipo-check" id="check-GUN" value="GUN">
                        <label class="custom-control-label" for="check-GUN">
                            <span class="badge badge-danger">GUN</span> Armas
                        </label>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <label class="small">Formato:</label>
                    <select class="form-control form-control-sm" id="formato">
                        <option value="brackets">[TIPO]</option>
                        <option value="numbered">[TIPO_1]</option>
                        <option value="redacted">[REDACTADO]</option>
                        <option value="asterisks">***</option>
                    </select>
                </div>

                <button class="btn btn-outline-secondary btn-sm btn-block mb-2" onclick="actualizarPreview()">
                    <i class="fas fa-sync mr-1"></i>Actualizar
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>Resumen</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody id="resumen-entidades">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Comparación -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-columns mr-2"></i>Comparacion</h3>
                <div class="card-tools">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-default active" onclick="mostrarVista('comparar')">
                            Comparar
                        </button>
                        <button type="button" class="btn btn-default" onclick="mostrarVista('original')">
                            Original
                        </button>
                        <button type="button" class="btn btn-default" onclick="mostrarVista('anonimizado')">
                            Anonimizado
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @php $transcripcionTexto = $entrevista->getTextoParaProcesamiento(); @endphp
                <!-- Vista comparación -->
                <div id="vista-comparar" class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2"><i class="fas fa-file-alt mr-1"></i>Original</h6>
                        <div class="comparacion-panel transcripcion-container" id="texto-original">
                            @if($transcripcionTexto)
                                {{ $transcripcionTexto }}
                            @else
                                <p class="text-muted text-center py-5">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                    No hay transcripcion disponible.<br>
                                    <small>La funcionalidad de anonimizacion requiere primero transcribir la entrevista.</small>
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2"><i class="fas fa-user-secret mr-1"></i>Anonimizado</h6>
                        <div class="comparacion-panel transcripcion-container bg-light" id="texto-anonimizado">
                            <!-- Se llena dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Vista solo original -->
                <div id="vista-original" style="display: none;">
                    <div class="transcripcion-container" id="texto-original-full">
                        {{ $transcripcionTexto ?? '' }}
                    </div>
                </div>

                <!-- Vista solo anonimizado -->
                <div id="vista-anonimizado" style="display: none;">
                    <div class="transcripcion-container" id="texto-anonimizado-full">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('procesamientos.generar-anonimizacion', $entrevista->id_e_ind_fvt) }}"
                      method="POST" id="form-anonimizar" class="d-inline">
                    @csrf
                    <input type="hidden" name="tipos" id="input-tipos" value="">
                    <input type="hidden" name="formato" id="input-formato" value="">
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('¿Generar y guardar la version anonimizada?')">
                        <i class="fas fa-save mr-2"></i>Guardar Version Anonimizada
                    </button>
                </form>
                <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
                <button class="btn btn-outline-primary float-right" onclick="copiarAnonimizado()">
                    <i class="fas fa-copy mr-2"></i>Copiar Texto
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
var entidades = @json($entidades);
var textoOriginal = @json($entrevista->getTextoParaProcesamiento() ?? '');

$(document).ready(function() {
    actualizarPreview();

    // Actualizar al cambiar opciones
    $('.tipo-check, #formato').on('change', actualizarPreview);
});

function actualizarPreview() {
    var tiposSeleccionados = [];
    $('.tipo-check:checked').each(function() {
        tiposSeleccionados.push($(this).val());
    });

    var formato = $('#formato').val();
    var texto = textoOriginal;
    var contadores = {};
    var resumen = {};

    // Ordenar entidades por posición descendente
    var entidadesOrdenadas = [...entidades].sort((a, b) => (b.start || 0) - (a.start || 0));

    // Reemplazar entidades
    entidadesOrdenadas.forEach(function(ent) {
        if (!tiposSeleccionados.includes(ent.type)) return;

        // Contador por tipo
        if (!contadores[ent.type]) contadores[ent.type] = 0;
        contadores[ent.type]++;

        // Resumen
        if (!resumen[ent.type]) resumen[ent.type] = 0;
        resumen[ent.type]++;

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
            texto = texto.replace(regex, '<span class="entity-anonimizada">' + reemplazo + '</span>');
        }
    });

    // Marcar entidades en original
    var textoOriginalMarcado = textoOriginal;
    entidades.forEach(function(ent) {
        if (ent.text && tiposSeleccionados.includes(ent.type)) {
            var regex = new RegExp('(' + escapeRegex(ent.text) + ')', 'gi');
            textoOriginalMarcado = textoOriginalMarcado.replace(regex,
                '<span class="entity-original entity-' + ent.type + '">$1</span>');
        }
    });

    // Actualizar vistas
    $('#texto-original').html(textoOriginalMarcado);
    $('#texto-anonimizado').html(texto);
    $('#texto-original-full').html(textoOriginalMarcado);
    $('#texto-anonimizado-full').html(texto);

    // Actualizar resumen
    var htmlResumen = '';
    var totalAnonimizadas = 0;
    for (var tipo in resumen) {
        totalAnonimizadas += resumen[tipo];
        htmlResumen += '<tr><td><span class="badge badge-dark">' + tipo + '</span></td>' +
                       '<td class="text-right">' + resumen[tipo] + '</td></tr>';
    }
    htmlResumen += '<tr class="table-active"><td><strong>Total</strong></td>' +
                   '<td class="text-right"><strong>' + totalAnonimizadas + '</strong></td></tr>';
    $('#resumen-entidades').html(htmlResumen);

    // Actualizar inputs del form
    $('#input-tipos').val(tiposSeleccionados.join(','));
    $('#input-formato').val(formato);
}

function mostrarVista(vista) {
    $('#vista-comparar, #vista-original, #vista-anonimizado').hide();
    $('#vista-' + vista).show();
    $('.card-tools .btn').removeClass('active');
    $('.card-tools .btn[onclick*="' + vista + '"]').addClass('active');
}

function copiarAnonimizado() {
    var texto = $('#texto-anonimizado').text();
    navigator.clipboard.writeText(texto).then(function() {
        alert('Texto copiado al portapapeles');
    });
}

function escapeRegex(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}
</script>
@endsection
