@extends('layouts.app')

@section('title', 'Deteccion de Entidades')
@section('content_header', 'Deteccion de Entidades')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="callout callout-warning">
            <h5><i class="fas fa-brain mr-2"></i>spaCy NER - Reconocimiento de Entidades</h5>
            <p class="mb-0">
                Sistema de deteccion de entidades nombradas (NER) basado en spaCy con modelo en español.
                Identifica personas, lugares, organizaciones, fechas, eventos y otros elementos relevantes.
            </p>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tipos de entidades a detectar -->
    <div class="col-md-12 mb-3">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tags mr-2"></i>Tipos de Entidades a Detectar</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" onclick="seleccionarTodos()" title="Seleccionar todos">
                        <i class="fas fa-check-double"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Seleccione los tipos de entidades que desea detectar. MISC puede generar muchos falsos positivos.</p>
                <div class="row">
                    @php
                        $badgeClasses = [
                            'PER' => 'primary',
                            'LOC' => 'success',
                            'ORG' => 'info',
                            'DATE' => 'secondary',
                            'EVENT' => 'warning',
                            'GUN' => 'danger',
                            'MISC' => 'dark'
                        ];
                        $defaultSelected = ['PER', 'LOC', 'ORG', 'DATE'];
                    @endphp
                    @foreach($tiposEntidades as $tipo => $nombre)
                    <div class="col-md-3 col-sm-4 col-6 mb-2">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input tipo-detectar"
                                   id="tipo-{{ $tipo }}" value="{{ $tipo }}"
                                   {{ in_array($tipo, $defaultSelected) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="tipo-{{ $tipo }}">
                                <span class="badge badge-{{ $badgeClasses[$tipo] ?? 'dark' }} p-2">{{ $tipo }}</span>
                                <span class="ml-1">{{ $nombre }}</span>
                            </label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>Entrevistas Pendientes de Analisis</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Codigo</th>
                            <th>Titulo</th>
                            <th>Archivos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendientes as $entrevista)
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
                                <span class="badge badge-info">
                                    {{ $entrevista->rel_adjuntos->count() }} audios
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-detectar"
                                        data-id="{{ $entrevista->id_e_ind_fvt }}"
                                        title="Detectar entidades">
                                    <i class="fas fa-search"></i> Detectar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x mb-2"></i><br>
                                No hay entrevistas pendientes de analisis
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pendientes->hasPages())
            <div class="card-footer">
                {{ $pendientes->links() }}
            </div>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <!-- Acciones en lote -->
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Procesamiento en Lote</h3>
            </div>
            <div class="card-body">
                <p class="text-muted">Seleccione entrevistas para detectar entidades en lote.</p>
                <div class="form-group">
                    <label>Seleccionadas:</label>
                    <span id="count-seleccionadas" class="badge badge-warning">0</span>
                </div>
                <button class="btn btn-warning btn-block" id="btn-procesar-lote" disabled>
                    <i class="fas fa-search mr-2"></i>Detectar Entidades
                </button>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Contador de seleccionados
    $('.check-item').on('change', function() {
        var count = $('.check-item:checked').length;
        $('#count-seleccionadas').text(count);
        $('#btn-procesar-lote').prop('disabled', count === 0);
    });

    // Detectar individual
    $('.btn-detectar').on('click', function() {
        var id = $(this).data('id');
        var btn = $(this);

        var tiposSeleccionados = [];
        $('.tipo-detectar:checked').each(function() {
            tiposSeleccionados.push($(this).val());
        });

        if (tiposSeleccionados.length === 0) {
            alert('Debe seleccionar al menos un tipo de entidad a detectar');
            return;
        }

        if (!confirm('¿Iniciar deteccion de entidades?\nTipos: ' + tiposSeleccionados.join(', '))) return;

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: '{{ url("procesamientos/entidades") }}/' + id + '/detectar',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                tipos: tiposSeleccionados.join(',')
            },
            success: function(response) {
                alert('Deteccion iniciada correctamente');
                location.reload();
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON?.error || 'Error desconocido'));
                btn.prop('disabled', false).html('<i class="fas fa-search"></i> Detectar');
            }
        });
    });

    // Procesar en lote
    $('#btn-procesar-lote').on('click', function() {
        var ids = [];
        $('.check-item:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) return;

        if (!confirm('¿Detectar entidades en ' + ids.length + ' entrevista(s)?')) return;

        alert('Funcionalidad de procesamiento en lote pendiente de implementacion');
    });
});

function seleccionarTodos() {
    var todos = $('.tipo-detectar');
    var todosMarcados = todos.filter(':checked').length === todos.length;
    todos.prop('checked', !todosMarcados);
}

</script>
@endsection
