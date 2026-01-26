@extends('layouts.app')

@section('title', 'Editar Persona')
@section('content_header', 'Editar Testimoniante')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />
<style>
    .section-title {
        border-bottom: 2px solid #ffc107;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }
    .required-field::after {
        content: ' *';
        color: #dc3545;
    }
    .select2-container--bootstrap4 .select2-selection {
        min-height: calc(1.5em + 0.75rem + 2px);
    }
</style>
@endsection

@section('content')
<form action="{{ route('personas.update', $persona->id_persona) }}" method="POST" id="form-persona">
    @csrf
    @method('PUT')

    <div class="row">
        <div class="col-md-8">
            <!-- Card Principal -->
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user-edit"></i>
                        Editando: {{ $persona->fmt_nombre_completo }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('personas.show', $persona->id_persona) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Por favor corrija los siguientes errores:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="row">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <!-- Identificacion -->
                            <h5 class="section-title"><i class="fas fa-id-card text-warning"></i> Identificacion</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="nombre" class="required-field">Nombre(s)</label>
                                        <input type="text" name="nombre" id="nombre"
                                            class="form-control @error('nombre') is-invalid @enderror"
                                            value="{{ old('nombre', $persona->nombre) }}" required maxlength="200">
                                        @error('nombre')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="apellido" class="required-field">Apellido(s)</label>
                                        <input type="text" name="apellido" id="apellido"
                                            class="form-control @error('apellido') is-invalid @enderror"
                                            value="{{ old('apellido', $persona->apellido) }}" required maxlength="200">
                                        @error('apellido')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="nombre_identitario">Nombre Identitario</label>
                                <input type="text" name="nombre_identitario" id="nombre_identitario" class="form-control"
                                    value="{{ old('nombre_identitario', $persona->nombre_identitario) }}" maxlength="200">
                                <small class="form-text text-muted">Nombre con el que la persona elige ser reconocida</small>
                            </div>

                            <!-- Lugar de Origen -->
                            <h5 class="section-title mt-4"><i class="fas fa-map-marker-alt text-info"></i> Lugar de Origen</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_nacimiento_depto">Departamento</label>
                                        <select name="id_lugar_nacimiento_depto" id="id_lugar_nacimiento_depto" class="form-control">
                                            @foreach($departamentos as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_lugar_nacimiento_depto', $persona->id_lugar_nacimiento_depto) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_lugar_nacimiento">Municipio</label>
                                        <select name="id_lugar_nacimiento" id="id_lugar_nacimiento" class="form-control">
                                            <option value="">-- Seleccione Departamento primero --</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Poblacion -->
                            <h5 class="section-title mt-4"><i class="fas fa-users text-success"></i> Poblacion</h5>

                            <div class="form-group">
                                <label for="poblaciones">Poblacion</label>
                                <select name="poblaciones[]" id="poblaciones" class="form-control select2-multiple" multiple>
                                    @foreach($poblaciones as $id => $nombre)
                                    <option value="{{ $id }}" {{ in_array($id, $persona->rel_poblaciones->pluck('id_item')->toArray()) ? 'selected' : '' }}>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Puede seleccionar varias opciones</small>
                            </div>

                            <!-- Ocupacion -->
                            <h5 class="section-title mt-4"><i class="fas fa-briefcase text-warning"></i> Ocupacion</h5>

                            <div class="form-group">
                                <label for="ocupaciones">Ocupacion</label>
                                <select name="ocupaciones[]" id="ocupaciones" class="form-control select2-multiple" multiple>
                                    @foreach($ocupaciones as $id => $nombre)
                                    <option value="{{ $id }}" {{ in_array($id, $persona->rel_ocupaciones->pluck('id_item')->toArray()) ? 'selected' : '' }}>{{ $nombre }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Puede seleccionar varias opciones</small>
                            </div>
                        </div>

                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <!-- Caracterizacion -->
                            <h5 class="section-title"><i class="fas fa-venus-mars text-danger"></i> Caracterizacion</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_sexo">Sexo</label>
                                        <select name="id_sexo" id="id_sexo" class="form-control">
                                            @foreach($sexos as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_sexo', $persona->id_sexo) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_identidad">Identidad de Genero</label>
                                        <select name="id_identidad" id="id_identidad" class="form-control">
                                            @foreach($identidades_genero as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_identidad', $persona->id_identidad) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_orientacion">Orientacion Sexual</label>
                                        <select name="id_orientacion" id="id_orientacion" class="form-control">
                                            @foreach($orientaciones_sexuales as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_orientacion', $persona->id_orientacion) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_etnia">Grupo Etnico</label>
                                        <select name="id_etnia" id="id_etnia" class="form-control">
                                            @foreach($etnias as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_etnia', $persona->id_etnia) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_rango_etario">Rango Etario</label>
                                        <select name="id_rango_etario" id="id_rango_etario" class="form-control">
                                            @foreach($rangos_etarios as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_rango_etario', $persona->id_rango_etario) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="id_discapacidad">Discapacidad</label>
                                        <select name="id_discapacidad" id="id_discapacidad" class="form-control">
                                            @foreach($discapacidades as $id => $nombre)
                                            <option value="{{ $id }}" {{ old('id_discapacidad', $persona->id_discapacidad) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos Adicionales (colapsable) -->
                            <div class="card card-secondary card-outline mt-4">
                                <div class="card-header p-2">
                                    <h6 class="mb-0">
                                        <a data-toggle="collapse" href="#datosAdicionales" class="text-secondary">
                                            <i class="fas fa-address-card"></i> Datos Adicionales (opcional)
                                            <i class="fas fa-chevron-down float-right"></i>
                                        </a>
                                    </h6>
                                </div>
                                <div id="datosAdicionales" class="collapse {{ ($persona->num_documento || $persona->telefono || $persona->correo_electronico) ? 'show' : '' }}">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="id_tipo_documento">Tipo Documento</label>
                                                    <select name="id_tipo_documento" id="id_tipo_documento" class="form-control">
                                                        @foreach($tipos_documento as $id => $nombre)
                                                        <option value="{{ $id }}" {{ old('id_tipo_documento', $persona->id_tipo_documento) == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="num_documento">Numero Documento</label>
                                                    <input type="text" name="num_documento" id="num_documento" class="form-control"
                                                        value="{{ old('num_documento', $persona->num_documento) }}" maxlength="50">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="telefono">Telefono</label>
                                                    <input type="text" name="telefono" id="telefono" class="form-control"
                                                        value="{{ old('telefono', $persona->telefono) }}" maxlength="50">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="correo_electronico">Correo Electronico</label>
                                                    <input type="email" name="correo_electronico" id="correo_electronico" class="form-control"
                                                        value="{{ old('correo_electronico', $persona->correo_electronico) }}" maxlength="100">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                    <a href="{{ route('personas.show', $persona->id_persona) }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Info del Registro -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-info-circle"></i> Informacion del Registro</h3>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">ID:</td>
                            <td><code>{{ $persona->id_persona }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Creado:</td>
                            <td>{{ $persona->created_at ? \Carbon\Carbon::parse($persona->created_at)->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Actualizado:</td>
                            <td>{{ $persona->updated_at ? \Carbon\Carbon::parse($persona->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-question-circle"></i> Ayuda</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-2">
                        <i class="fas fa-asterisk text-danger"></i> Los campos marcados con asterisco son obligatorios.
                    </p>
                    <p class="text-muted mb-2">
                        <i class="fas fa-lightbulb text-warning"></i> Los municipios se cargan automaticamente al seleccionar un departamento.
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-info-circle text-info"></i> Los campos de Poblacion y Ocupacion permiten seleccion multiple.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Inicializar Select2 para campos multiple
    $('.select2-multiple').select2({
        theme: 'bootstrap4',
        placeholder: '-- Seleccione --',
        allowClear: true
    });

    // Cargar municipios al cambiar departamento
    $('#id_lugar_nacimiento_depto').on('change', function() {
        let deptoId = $(this).val();
        let muniSelect = $('#id_lugar_nacimiento');

        if (!deptoId) {
            muniSelect.html('<option value="">-- Seleccione Departamento primero --</option>');
            return;
        }

        $.get('{{ route("api.municipios") }}', { id_departamento: deptoId }, function(data) {
            muniSelect.empty().append('<option value="">-- Seleccione --</option>');
            $.each(data, function(id, nombre) {
                muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
            });
        });
    });

    // Cargar municipios si ya hay departamento seleccionado (para edicion)
    @if($persona->id_lugar_nacimiento_depto)
    $.get('{{ route("api.municipios") }}', { id_departamento: '{{ $persona->id_lugar_nacimiento_depto }}' }, function(data) {
        let muniSelect = $('#id_lugar_nacimiento');
        muniSelect.empty().append('<option value="">-- Seleccione --</option>');
        $.each(data, function(id, nombre) {
            muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
        });
        @if($persona->id_lugar_nacimiento)
        muniSelect.val('{{ $persona->id_lugar_nacimiento }}');
        @endif
    });
    @endif
});
</script>
@endsection
