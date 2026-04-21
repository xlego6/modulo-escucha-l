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
            <span class="small-box-footer">&nbsp;</span>
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
            <span class="small-box-footer">&nbsp;</span>
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
    <!-- Personas por grupo étnico -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Personas por Grupo Etnico</h3>
            </div>
            <div class="card-body">
                @if($personas_por_etnia->count() > 0)
                <canvas id="chartEtnias" height="80"></canvas>
                @else
                <p class="text-muted text-center">Sin datos de grupo etnico</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SECCIÓN: Metadatos de Contenido (Paso 3) -->
<!-- ============================================================ -->
<div class="row mt-2">
    <div class="col-12">
        <h5 class="text-muted border-bottom pb-2">
            <i class="fas fa-file-alt mr-1"></i> Metadatos de Contenido del Testimonio
        </h5>
    </div>
</div>

<div class="row">
    <!-- Hechos Victimizantes -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Hechos Victimizantes</h3>
            </div>
            <div class="card-body">
                @if($hechos_victimizantes->count() > 0)
                <canvas id="chartHechosVictimizantes" height="200"></canvas>
                @else
                <p class="text-muted text-center">Sin datos registrados</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Prácticas de Resistencia -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Prácticas de Resistencia</h3>
            </div>
            <div class="card-body">
                @if($practicas_resistencia->count() > 0)
                <canvas id="chartResistencias" height="200"></canvas>
                @else
                <p class="text-muted text-center">Sin datos registrados</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Hechos Victimizantes por Año -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Testimonios con Hechos Victimizantes por Año</h3>
            </div>
            <div class="card-body">
                @if(!empty($hechos_por_anio))
                <canvas id="chartHechosPorAnio" height="80"></canvas>
                @else
                <p class="text-muted text-center">Sin datos de fechas de hechos</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SECCIÓN: Información del Testimoniante (Paso 2) -->
<!-- ============================================================ -->
<div class="row mt-2">
    <div class="col-12">
        <h5 class="text-muted border-bottom pb-2">
            <i class="fas fa-users mr-1"></i> Información del Testimoniante
        </h5>
    </div>
</div>

<div class="row">
    <!-- Poblaciones -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Poblaciones</h3>
            </div>
            <div class="card-body">
                @if($poblaciones_testimoniantes->count() > 0)
                <canvas id="chartPoblaciones" height="220"></canvas>
                @else
                <p class="text-muted text-center">Sin datos registrados</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Sexo -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-venus-mars"></i> Personas por Sexo</h3>
            </div>
            <div class="card-body">
                @if($sexo_testimoniantes->count() > 0)
                <canvas id="chartSexoTestimoniantes" height="220"></canvas>
                @else
                <p class="text-muted text-center">Sin datos registrados</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Rango Etario -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Rango de Edad</h3>
            </div>
            <div class="card-body">
                @if($rangos_etarios->count() > 0)
                <canvas id="chartRangosEtarios" height="80"></canvas>
                @else
                <p class="text-muted text-center">Sin datos registrados</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- SECCIÓN: Clasificación y Dependencia -->
<!-- ============================================================ -->
<div class="row mt-2">
    <div class="col-12">
        <h5 class="text-muted border-bottom pb-2">
            <i class="fas fa-shield-alt mr-1"></i> Clasificación y Dependencia
        </h5>
    </div>
</div>

@php
    $coloresClasif = [
        5  => ['hex' => '#dc3545', 'label' => 'Inteligencia y Contrainteligencia'],
        4  => ['hex' => '#fd7e14', 'label' => 'Pública-Clasificada y Reservada'],
        3  => ['hex' => '#ffc107', 'label' => 'Pública-Clasificada'],
        2  => ['hex' => '#17a2b8', 'label' => 'Pública-Reservada'],
        1  => ['hex' => '#28a745', 'label' => 'Pública-Pública'],
        -1 => ['hex' => '#6c757d', 'label' => 'Pendiente de Calificación'],
    ];
    $totalClasifE   = collect($clasif_por_entrevista)->sum('total');
@endphp

<div class="row">
    <!-- Clasificación por entrevista -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie"></i> Clasificación por Entrevista</h3>
                <div class="card-tools">
                    <small class="text-muted">Clasificación más restrictiva de cada entrevista</small>
                </div>
            </div>
            <div class="card-body">
                @if(count($clasif_por_entrevista) > 0)
                <div class="row">
                    <div class="col-6">
                        <canvas id="chartClasifEntrevista" height="200"></canvas>
                    </div>
                    <div class="col-6">
                        <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem">
                            @foreach($clasif_por_entrevista as $row)
                            @php
                                $pct = $totalClasifE > 0 ? round($row->total * 100 / $totalClasifE, 1) : 0;
                                $color = $coloresClasif[$row->nivel]['hex'] ?? '#adb5bd';
                            @endphp
                            <tr>
                                <td style="width:10px">
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $color }}"></span>
                                </td>
                                <td>{{ $row->clasificacion }}</td>
                                <td class="text-right text-nowrap">
                                    <strong>{{ $row->total }}</strong>
                                    <span class="text-muted">({{ $pct }}%)</span>
                                </td>
                            </tr>
                            @endforeach
                            <tr class="border-top">
                                <td colspan="2"><strong>Total</strong></td>
                                <td class="text-right"><strong>{{ $totalClasifE }}</strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                @else
                <p class="text-muted text-center">Sin consentimientos con prueba de daño registrada</p>
                @endif
            </div>
        </div>
    </div>

