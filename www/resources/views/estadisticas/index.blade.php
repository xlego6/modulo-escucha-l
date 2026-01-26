@extends('layouts.app')

@section('title', 'Estadisticas')
@section('content_header', 'Estadisticas Generales')

@section('css')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endsection

@section('content')
<!-- Contadores principales -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($totales['entrevistas']) }}</h3>
                <p>Entrevistas</p>
            </div>
            <div class="icon">
                <i class="fas fa-microphone"></i>
            </div>
            <a href="{{ route('entrevistas.index') }}" class="small-box-footer">
                Ver todas <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($totales['personas']) }}</h3>
                <p>Personas</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('personas.index') }}" class="small-box-footer">
                Ver todas <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($totales['adjuntos']) }}</h3>
                <p>Archivos</p>
            </div>
            <div class="icon">
                <i class="fas fa-paperclip"></i>
            </div>
            <a href="{{ route('adjuntos.index') }}" class="small-box-footer">
                Ver todos <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format($totales['entrevistadores']) }}</h3>
                <p>Entrevistadores</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <span class="small-box-footer">&nbsp;</span>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row">
    <!-- Entrevistas por mes -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Entrevistas por Mes (ultimos 12 meses)</h3>
            </div>
            <div class="card-body">
                <canvas id="chartEntrevistasMes" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Personas por sexo -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Personas por Sexo</h3>
            </div>
            <div class="card-body">
                <canvas id="chartPersonasSexo" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Entrevistas por territorio -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Entrevistas por Territorio (Top 10)</h3>
            </div>
            <div class="card-body">
                @if($entrevistas_por_territorio->count() > 0)
                <canvas id="chartTerritorios" height="200"></canvas>
                @else
                <p class="text-muted text-center">Sin datos de territorio</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Personas por grupo étnico -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Personas por Grupo Etnico</h3>
            </div>
            <div class="card-body">
                @if($personas_por_etnia->count() > 0)
                <canvas id="chartEtnias" height="200"></canvas>
                @else
                <p class="text-muted text-center">Sin datos de grupo etnico</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Top entrevistadores -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-trophy"></i> Top Entrevistadores</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th class="text-right">Entrevistas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($top_entrevistadores as $index => $e)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $e->name }}</td>
                            <td class="text-right"><span class="badge badge-info">{{ $e->total }}</span></td>
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

    <!-- Adjuntos por tipo -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file"></i> Archivos por Tipo</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th class="text-right">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($adjuntos_por_tipo as $a)
                        <tr>
                            <td>{{ $a->tipo }}</td>
                            <td class="text-right"><span class="badge badge-warning">{{ $a->total }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted">Sin datos</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="p-3 border-top">
                    <strong>Almacenamiento total:</strong>
                    <span class="float-right">
                        @if($tamano_total_adjuntos >= 1073741824)
                            {{ number_format($tamano_total_adjuntos / 1073741824, 2) }} GB
                        @elseif($tamano_total_adjuntos >= 1048576)
                            {{ number_format($tamano_total_adjuntos / 1048576, 2) }} MB
                        @elseif($tamano_total_adjuntos >= 1024)
                            {{ number_format($tamano_total_adjuntos / 1024, 2) }} KB
                        @else
                            {{ $tamano_total_adjuntos }} bytes
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Entrevistas recientes -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock"></i> Entrevistas Recientes</h3>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($entrevistas_recientes as $e)
                    <li class="list-group-item">
                        <a href="{{ route('entrevistas.show', $e->id_e_ind_fvt) }}">
                            <strong>{{ $e->entrevista_codigo }}</strong>
                        </a>
                        <br>
                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($e->titulo, 35) }}</small>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted">Sin entrevistas</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Colores para gráficos
    const colores = [
        '#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8',
        '#6f42c1', '#fd7e14', '#20c997', '#e83e8c', '#6c757d'
    ];

    // Entrevistas por mes
    const dataMeses = @json($entrevistas_por_mes);
    const labelsMeses = Object.keys(dataMeses);
    const valuesMeses = Object.values(dataMeses);

    if (labelsMeses.length > 0) {
        new Chart(document.getElementById('chartEntrevistasMes'), {
            type: 'line',
            data: {
                labels: labelsMeses,
                datasets: [{
                    label: 'Entrevistas',
                    data: valuesMeses,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Personas por sexo
    const dataSexo = @json($personas_por_sexo);
    if (dataSexo.length > 0) {
        new Chart(document.getElementById('chartPersonasSexo'), {
            type: 'doughnut',
            data: {
                labels: dataSexo.map(d => d.sexo),
                datasets: [{
                    data: dataSexo.map(d => d.total),
                    backgroundColor: colores.slice(0, dataSexo.length)
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Entrevistas por territorio
    const dataTerritorios = @json($entrevistas_por_territorio);
    if (dataTerritorios.length > 0) {
        new Chart(document.getElementById('chartTerritorios'), {
            type: 'bar',
            data: {
                labels: dataTerritorios.map(d => d.territorio),
                datasets: [{
                    label: 'Entrevistas',
                    data: dataTerritorios.map(d => d.total),
                    backgroundColor: '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Personas por etnia
    const dataEtnias = @json($personas_por_etnia);
    if (dataEtnias.length > 0) {
        new Chart(document.getElementById('chartEtnias'), {
            type: 'pie',
            data: {
                labels: dataEtnias.map(d => d.etnia),
                datasets: [{
                    data: dataEtnias.map(d => d.total),
                    backgroundColor: colores.slice(0, dataEtnias.length)
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
@endsection
