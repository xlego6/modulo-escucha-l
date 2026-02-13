@extends('layouts.app')

@section('title', 'Transcripcion Automatizada')
@section('content_header', 'Transcripcion Automatizada')

@section('content')
<!-- Panel de Resultado Individual -->
<div class="row" id="panel-resultado" style="display: none;">
    <div class="col-12">
        <div class="card" id="card-resultado">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Resultado de Transcripcion</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="$('#panel-resultado').slideUp()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="resultado-loading" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5>Transcribiendo audio...</h5>
                    <p class="text-muted">Este proceso puede tomar varios minutos dependiendo de la duracion del audio.</p>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 100%"></div>
                    </div>
                </div>
                <div id="resultado-exito" style="display: none;">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i>
                        <strong>Transcripcion completada exitosamente</strong>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted">Entrevista:</small><br>
                            <strong id="res-codigo"></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Caracteres:</small><br>
                            <strong id="res-caracteres"></strong>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Hablantes detectados:</small><br>
                            <strong id="res-hablantes"></strong>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Vista previa del texto:</label>
                        <textarea class="form-control" id="res-texto" rows="8" readonly></textarea>
                    </div>
                    <a href="#" id="btn-editar-transcripcion" class="btn btn-success">
                        <i class="fas fa-edit mr-2"></i>Editar Transcripcion
                    </a>
                </div>
                <div id="resultado-error" style="display: none;">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Error en la transcripcion</strong>
                    </div>
                    <p id="res-error-mensaje" class="text-danger"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Panel de Progreso en Lote -->
<div class="row" id="panel-lote" style="display: none;">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Procesamiento en Lote</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="btn-cancelar-lote" title="Cancelar">
                        <i class="fas fa-stop"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3 text-center">
                        <h3 class="mb-0" id="lote-procesados">0</h3>
                        <small class="text-muted">Procesados</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="mb-0 text-primary" id="lote-total">0</h3>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="mb-0 text-success" id="lote-exitosos">0</h3>
                        <small class="text-muted">Exitosos</small>
                    </div>
                    <div class="col-md-3 text-center">
                        <h3 class="mb-0 text-danger" id="lote-errores">0</h3>
                        <small class="text-muted">Errores</small>
                    </div>
                </div>

                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-success" id="lote-progress-bar" role="progressbar" style="width: 0%">
                        <span id="lote-progress-text">0%</span>
                    </div>
                </div>

                <div id="lote-status" class="mb-3">
                    <i class="fas fa-spinner fa-spin mr-2"></i>
                    <span id="lote-mensaje">Iniciando...</span>
                </div>

                <div class="card card-outline card-secondary collapsed-card">
                    <div class="card-header py-2">
                        <h3 class="card-title text-sm"><i class="fas fa-list mr-2"></i>Registro de actividad</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0" style="display: none; max-height: 200px; overflow-y: auto;">
                        <ul class="list-group list-group-flush" id="lote-log">
                            <!-- Log entries -->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-footer" id="lote-footer" style="display: none;">
                <button class="btn btn-secondary" onclick="$('#panel-lote').slideUp(); location.reload();">
                    <i class="fas fa-check mr-2"></i>Cerrar y Actualizar
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="callout callout-info">
            <h5><i class="fas fa-info-circle mr-2"></i>WhisperX - Motor de Transcripcion</h5>
            <p class="mb-0">
                Sistema de transcripcion automatica basado en WhisperX con soporte para diarizacion
                (identificacion de hablantes) y marcas de tiempo precisas.
            </p>
        </div>
    </div>
</div>

