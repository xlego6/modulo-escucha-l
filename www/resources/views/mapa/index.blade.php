@extends('layouts.app')

@section('title', 'Mapa de Entrevistas')
@section('content_header', 'Mapa de Entrevistas')

@section('css')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
    #mapa {
        height: 500px;
        width: 100%;
        border-radius: 4px;
    }
    .info-legend {
        padding: 6px 8px;
        background: white;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
        border-radius: 5px;
    }
    .info-legend h4 {
        margin: 0 0 5px;
        color: #777;
    }
    .legend-item {
        display: flex;
        align-items: center;
        margin: 3px 0;
    }
    .legend-color {
        width: 18px;
        height: 18px;
        margin-right: 8px;
        border-radius: 50%;
    }
    .tipo-selector {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    .tipo-selector .btn {
        flex: 1;
        min-width: 120px;
    }
    .tipo-selector .btn.active {
        box-shadow: 0 0 0 3px rgba(235, 192, 26, 0.5);
    }
    .tipo-selector .btn i {
        margin-right: 5px;
    }
</style>
@endsection

@section('content')
<!-- Selector de tipo de ubicacion -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-outline card-primary">
            <div class="card-header py-2">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Tipo de Ubicacion</h3>
            </div>
            <div class="card-body py-2">
                <div class="tipo-selector">
                    <button type="button" class="btn btn-outline-primary active" data-tipo="toma" id="btn-toma">
                        <i class="fas fa-map-pin"></i> Lugar de Toma
                        <small class="d-block text-muted">Donde se realizo la entrevista</small>
                    </button>
                    <button type="button" class="btn btn-outline-success" data-tipo="origen" id="btn-origen">
                        <i class="fas fa-home"></i> Origen Testimoniante
                        <small class="d-block text-muted">Lugar de nacimiento/residencia</small>
                    </button>
                    <button type="button" class="btn btn-outline-warning" data-tipo="mencionados" id="btn-mencionados">
                        <i class="fas fa-map-marked-alt"></i> Lugares Mencionados
                        <small class="d-block text-muted">Lugares referenciados en el relato</small>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Estadisticas -->
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-microphone"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Entrevistas</span>
                <span class="info-box-number" id="stat-total">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-map-marker-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Departamentos</span>
                <span class="info-box-number" id="stat-deptos">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-chart-bar"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Mayor Concentracion</span>
                <span class="info-box-number" id="stat-max">-</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-globe-americas"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Cobertura</span>
                <span class="info-box-number" id="stat-cobertura">-</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map mr-2"></i>Distribucion Geografica</h3>
                <span class="badge badge-primary ml-2" id="tipo-actual">Lugar de Toma</span>
            </div>
            <div class="card-body p-0">
                <div id="mapa"></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ol mr-2"></i>Entrevistas por Departamento</h3>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                <table class="table table-striped table-sm" id="tabla-departamentos">
                    <thead>
                        <tr>
                            <th>Departamento</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-info" id="card-detalle" style="display: none;">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Detalle: <span id="detalle-nombre"></span></h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="cerrarDetalle()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <div id="detalle-contenido"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var mapa;
var marcadores = [];
var tipoActual = 'toma';

// Colores por tipo
var coloresTipo = {
    'toma': '#007bff',
    'origen': '#28a745',
    'mencionados': '#ffc107'
};

var nombresTipo = {
    'toma': 'Lugar de Toma',
    'origen': 'Origen Testimoniante',
    'mencionados': 'Lugares Mencionados'
};

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar mapa centrado en Colombia
    mapa = L.map('mapa').setView([4.5, -74.0], 5);

    // Capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapa);

    // Configurar botones de tipo
    document.querySelectorAll('.tipo-selector .btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Quitar active de todos
            document.querySelectorAll('.tipo-selector .btn').forEach(function(b) {
                b.classList.remove('active');
            });
            // Activar el seleccionado
            this.classList.add('active');

            tipoActual = this.dataset.tipo;
            document.getElementById('tipo-actual').textContent = nombresTipo[tipoActual];

            // Recargar datos
            cargarDatos();
        });
    });

    // Cargar datos iniciales
    cargarDatos();
});

function cargarDatos() {
    // Mostrar cargando
    document.querySelector('#tabla-departamentos tbody').innerHTML =
        '<tr><td colspan="2" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

    // Limpiar marcadores anteriores
    limpiarMarcadores();

    fetch('{{ route("mapa.datos") }}?tipo=' + tipoActual)
        .then(response => response.json())
        .then(data => {
            // Actualizar estadisticas
            document.getElementById('stat-total').textContent = data.estadisticas.total_entrevistas.toLocaleString();
            document.getElementById('stat-deptos').textContent = data.estadisticas.total_departamentos;
            document.getElementById('stat-max').textContent = data.estadisticas.max_entrevistas.toLocaleString();
            document.getElementById('stat-cobertura').textContent = Math.round(data.estadisticas.total_departamentos / 33 * 100) + '%';

            // Agregar marcadores
            agregarMarcadores(data.datos, data.estadisticas.max_entrevistas);

            // Llenar tabla
            llenarTabla(data.datos);
        })
        .catch(error => {
            console.error('Error cargando datos:', error);
            document.querySelector('#tabla-departamentos tbody').innerHTML =
                '<tr><td colspan="2" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error al cargar datos</td></tr>';
        });
}

