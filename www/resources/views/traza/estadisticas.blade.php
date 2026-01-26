@extends('layouts.app')

@section('title', 'Estadisticas de Actividad')
@section('content_header', 'Estadisticas de Actividad del Sistema')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Periodo de Analisis</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('traza.estadisticas') }}" method="GET" class="form-inline">
            <div class="form-group mr-3">
                <label for="fecha_desde" class="mr-2">Desde:</label>
                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}">
            </div>
            <div class="form-group mr-3">
                <label for="fecha_hasta" class="mr-2">Hasta:</label>
                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sync mr-1"></i>Actualizar
            </button>
            <a href="{{ route('traza.index') }}" class="btn btn-default ml-2">
                <i class="fas fa-list mr-1"></i>Ver Listado
            </a>
        </form>
    </div>
</div>

<div class="row">
    <!-- Actividad por Usuario -->
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Top 10 Usuarios mas Activos</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th class="text-right">Acciones</th>
                            <th style="width: 40%">Barra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $maxUsuario = $actividadPorUsuario->max('total') ?: 1;
                        @endphp
                        @forelse($actividadPorUsuario as $item)
                        <tr>
                            <td>
                                @if($item->rel_usuario)
                                    {{ $item->rel_usuario->name }}
                                @else
                                    <span class="text-muted">Usuario ID: {{ $item->id_usuario }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <span class="badge badge-info">{{ number_format($item->total) }}</span>
                            </td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-primary" style="width: {{ ($item->total / $maxUsuario) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Sin datos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Actividad por Accion -->
    <div class="col-md-6">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Actividad por Tipo de Accion</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Accion</th>
                            <th class="text-right">Total</th>
                            <th style="width: 40%">Barra</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $maxAccion = $actividadPorAccion->max('total') ?: 1;
                            $colores = [
                                'crear' => 'success',
                                'editar' => 'warning',
                                'eliminar' => 'danger',
                                'ver' => 'info',
                                'descargar' => 'primary',
                                'subir' => 'success',
                                'exportar' => 'primary',
                            ];
                        @endphp
                        @forelse($actividadPorAccion as $item)
                        @php
                            $color = $colores[$item->accion] ?? 'secondary';
                        @endphp
                        <tr>
                            <td>
                                <span class="badge badge-{{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $item->accion)) }}
                                </span>
                            </td>
                            <td class="text-right">
                                <span class="badge badge-info">{{ number_format($item->total) }}</span>
                            </td>
                            <td>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-{{ $color }}" style="width: {{ ($item->total / $maxAccion) * 100 }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted">Sin datos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Actividad por Dia -->
<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Actividad Diaria</h3>
    </div>
    <div class="card-body">
        @if($actividadPorDia->count() > 0)
        <div class="chart-container" style="height: 300px;">
            <canvas id="chart-actividad-diaria"></canvas>
        </div>
        @else
        <p class="text-center text-muted">No hay datos para el periodo seleccionado</p>
        @endif
    </div>
</div>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if($actividadPorDia->count() > 0)
    var ctx = document.getElementById('chart-actividad-diaria').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($actividadPorDia->pluck('fecha')->map(function($f) { return \Carbon\Carbon::parse($f)->format('d/m'); })) !!},
            datasets: [{
                label: 'Acciones',
                data: {!! json_encode($actividadPorDia->pluck('total')) !!},
                borderColor: '#17a2b8',
                backgroundColor: 'rgba(23, 162, 184, 0.1)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    @endif
});
</script>
@endsection