@if($enProceso->count() > 0)
<div class="row">
    <div class="col-12">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-spinner fa-spin mr-2"></i>En Proceso ({{ $enProceso->count() }})</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Codigo</th>
                            <th>Titulo</th>
                            <th>Archivos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enProceso as $ent)
                        <tr>
                            <td><code>{{ $ent->entrevista_codigo }}</code></td>
                            <td>{{ \Illuminate\Support\Str::limit($ent->titulo, 40) }}</td>
                            <td>{{ $ent->rel_adjuntos->count() }} archivo(s)</td>
                            <td>
                                <span class="badge badge-warning">
                                    <i class="fas fa-spinner fa-spin mr-1"></i>Procesando
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary" disabled>
                                    <i class="fas fa-clock"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas Pendientes de Transcripcion</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Codigo</th>
                            <th>Titulo</th>
                            <th>Audios</th>
                            <th>Duracion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entrevistas as $entrevista)
                        <tr>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input check-item"
                                           id="check{{ $entrevista->id_e_ind_fvt }}"
                                           value="{{ $entrevista->id_e_ind_fvt }}">
                                    <label class="custom-control-label" for="check{{ $entrevista->id_e_ind_fvt }}"></label>
                                </div>
                            </td>
                            <td><code>{{ $entrevista->entrevista_codigo }}</code></td>
                            <td>
                                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}">
                                    {{ \Illuminate\Support\Str::limit($entrevista->titulo, 35) }}
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $entrevista->rel_adjuntos->count() }}</span>
                            </td>
                            <td>
                                @php
                                    $duracion = $entrevista->rel_adjuntos->sum('duracion');
                                    $horas = floor($duracion / 3600);
                                    $minutos = floor(($duracion % 3600) / 60);
                                @endphp
                                @if($duracion > 0)
                                    {{ $horas }}h {{ $minutos }}m
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-transcribir"
                                        data-id="{{ $entrevista->id_e_ind_fvt }}"
                                        title="Iniciar transcripcion">
                                    <i class="fas fa-play"></i>
                                </button>
                                <a href="{{ route('adjuntos.gestionar', $entrevista->id_e_ind_fvt) }}"
                                   class="btn btn-sm btn-secondary" title="Ver adjuntos">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                No hay entrevistas pendientes de transcripcion
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($entrevistas->hasPages())
            <div class="card-footer">
                {{ $entrevistas->links() }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <!-- Acciones en lote -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Acciones en Lote</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Seleccione entrevistas de la lista para procesarlas en lote.</p>
                <div class="form-group">
                    <label>Entrevistas seleccionadas:</label>
                    <span id="count-seleccionadas" class="badge badge-primary">0</span>
                </div>
                <button class="btn btn-primary btn-block" id="btn-procesar-lote" disabled>
                    <i class="fas fa-play mr-2"></i>Iniciar Transcripcion
                </button>
            </div>
        </div>

        <!-- Configuración -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog mr-2"></i>Configuracion</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Modelo de Whisper</label>
                    <select class="form-control" id="modelo-whisper">
                        <option value="large-v3-turbo" selected>large-v3-turbo (Recomendado)</option>
                        <option value="large-v3">large-v3 (Mas preciso)</option>
                        <option value="large-v2">large-v2</option>
                        <option value="medium">medium (Mas rapido)</option>
                        <option value="small">small (Rapido)</option>
                    </select>
                    <small class="text-muted">Modelos mas grandes son mas precisos pero mas lentos</small>
                </div>
                <div class="form-group">
                    <label>Idioma</label>
                    <select class="form-control" id="idioma">
                        <option value="es">Español</option>
                        <option value="auto">Detectar automaticamente</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dispositivo</label>
                    <select class="form-control" id="dispositivo">
                        <option value="auto">Automatico</option>
                        <option value="cuda">GPU (CUDA)</option>
                        <option value="cpu">CPU</option>
                    </select>
                    <small class="text-muted">
                        <span id="gpu-status">
                            <i class="fas fa-spinner fa-spin"></i> Detectando GPU...
                        </span>
                    </small>
                </div>
                <div class="custom-control custom-switch mb-2">
                    <input type="checkbox" class="custom-control-input" id="diarizar" checked>
                    <label class="custom-control-label" for="diarizar">Diarizacion (identificar hablantes)</label>
                </div>
                <div id="hf-token-group" class="mt-2">
                    <label class="small">Token de HuggingFace</label>
                    <div class="input-group input-group-sm">
                        <input type="password" class="form-control" id="hf_token"
                               placeholder="hf_..." autocomplete="off">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="btn-toggle-token" title="Mostrar/ocultar token">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Opcional si ya esta configurado en el servidor. Se usa solo para esta sesion.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Toggle visibilidad del token
    $('#btn-toggle-token').on('click', function() {
        var input = $('#hf_token');
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Mostrar/ocultar campo token segun diarizacion
    $('#diarizar').on('change', function() {
        $('#hf-token-group').toggle($(this).is(':checked'));
    });

    // Detectar GPU disponible
    detectarGPU();

    // Contador de seleccionados
    $('.check-item').on('change', function() {
        var count = $('.check-item:checked').length;
        $('#count-seleccionadas').text(count);
        $('#btn-procesar-lote').prop('disabled', count === 0);
    });

    // Transcribir individual
    $('.btn-transcribir').on('click', function() {
        var id = $(this).data('id');
        var btn = $(this);
        var row = btn.closest('tr');
        var codigo = row.find('code').text();

        if (!confirm('¿Iniciar transcripcion de esta entrevista?\n\nEsto puede tomar varios minutos.')) return;

        // Mostrar panel de resultado
        $('#panel-resultado').slideDown();
        $('#resultado-loading').show();
        $('#resultado-exito, #resultado-error').hide();
        $('#card-resultado').removeClass('card-success card-danger').addClass('card-primary');

        // Scroll al panel
        $('html, body').animate({ scrollTop: 0 }, 300);

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("procesamientos/transcripcion") }}/' + id + '/iniciar',
            method: 'POST',
            timeout: 1200000, // 20 minutos (archivos grandes pueden tardar)
            data: {
                _token: '{{ csrf_token() }}',
                modelo: $('#modelo-whisper').val(),
                idioma: $('#idioma').val(),
                dispositivo: $('#dispositivo').val(),
                diarizar: $('#diarizar').is(':checked') ? 1 : 0,
                hf_token: $('#hf_token').val() || ''
            },
            success: function(response) {
                $('#resultado-loading').hide();

                if (response.success) {
                    $('#card-resultado').removeClass('card-primary card-danger').addClass('card-success');
                    $('#resultado-exito').show();
                    $('#res-codigo').text(codigo);
                    $('#res-caracteres').text(response.text_length ? response.text_length.toLocaleString() : '0');
                    $('#res-hablantes').text(response.speakers || 'N/A');
                    $('#res-texto').val(response.text || 'Sin texto');
                    $('#btn-editar-transcripcion').attr('href', '{{ url("procesamientos/edicion") }}/' + id);

                    // Marcar fila como completada
                    btn.removeClass('btn-primary').addClass('btn-success')
                       .html('<i class="fas fa-check"></i>').prop('disabled', true);
                    row.addClass('table-success');
                } else {
                    mostrarError(response.error || 'Error desconocido');
                    btn.prop('disabled', false).html('<i class="fas fa-play"></i>');
                }
            },
            error: function(xhr) {
                $('#resultado-loading').hide();
                var errorMsg = xhr.responseJSON?.error || 'Error de conexion con el servidor';
                mostrarError(errorMsg);
                btn.prop('disabled', false).html('<i class="fas fa-play"></i>');
            }
        });
    });

    function mostrarError(mensaje) {
        $('#card-resultado').removeClass('card-primary card-success').addClass('card-danger');
        $('#resultado-error').show();
        $('#res-error-mensaje').text(mensaje);
    }

    // Procesar en lote
    $('#btn-procesar-lote').on('click', function() {
        var ids = [];
        $('.check-item:checked').each(function() {
            ids.push(parseInt($(this).val()));
        });

        if (ids.length === 0) return;

        if (!confirm('¿Iniciar transcripcion de ' + ids.length + ' entrevista(s)?\n\nEste proceso puede tomar varios minutos por cada entrevista.')) return;

        iniciarProcesamientoLote(ids);
    });

    // Cancelar lote
    $('#btn-cancelar-lote').on('click', function() {
        if (window.loteEventSource) {
            if (confirm('¿Cancelar el procesamiento en lote?\n\nLas transcripciones ya completadas se conservaran.')) {
                window.loteEventSource.close();
                $('#lote-status').html('<i class="fas fa-stop-circle text-warning mr-2"></i><span>Procesamiento cancelado por el usuario</span>');
                $('#lote-footer').show();
                addLogEntry('warning', 'Procesamiento cancelado por el usuario');
            }
        }
    });
});