function limpiarMarcadores() {
    marcadores.forEach(function(marker) {
        mapa.removeLayer(marker);
    });
    marcadores = [];
}

function agregarMarcadores(datos, maxEntrevistas) {
    var color = coloresTipo[tipoActual] || '#EBC01A';

    datos.forEach(function(item) {
        var radio = Math.max(10, Math.min(40, (item.total / maxEntrevistas) * 40));

        var marker = L.circleMarker([item.lat, item.lng], {
            radius: radio,
            fillColor: color,
            color: '#000',
            weight: 1,
            opacity: 1,
            fillOpacity: 0.7
        }).addTo(mapa);

        marker.bindPopup(
            '<strong>' + item.nombre + '</strong><br>' +
            'Entrevistas: <b>' + item.total + '</b><br>' +
            '<small class="text-muted">' + nombresTipo[tipoActual] + '</small><br>' +
            '<a href="javascript:verDetalle(' + item.id + ')">Ver detalle</a>'
        );

        marker.on('click', function() {
            verDetalle(item.id);
        });

        marcadores.push(marker);
    });
}

function llenarTabla(datos) {
    var tbody = document.querySelector('#tabla-departamentos tbody');
    tbody.innerHTML = '';

    // Ordenar por total descendente
    datos.sort((a, b) => b.total - a.total);

    if (datos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No hay datos para este tipo de ubicacion</td></tr>';
        return;
    }

    datos.forEach(function(item, index) {
        var tr = document.createElement('tr');
        tr.style.cursor = 'pointer';
        tr.onclick = function() { verDetalle(item.id); centrarMapa(item.lat, item.lng); };

        var badgeClass = tipoActual === 'toma' ? 'badge-primary' :
                        (tipoActual === 'origen' ? 'badge-success' : 'badge-warning');

        tr.innerHTML = '<td>' + (index + 1) + '. ' + item.nombre + '</td>' +
                      '<td class="text-right"><span class="badge ' + badgeClass + '">' + item.total + '</span></td>';
        tbody.appendChild(tr);
    });
}

function centrarMapa(lat, lng) {
    mapa.setView([lat, lng], 7);
}

function verDetalle(id) {
    document.getElementById('card-detalle').style.display = 'block';
    document.getElementById('detalle-contenido').innerHTML = '<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>';

    fetch('{{ url("mapa/departamento") }}/' + id + '?tipo=' + tipoActual)
        .then(response => response.json())
        .then(data => {
            document.getElementById('detalle-nombre').textContent = data.departamento;

            var html = '<table class="table table-sm mb-0">';

            if (data.municipios && data.municipios.length > 0) {
                html += '<thead><tr><th colspan="2" class="bg-light">Municipios con mas entrevistas</th></tr></thead>';
                html += '<tbody>';
                data.municipios.forEach(function(mun) {
                    var nombre = mun.nombre || 'Sin municipio';
                    html += '<tr><td>' + nombre + '</td><td class="text-right"><span class="badge badge-info">' + mun.total + '</span></td></tr>';
                });
                html += '</tbody>';
            }

            html += '</table>';

            if (data.entrevistas && data.entrevistas.length > 0) {
                html += '<div class="p-2 bg-light"><strong>Ultimas entrevistas:</strong></div>';
                html += '<ul class="list-group list-group-flush">';
                data.entrevistas.slice(0, 5).forEach(function(ent) {
                    html += '<li class="list-group-item p-2">';
                    html += '<small class="text-muted">' + ent.entrevista_codigo + '</small><br>';
                    html += '<a href="{{ url("entrevistas") }}/' + ent.id_e_ind_fvt + '">' + (ent.titulo || 'Sin titulo') + '</a>';
                    html += '</li>';
                });
                html += '</ul>';
            }

            if ((!data.entrevistas || data.entrevistas.length === 0) && (!data.municipios || data.municipios.length === 0)) {
                html = '<div class="alert alert-info m-2">No hay datos detallados para este departamento</div>';
            }

            document.getElementById('detalle-contenido').innerHTML = html;
        })
        .catch(error => {
            document.getElementById('detalle-contenido').innerHTML = '<div class="alert alert-danger m-2">Error cargando detalle</div>';
        });
}

function cerrarDetalle() {
    document.getElementById('card-detalle').style.display = 'none';
}
</script>
@endsection
