@extends('layouts.app')

@section('title', 'Otorgar Permiso')
@section('content_header', 'Otorgar Permiso de Acceso')

@section('content')
<div class="card">
    <form action="{{ route('permisos.store') }}" method="POST">
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

            <div class="row">
                <div class="col-md-6">

                    {{-- Búsqueda de usuario --}}
                    <div class="form-group">
                        <label>Usuario <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_entrevistador') is-invalid @enderror"
                                id="sel_entrevistador" name="id_entrevistador" required>
                            {{-- Si hay valor previo (error de validación), se recargará con JS --}}
                        </select>
                        @error('id_entrevistador')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Escriba el nombre para buscar</small>
                    </div>

                    {{-- Búsqueda de entrevista --}}
                    <div class="form-group">
                        <label>Entrevista <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_e_ind_fvt') is-invalid @enderror"
                                id="sel_entrevista" name="id_e_ind_fvt">
                            @if($entrevistaPreselect)
                            <option value="{{ $entrevistaPreselect->id_e_ind_fvt }}" selected>
                                {{ $entrevistaPreselect->entrevista_codigo }} — {{ $entrevistaPreselect->titulo }}
                            </option>
                            @endif
                        </select>
                        @error('id_e_ind_fvt')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Busque por código o título. O use el campo de abajo para pegar múltiples códigos.
                        </small>
                    </div>

                    {{-- Códigos múltiples (alternativa al buscador) --}}
                    <div class="form-group">
                        <label for="codigos_entrevista">O pegue varios códigos</label>
                        <textarea class="form-control @error('codigos_entrevista') is-invalid @enderror"
                                  id="codigos_entrevista" name="codigos_entrevista"
                                  rows="3"
                                  placeholder="Ej: COD001, COD002&#10;COD003">{{ old('codigos_entrevista', $codigoPreselect) }}</textarea>
                        @error('codigos_entrevista')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">
                            Separe los códigos por coma, espacio o salto de línea. Si usa este campo, el buscador de arriba se ignora.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="id_tipo">Tipo de Permiso <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_tipo') is-invalid @enderror" id="id_tipo" name="id_tipo" required>
                            @foreach($tipos as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_tipo') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            <strong>Lectura:</strong> Solo ver &nbsp;|&nbsp;
                            <strong>Escritura:</strong> Ver y editar &nbsp;|&nbsp;
                            <strong>Completo:</strong> Ver, editar y eliminar
                        </small>
                    </div>

                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                        <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento"
                               value="{{ old('fecha_vencimiento') }}">
                        <small class="form-text text-muted">Dejar en blanco para permiso sin fecha de expiración</small>
                    </div>

                    <div class="form-group">
                        <label for="justificacion">Justificación <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('justificacion') is-invalid @enderror"
                                  id="justificacion" name="justificacion" rows="4" required>{{ old('justificacion') }}</textarea>
                        @error('justificacion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Explique brevemente el motivo por el cual se otorga este permiso</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check mr-1"></i> Otorgar Permiso
            </button>
            <a href="{{ route('permisos.index') }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    // Select2 AJAX para usuarios (entrevistadores)
    $('#sel_entrevistador').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Escriba el nombre para buscar...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("permisos.buscar_entrevistadores") }}',
            dataType: 'json',
            delay: 300,
            data: function (p) { return { q: p.term }; },
            processResults: function (data) { return { results: data }; },
            cache: true
        }
    });

    // Select2 AJAX para entrevista individual
    $('#sel_entrevista').select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: 'Escriba código o título para buscar...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("permisos.buscar_entrevistas") }}',
            dataType: 'json',
            delay: 300,
            data: function (p) { return { q: p.term }; },
            processResults: function (data) { return { results: data }; },
            cache: true
        }
    });

    // Si hay un campo de códigos múltiples relleno, limpiar el buscador (y viceversa)
    $('#codigos_entrevista').on('input', function () {
        if ($(this).val().trim() !== '') {
            $('#sel_entrevista').val(null).trigger('change');
        }
    });
    $('#sel_entrevista').on('change', function () {
        if ($(this).val()) {
            $('#codigos_entrevista').val('');
        }
    });
});
</script>
@endsection
