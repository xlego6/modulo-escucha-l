@extends('layouts.app')

@section('title', 'Ver Entidades')
@section('content_header')
Entidades: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
<style>
    .entity {
        padding: 2px 6px;
        border-radius: 4px;
        margin: 0 2px;
        cursor: pointer;
    }
    .entity-NUMERO { background-color: #f8d7da; border: 1px solid #f5c6cb; }
    .entity-PERSONA { background-color: #cce5ff; border: 1px solid #b8daff; }
    .entity-ORGANIZACION { background-color: #e2d9f3; border: 1px solid #d4c5ec; }
    .entity-LUGAR { background-color: #d4edda; border: 1px solid #c3e6cb; }
    .entity-GENTILICIO { background-color: #dcdcf7; border: 1px solid #c7c7f0; }
    .entity-FECHA { background-color: #e2e3e5; border: 1px solid #d6d8db; }
    .entity-EDAD { background-color: #ffe5d0; border: 1px solid #ffd8b8; }
    .entity-OCUPACION { background-color: #d1ecf1; border: 1px solid #bee5eb; }
    .entity-GRUPO_ARMADO { background-color: #e8c4c4; border: 1px solid #dba8a8; }
    .entity-ROL_ARMADO { background-color: #fff3cd; border: 1px solid #ffeeba; }
    .entity-ETNICO { background-color: #c8f0d4; border: 1px solid #a8e6bc; }
    .entity-label {
        font-size: 10px;
        font-weight: bold;
        vertical-align: super;
        margin-left: 2px;
    }
    .transcripcion-container {
        line-height: 2;
        font-size: 14px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Transcripcion con Entidades</h3>
            </div>
            <div class="card-body">
                @php $transcripcionTexto = $entrevista->getTextoParaProcesamiento(); @endphp
                <div class="transcripcion-container" id="transcripcion-marcada">
                    @if($transcripcionTexto)
                        {{ $transcripcionTexto }}
                    @else
                        <p class="text-muted text-center py-5">
                            <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                            No hay transcripcion disponible.<br>
                            <small>La deteccion de entidades requiere primero transcribir la entrevista.</small>
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Resumen de entidades -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Resumen de Entidades</h3>
            </div>
            <div class="card-body">
                @if(count($entidades) > 0)
                <canvas id="chartEntidades" height="200"></canvas>
                @else
                <p class="text-muted text-center">No hay entidades detectadas</p>
                @endif
            </div>
        </div>

        <!-- Lista de entidades por tipo -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entidades Detectadas</h3>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                @if(count($entidades) > 0)
                    @php
                        $entidadesPorTipo = collect($entidades)->groupBy('type');
                    @endphp
                    <div class="accordion" id="accordionEntidades">
                        @foreach($entidadesPorTipo as $tipo => $items)
                        <div class="card mb-0">
                            <div class="card-header p-2" id="heading{{ $tipo }}">
                                <button class="btn btn-link btn-block text-left p-0" type="button"
                                        data-toggle="collapse" data-target="#collapse{{ $tipo }}">
                                    <span class="entity entity-{{ $tipo }}">{{ $tipo }}</span>
                                    <span class="badge badge-secondary float-right">{{ count($items) }}</span>
                                </button>
                            </div>
                            <div id="collapse{{ $tipo }}" class="collapse"
                                 data-parent="#accordionEntidades">
                                <div class="card-body p-2">
                                    <ul class="list-unstyled mb-0">
                                        @foreach($items->unique('text')->take(20) as $item)
                                        <li class="mb-1">
                                            <small>{{ $item['text'] ?? 'N/A' }}</small>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center text-muted py-3">
                        <p class="mb-0">No hay entidades</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Acciones -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Acciones</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('procesamientos.anonimizacion') }}"
                   class="btn btn-danger btn-block">
                    <i class="fas fa-user-secret mr-2"></i>Ir a Anonimizacion
                </a>
                <a href="{{ route('procesamientos.transcripcion', ['tipo' => 'anonimizacion']) }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-2"></i>Volver
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
var entidades = @json($entidades);

$(document).ready(function() {
    // Marcar entidades en la transcripción
    if (entidades && entidades.length > 0) {
        var texto = $('#transcripcion-marcada').text();

        // Solo entidades con posicion valida dentro del texto
        var entidadesValidas = entidades.filter(function(ent) {
            return ent.text && typeof ent.start === 'number' && typeof ent.end === 'number'
                && ent.start >= 0 && ent.end > ent.start && ent.end <= texto.length;
        });

        // Quitar solapamientos (misma logica que el editor de anonimizacion):
        // ascendente primero para detectarlos, luego descendente para reemplazar
        // por indice sin invalidar las posiciones ya calculadas.
        entidadesValidas.sort(function(a, b) { return a.start - b.start; });
        var entidadesUnicas = [];
        var finAnterior = -1;
        entidadesValidas.forEach(function(ent) {
            if (ent.start >= finAnterior) {
                entidadesUnicas.push(ent);
                finAnterior = ent.end;
            }
        });
        entidadesUnicas.sort(function(a, b) { return b.start - a.start; });

        entidadesUnicas.forEach(function(ent) {
            var antes = texto.substring(0, ent.start);
            var textoEntidad = texto.substring(ent.start, ent.end);
            var despues = texto.substring(ent.end);
            var span = '<span class="entity entity-' + ent.type + '">' +
                       escapeHtmlVE(textoEntidad) +
                       '<span class="entity-label">' + ent.type + '</span></span>';
            texto = antes + span + despues;
        });

        $('#transcripcion-marcada').html(texto);
    }

    // Gráfico de entidades
    @if(count($entidades) > 0)
    var tipos = {};
    entidades.forEach(function(ent) {
        tipos[ent.type] = (tipos[ent.type] || 0) + 1;
    });

    var colores = {
        'NUMERO': '#dc3545',
        'PERSONA': '#007bff',
        'ORGANIZACION': '#8e5fd6',
        'LUGAR': '#28a745',
        'GENTILICIO': '#5c5cd6',
        'FECHA': '#6c757d',
        'EDAD': '#e08a3c',
        'OCUPACION': '#17a2b8',
        'GRUPO_ARMADO': '#8b2e2e',
        'ROL_ARMADO': '#ffc107',
        'ETNICO': '#2e8b57'
    };

    new Chart(document.getElementById('chartEntidades'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(tipos),
            datasets: [{
                data: Object.values(tipos),
                backgroundColor: Object.keys(tipos).map(t => colores[t] || '#999')
            }]
        },
        options: {
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    @endif
});

function escapeHtmlVE(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endsection
