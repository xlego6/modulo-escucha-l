@extends('layouts.app')

@section('title', 'Monitor — Importación #' . $importacion->id_importacion)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Importación #{{ $importacion->id_importacion }} — Paso 4 de 4</h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-file-csv text-success"></i> {{ $importacion->nombre_archivo }}
                    · {{ $importacion->total_expedientes }} expedientes
                </p>
            </div>
            <div class="col-sm-4 text-right">
                <a href="{{ route('importacion.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-list"></i> Historial
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @include('importacion._pasos', ['paso_actual' => 4])

    {{-- Resumen en tiempo real --}}
    <div class="row" id="resumen-cards">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-secondary"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pendientes</span>
                    <span class="info-box-number" id="cnt-pendientes">{{ $importacion->total_expedientes - $importacion->procesados - $importacion->con_error }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completados</span>
                    <span class="info-box-number" id="cnt-completados">{{ $importacion->procesados }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-times"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Errores</span>
                    <span class="info-box-number" id="cnt-errores">{{ $importacion->con_error }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-folder-open"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total</span>
                    <span class="info-box-number">{{ $importacion->total_expedientes }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra de progreso --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
                <span id="lbl-estado" class="font-weight-bold">
                    <span class="badge badge-{{ $importacion->clase_estado }}">
                        {{ $importacion->etiqueta_estado }}
                    </span>
                </span>
                <span id="lbl-porcentaje">{{ $importacion->porcentaje }}%</span>
            </div>
            <div class="progress" style="height: 20px;">
                <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                    role="progressbar"
                    style="width: {{ $importacion->porcentaje }}%"
                    aria-valuenow="{{ $importacion->porcentaje }}"
                    aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de expedientes --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Estado por expediente</h3>
            <div class="card-tools">
                <span class="badge badge-secondary mr-2" id="lbl-ultimo-update">Actualizando…</span>
            </div>
        </div>
        <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-sm mb-0">
                <thead class="thead-light" style="position: sticky; top: 0; z-index: 1;">
                    <tr>
                        <th style="width:100px">ID CSV</th>
                        <th style="width:120px">Estado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody id="tbody-expedientes">
                    <tr><td colspan="3" class="text-center text-muted py-3">Cargando…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Botón reintentar errores --}}
    <div id="btn-reintentar-container" class="mb-4" style="display:none;">
        <form method="POST" action="{{ route('importacion.procesar', $importacion->id_importacion) }}">
            @csrf
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-redo"></i> Reintentar expedientes con error
            </button>
        </form>
    </div>

</div>
</section>
@endsection

@section('scripts')
<script>
var estadoUrl        = '{{ route("importacion.estado", $importacion->id_importacion) }}';
var estadoFinal      = ['completado', 'con_errores'];
var intervalo        = null;
var totalExpedientes = {{ $importacion->total_expedientes }};
var yaNotificado     = false;
var tituloOriginal   = document.title;

var badgeClases = {
    pendiente:   'badge-secondary',
    procesando:  'badge-primary',
    completado:  'badge-success',
    error:       'badge-danger',
};

function pedirPermisoNotificacion() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function notificarCompletado(completados, errores) {
    if (yaNotificado) return;
    yaNotificado = true;

    var titulo, cuerpo, icono;
    if (errores > 0) {
        titulo = '⚠️ Importación completada con errores';
        cuerpo = completados + ' expedientes creados, ' + errores + ' con error.';
    } else {
        titulo = '✅ Importación completada';
        cuerpo = completados + ' expedientes creados correctamente.';
    }

    // Notificación del navegador (funciona aunque la pestaña esté en segundo plano)
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(titulo, { body: cuerpo, icon: '/favicon.ico' });
    }

    // Cambiar título de la pestaña
    document.title = (errores > 0 ? '⚠️ ' : '✅ ') + tituloOriginal;

    // Banner prominente en la página
    var color  = errores > 0 ? 'warning' : 'success';
    var icono  = errores > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle';
    var banner = '<div class="alert alert-' + color + ' alert-dismissible text-center py-3 mt-3" id="banner-completado">'
               + '<h4><i class="fas ' + icono + '"></i> ' + titulo + '</h4>'
               + '<p class="mb-0">' + cuerpo + '</p>'
               + '<button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>'
               + '</div>';
    $('.container-fluid').prepend(banner);

    // Scroll al banner
    $('html, body').animate({ scrollTop: 0 }, 400);
}

function actualizarMonitor() {
    $.getJSON(estadoUrl, function (data) {
        // Contadores
        $('#cnt-pendientes').text(data.pendientes);
        $('#cnt-completados').text(data.completados);
        $('#cnt-errores').text(data.errores);

        // Barra
        var pct = data.porcentaje;
        $('#barra-progreso').css('width', pct + '%').attr('aria-valuenow', pct);
        $('#lbl-porcentaje').text(pct + '%');

        // Tabla
        var html = '';
        (data.expedientes || []).forEach(function (exp) {
            var clase   = badgeClases[exp.estado] || 'badge-secondary';
            var etiq    = { pendiente: 'Pendiente', procesando: 'Procesando', completado: 'Completado', error: 'Error' }[exp.estado] || exp.estado;
            var detalle = '';
            if (exp.error)        detalle = '<span class="text-danger small">' + $('<div>').text(exp.error).html() + '</span>';
            if (exp.id_e_ind_fvt) detalle += ' <a href="/entrevistas/' + exp.id_e_ind_fvt + '" target="_blank" class="btn btn-xs btn-outline-primary ml-1"><i class="fas fa-external-link-alt"></i> Ver</a>';

            html += '<tr>'
                + '<td><strong>' + exp.id_csv + '</strong></td>'
                + '<td><span class="badge ' + clase + '">' + etiq + '</span></td>'
                + '<td>' + detalle + '</td>'
                + '</tr>';
        });
        $('#tbody-expedientes').html(html || '<tr><td colspan="3" class="text-center text-muted">Sin datos</td></tr>');

        // Timestamp
        var now = new Date();
        $('#lbl-ultimo-update').text('Actualizado ' + now.toLocaleTimeString());

        // Estado general
        if (estadoFinal.includes(data.estado)) {
            clearInterval(intervalo);
            var barColor = data.errores > 0 ? 'bg-warning' : 'bg-success';
            $('#barra-progreso').removeClass('progress-bar-animated progress-bar-striped bg-primary').addClass(barColor);

            notificarCompletado(data.completados, data.errores);

            if (data.errores > 0) {
                $('#btn-reintentar-container').show();
            }
        }
    });
}

$(document).ready(function () {
    pedirPermisoNotificacion();
    actualizarMonitor();
    intervalo = setInterval(actualizarMonitor, 4000);
});
</script>
@endsection