var loteExitosos = 0;
var loteErrores = 0;

function iniciarProcesamientoLote(ids) {
    // Resetear contadores
    loteExitosos = 0;
    loteErrores = 0;
    $('#lote-procesados, #lote-exitosos, #lote-errores').text('0');
    $('#lote-total').text(ids.length);
    $('#lote-progress-bar').css('width', '0%');
    $('#lote-progress-text').text('0%');
    $('#lote-log').empty();
    $('#lote-footer').hide();

    // Mostrar panel
    $('#panel-lote').slideDown();
    $('html, body').animate({ scrollTop: 0 }, 300);

    // Deshabilitar controles
    $('#btn-procesar-lote').prop('disabled', true);
    $('.check-item').prop('disabled', true);
    $('.btn-transcribir').prop('disabled', true);

    // Crear formulario para enviar IDs
    var formData = new FormData();
    ids.forEach(function(id) {
        formData.append('ids[]', id);
    });
    formData.append('modelo', $('#modelo-whisper').val());
    formData.append('idioma', $('#idioma').val());
    formData.append('dispositivo', $('#dispositivo').val());
    formData.append('diarizar', $('#diarizar').is(':checked') ? 1 : 0);
    formData.append('hf_token', $('#hf_token').val() || '');
    formData.append('_token', '{{ csrf_token() }}');

    // Usar fetch con POST para enviar los IDs y recibir SSE
    fetch('{{ route("procesamientos.transcripcion-lote") }}', {
        method: 'POST',
        body: formData
    }).then(function(response) {
        if (!response.ok) {
            throw new Error('Error en la solicitud');
        }

        var reader = response.body.getReader();
        var decoder = new TextDecoder();
        var buffer = '';

        function processStream() {
            reader.read().then(function(result) {
                if (result.done) {
                    return;
                }

                buffer += decoder.decode(result.value, { stream: true });

                // Procesar eventos completos
                var lines = buffer.split('\n');
                buffer = lines.pop(); // Guardar linea incompleta

                var currentEvent = '';
                var currentData = '';

                lines.forEach(function(line) {
                    if (line.startsWith('event: ')) {
                        currentEvent = line.substring(7);
                    } else if (line.startsWith('data: ')) {
                        currentData = line.substring(6);
                        if (currentEvent && currentData) {
                            procesarEventoLote(currentEvent, JSON.parse(currentData));
                            currentEvent = '';
                            currentData = '';
                        }
                    }
                });

                processStream();
            }).catch(function(error) {
                console.error('Error en stream:', error);
                $('#lote-status').html('<i class="fas fa-exclamation-circle text-danger mr-2"></i><span>Error de conexion</span>');
                finalizarLote();
            });
        }

        processStream();
    }).catch(function(error) {
        console.error('Error:', error);
        $('#lote-status').html('<i class="fas fa-exclamation-circle text-danger mr-2"></i><span>Error al iniciar procesamiento</span>');
        finalizarLote();
    });
}

