@extends('layouts.app')

@section('title', 'Desclasificacion')
@section('content_header', 'Desclasificacion de Entrevistas')

@section('content')
<div class="row">
    <div class="col-lg-7">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-unlock-alt mr-2"></i>Autorizar Acceso por Desclasificacion
                </h3>
            </div>
            <form action="{{ route('permisos.store_desclasificacion') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="form-group">
                        <label for="id_entrevistador">Usuario Autorizado <span class="text-danger">*</span></label>
                        <select class="form-control select2 @error('id_entrevistador') is-invalid @enderror" id="id_entrevistador" name="id_entrevistador" required>
                            @foreach($entrevistadores as $id => $nombre)
                            <option value="{{ $id }}" {{ (old('id_entrevistador') == $id || $id_autorizado_preselect == $id) ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Seleccione el usuario que recibira acceso a las entrevistas</small>
                    </div>

                    <div class="form-group">
                        <label for="codigos_entrevista">Codigos de Entrevista <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('codigos_entrevista') is-invalid @enderror" id="codigos_entrevista" name="codigos_entrevista" rows="3" placeholder="Ej: VI-001, VI-002, VI-003" required>{{ old('codigos_entrevista') }}</textarea>
                        <small class="form-text text-muted">
                            Ingrese uno o mas codigos de entrevista separados por coma, espacio o salto de linea.
                            <br>Ejemplo: <code>VI-001, VI-002</code> o <code>VI-001 VI-002</code>
                        </small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_desde">Fecha Desde <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_desde') is-invalid @enderror" id="fecha_desde" name="fecha_desde" value="{{ old('fecha_desde', date('Y-m-d')) }}" required>
                                <small class="form-text text-muted">Inicio del periodo de acceso</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_hasta">Fecha Hasta <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('fecha_hasta') is-invalid @enderror" id="fecha_hasta" name="fecha_hasta" value="{{ old('fecha_hasta') }}" required>
                                <small class="form-text text-muted">Fin del periodo de acceso</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="justificacion">Justificacion <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('justificacion') is-invalid @enderror" id="justificacion" name="justificacion" rows="3" required>{{ old('justificacion') }}</textarea>
                        <small class="form-text text-muted">Describa el motivo de la desclasificacion</small>
                    </div>

                    <div class="form-group">
                        <label for="archivo_soporte">Documento de Soporte (PDF) <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input @error('archivo_soporte') is-invalid @enderror" id="archivo_soporte" name="archivo_soporte" accept=".pdf" required>
                            <label class="custom-file-label" for="archivo_soporte">Seleccionar archivo PDF...</label>
                        </div>
                        <small class="form-text text-muted">
                            Adjunte el documento que autoriza la desclasificacion (maximo 10MB)
                        </small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-unlock mr-1"></i> Autorizar Acceso
                    </button>
                    <a href="{{ route('permisos.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times mr-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>Permisos Otorgados Hoy
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light">{{ $historialHoy->count() }}</span>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                @if($historialHoy->count() > 0)
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Entrevista</th>
                            <th>Rango</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($historialHoy as $permiso)
                        <tr>
                            <td>
                                <small>{{ $permiso->rel_entrevistador->rel_usuario->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                <small>
                                    <a href="{{ route('entrevistas.show', $permiso->id_e_ind_fvt) }}">
                                        {{ $permiso->codigo_entrevista ?? $permiso->rel_entrevista->entrevista_codigo ?? 'N/A' }}
                                    </a>
                                </small>
                            </td>
                            <td>
                                <small>{{ $permiso->fmt_rango_fechas }}</small>
                            </td>
                            <td>
                                <form action="{{ route('permisos.destroy', $permiso->id_permiso) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Revocar este permiso?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Revocar">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No ha otorgado permisos hoy</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>Informacion
                </h3>
            </div>
            <div class="card-body">
                <p class="text-sm">
                    <strong>Desclasificacion</strong> permite otorgar acceso temporal a entrevistas clasificadas.
                </p>
                <ul class="text-sm pl-3">
                    <li>El acceso es valido solo durante el rango de fechas especificado</li>
                    <li>Se requiere un documento PDF de soporte que justifique la autorizacion</li>
                    <li>Puede autorizar multiples entrevistas en una sola operacion</li>
                    <li>Los accesos quedan registrados en la traza de actividad</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function() {
    // Actualizar label del input file
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName);
    });

    // Inicializar select2 si está disponible
    if ($.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });
    }
});
</script>
@endsection
