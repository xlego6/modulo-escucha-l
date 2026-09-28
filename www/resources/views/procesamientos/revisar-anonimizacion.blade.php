@extends('layouts.app')

@section('title', 'Revisar Anonimizacion')
@section('content_header')
Revisar Anonimizacion: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
@include('procesamientos.partials.anonimizacion-editor-css')
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        @include('procesamientos.partials.anonimizacion-editor', ['tituloEditor' => 'Anonimizacion (Editable)', 'iconoEditor' => 'fa-edit'])
    </div>
</div>

{{-- Tarjetas de información y decisión (parte inferior) --}}
<div class="row">
    <div class="col-md-3">
        {{-- Informacion de la asignacion --}}
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Revision Pendiente</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Anonimizador:</dt>
                    <dd class="col-sm-7">
                        <i class="fas fa-user mr-1"></i>
                        {{ $asignacion->rel_anonimizador->rel_usuario->name ?? 'N/A' }}
                    </dd>

                    <dt class="col-sm-5">Asignada por:</dt>
                    <dd class="col-sm-7">{{ $asignacion->rel_asignado_por->name ?? 'N/A' }}</dd>

                    <dt class="col-sm-5">Fecha Asignacion:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</dd>

                    <dt class="col-sm-5">Fecha Envio:</dt>
                    <dd class="col-sm-7">
                        @if($asignacion->fecha_envio_revision)
                            {{ $asignacion->fecha_envio_revision->format('d/m/Y H:i') }}
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        {{-- Informacion de la entrevista --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>Datos de la Entrevista</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Codigo:</dt>
                    <dd class="col-sm-8"><code>{{ $entrevista->entrevista_codigo }}</code></dd>

                    <dt class="col-sm-4">Titulo:</dt>
                    <dd class="col-sm-8">{{ $entrevista->titulo }}</dd>

                    <dt class="col-sm-4">Fecha:</dt>
                    <dd class="col-sm-8">{{ $entrevista->entrevista_fecha ? \Carbon\Carbon::parse($entrevista->entrevista_fecha)->format('d/m/Y') : '-' }}</dd>
                </dl>
                <hr>
                <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-sm btn-outline-info" target="_blank">
                    <i class="fas fa-external-link-alt mr-1"></i> Ver entrevista completa
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        @include('procesamientos.partials.anonimizacion-etiquetas')
    </div>

    <div class="col-md-3">
        {{-- Botones de accion --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gavel mr-2"></i>Decision</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('procesamientos.aprobar-anonimizacion', $asignacion->id_asignacion) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="form-group">
                        <label>Comentario (opcional)</label>
                        <textarea name="comentario" class="form-control" rows="2" placeholder="Comentario de aprobacion..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" onclick="return confirm('¿Aprobar esta anonimizacion como version final?')">
                        <i class="fas fa-check mr-1"></i> Aprobar Anonimizacion
                    </button>
                </form>

                <hr>

                <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#modalRechazar">
                    <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                </button>

                <hr>

                <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal Rechazar --}}
<div class="modal fade" id="modalRechazar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title"><i class="fas fa-times mr-2"></i>Rechazar Anonimizacion</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formRechazar" action="{{ route('procesamientos.rechazar-anonimizacion', $asignacion->id_asignacion) }}" method="POST">
                @csrf
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                    @endif
                    <p class="text-muted">
                        Indique el motivo del rechazo. El anonimizador recibira este comentario
                        y podra corregir la anonimizacion.
                    </p>
                    <div class="form-group">
                        <label>Motivo del rechazo <span class="text-danger">*</span></label>
                        <textarea name="comentario" id="comentarioRechazo" class="form-control" rows="4" required
                                  minlength="10"
                                  placeholder="Ej: Algunas entidades no fueron anonimizadas correctamente...">{{ old('comentario') }}</textarea>
                        <small class="text-muted">Minimo 10 caracteres</small>
                    </div>
                    <div id="errorRechazo" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="btnRechazar">
                        <i class="fas fa-times mr-1"></i> Rechazar y Devolver
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
@include('procesamientos.partials.anonimizacion-editor-js')
<script>
// Manejar envio del formulario de rechazo
$('#formRechazar').on('submit', function(e) {
    var comentario = $('#comentarioRechazo').val().trim();

    if (comentario.length < 10) {
        e.preventDefault();
        $('#errorRechazo').removeClass('d-none').text('El comentario debe tener al menos 10 caracteres');
        return false;
    }

    $('#btnRechazar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Procesando...');
    return true;
});

// Abrir modal si hay errores de validacion
@if($errors->any())
$('#modalRechazar').modal('show');
@endif
</script>
@endsection