function procesarEventoLote(evento, data) {
    switch(evento) {
        case 'inicio':
            $('#lote-mensaje').text(data.mensaje);
            addLogEntry('info', data.mensaje);
            break;

        case 'procesando':
            $('#lote-mensaje').text(data.mensaje);
            // Marcar fila como procesando
            var $row = $('input[value="' + data.id + '"]').closest('tr');
            $row.addClass('table-warning');
            $row.find('.btn-transcribir').html('<i class="fas fa-spinner fa-spin"></i>');
            break;

        case 'exito':
            loteExitosos++;
            $('#lote-exitosos').text(loteExitosos);
            $('#lote-procesados').text(data.procesados);
            actualizarProgreso(data.procesados, data.total);
            $('#lote-mensaje').text(data.mensaje);
            addLogEntry('success', data.mensaje);

            // Marcar fila como exitosa
            var $row = $('input[value="' + data.id + '"]').closest('tr');
            $row.removeClass('table-warning').addClass('table-success');
            $row.find('.btn-transcribir')
                .removeClass('btn-primary').addClass('btn-success')
                .html('<i class="fas fa-check"></i>').prop('disabled', true);
            $row.find('.check-item').prop('checked', false).prop('disabled', true);
            break;

        case 'error':
            loteErrores++;
            $('#lote-errores').text(loteErrores);
            $('#lote-procesados').text(data.procesados);
            actualizarProgreso(data.procesados, data.total);
            $('#lote-mensaje').text(data.mensaje);
            addLogEntry('danger', data.mensaje);

            // Marcar fila como error
            if (data.id) {
                var $row = $('input[value="' + data.id + '"]').closest('tr');
                $row.removeClass('table-warning').addClass('table-danger');
                $row.find('.btn-transcribir')
                    .removeClass('btn-primary').addClass('btn-danger')
                    .html('<i class="fas fa-times"></i>').prop('disabled', false);
            }
            break;

        case 'fin':
            $('#lote-status').html('<i class="fas fa-check-circle text-success mr-2"></i><span>' + data.mensaje + '</span>');
            addLogEntry('info', data.mensaje);
            finalizarLote();
            break;
    }
}