</div>

<!-- Dependencia -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sitemap"></i> Entrevistas por Dependencia</h3>
            </div>
            <div class="card-body">
                @if($entrevistas_por_dependencia->count() > 0)
                <canvas id="chartDependencia" height="100"></canvas>
                @else
                <p class="text-muted text-center">Sin datos de dependencia registrados</p>
                @endif
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

    // ============================================================
    // Contenido del Testimonio (Paso 3)
    // ============================================================

    // Hechos Victimizantes
    const dataHechos = @json($hechos_victimizantes);
    if (dataHechos.length > 0) {
        new Chart(document.getElementById('chartHechosVictimizantes'), {
            type: 'bar',
            data: {
                labels: dataHechos.map(d => d.hecho),
                datasets: [{
                    label: 'Testimonios',
                    data: dataHechos.map(d => d.total),
                    backgroundColor: '#dc3545'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Prácticas de Resistencia
    const dataResistencias = @json($practicas_resistencia);
    if (dataResistencias.length > 0) {
        new Chart(document.getElementById('chartResistencias'), {
            type: 'bar',
            data: {
                labels: dataResistencias.map(d => d.practica),
                datasets: [{
                    label: 'Testimonios',
                    data: dataResistencias.map(d => d.total),
                    backgroundColor: '#6f42c1'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Hechos victimizantes por año
    const dataHechosPorAnio = @json($hechos_por_anio);
    if (dataHechosPorAnio && Object.keys(dataHechosPorAnio).length > 0) {
        new Chart(document.getElementById('chartHechosPorAnio'), {
            type: 'line',
            data: {
                labels: Object.keys(dataHechosPorAnio),
                datasets: [{
                    label: 'Testimonios',
                    data: Object.values(dataHechosPorAnio),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                    x: { ticks: { maxTicksLimit: 20 } }
                }
            }
        });
    }

    // ============================================================
    // Testimoniantes (Paso 2)
    // ============================================================

    // Poblaciones
    const dataPoblaciones = @json($poblaciones_testimoniantes);
    if (dataPoblaciones.length > 0) {
        new Chart(document.getElementById('chartPoblaciones'), {
            type: 'bar',
            data: {
                labels: dataPoblaciones.map(d => d.poblacion),
                datasets: [{
                    label: 'Personas',
                    data: dataPoblaciones.map(d => d.total),
                    backgroundColor: '#20c997'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Sexo (testimoniantes)
    const dataSexoT = @json($sexo_testimoniantes);
    if (dataSexoT.length > 0) {
        new Chart(document.getElementById('chartSexoTestimoniantes'), {
            type: 'doughnut',
            data: {
                labels: dataSexoT.map(d => d.sexo),
                datasets: [{
                    data: dataSexoT.map(d => d.total),
                    backgroundColor: colores.slice(0, dataSexoT.length)
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // Rango Etario
    const dataRangos = @json($rangos_etarios);
    if (dataRangos.length > 0) {
        new Chart(document.getElementById('chartRangosEtarios'), {
            type: 'bar',
            data: {
                labels: dataRangos.map(d => d.rango),
                datasets: [{
                    label: 'Personas',
                    data: dataRangos.map(d => d.total),
                    backgroundColor: '#fd7e14'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // ============================================================
    // Clasificación y Dependencia
    // ============================================================

    const clasifColores = {
         5: '#dc3545',
         4: '#fd7e14',
         3: '#ffc107',
         2: '#17a2b8',
         1: '#28a745',
        '-1': '#6c757d'
    };

    // Clasificación por entrevista
    const dataClasifE = @json($clasif_por_entrevista);
    if (dataClasifE.length > 0) {
        new Chart(document.getElementById('chartClasifEntrevista'), {
            type: 'doughnut',
            data: {
                labels: dataClasifE.map(d => d.clasificacion),
                datasets: [{
                    data: dataClasifE.map(d => d.total),
                    backgroundColor: dataClasifE.map(d => clasifColores[d.nivel] || '#adb5bd')
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } }
            }
        });
    }

    // Dependencia
    const dataDependencia = @json($entrevistas_por_dependencia);
    if (dataDependencia.length > 0) {
        new Chart(document.getElementById('chartDependencia'), {
            type: 'bar',
            data: {
                labels: dataDependencia.map(d => d.dependencia),
                datasets: [{
                    label: 'Entrevistas',
                    data: dataDependencia.map(d => d.total),
                    backgroundColor: '#17a2b8'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});
</script>
@endsection