function actualizarProgreso(procesados, total) {
    var porcentaje = Math.round((procesados / total) * 100);
    $('#lote-progress-bar').css('width', porcentaje + '%');
    $('#lote-progress-text').text(porcentaje + '%');
}

function addLogEntry(tipo, mensaje) {
    var iconClass = {
        'info': 'fas fa-info-circle text-info',
        'success': 'fas fa-check-circle text-success',
        'warning': 'fas fa-exclamation-triangle text-warning',
        'danger': 'fas fa-times-circle text-danger'
    };
    var hora = new Date().toLocaleTimeString();
    var html = '<li class="list-group-item py-1 px-2 text-sm">' +
               '<i class="' + (iconClass[tipo] || iconClass['info']) + ' mr-2"></i>' +
               '<small class="text-muted mr-2">' + hora + '</small>' +
               mensaje + '</li>';
    $('#lote-log').append(html);

    // Auto-scroll
    var container = $('#lote-log').parent();
    container.scrollTop(container[0].scrollHeight);
}

function finalizarLote() {
    $('#lote-footer').show();
    $('#btn-procesar-lote').prop('disabled', false);
    $('.check-item:not(:disabled)').prop('disabled', false);
    $('.btn-transcribir:not(.btn-success):not(.btn-danger)').prop('disabled', false);

    // Actualizar contador de seleccionados
    var count = $('.check-item:checked').length;
    $('#count-seleccionadas').text(count);
    $('#btn-procesar-lote').prop('disabled', count === 0);
}

function detectarGPU() {
    $.get('{{ route("procesamientos.servicios-status") }}', function(data) {
        if (data.transcription && !data.transcription.error) {
            var device = data.transcription.device || 'cpu';
            if (device === 'cuda' || device.includes('GPU')) {
                $('#gpu-status').html('<i class="fas fa-check-circle text-success mr-1"></i> GPU CUDA detectada');
                $('#dispositivo').val('cuda');
            } else {
                $('#gpu-status').html('<i class="fas fa-exclamation-circle text-warning mr-1"></i> Solo CPU disponible');
                $('#dispositivo').val('cpu');
            }
        } else {
            $('#gpu-status').html('<i class="fas fa-times-circle text-danger mr-1"></i> Servicio no disponible');
        }
    }).fail(function() {
        $('#gpu-status').html('<i class="fas fa-question-circle text-secondary mr-1"></i> No se pudo detectar');
    });
}
</script>
@endsection
