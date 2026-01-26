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
                    <div class="form-group">
                        <label for="id_entrevistador">Usuario <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_entrevistador') is-invalid @enderror" id="id_entrevistador" name="id_entrevistador" required>
                            @foreach($entrevistadores as $id => $nombre)
                            <option value="{{ $id }}" {{ old('id_entrevistador') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Seleccione el usuario que recibira el permiso</small>
                    </div>

                    <div class="form-group">
                        <label for="id_e_ind_fvt">Entrevista <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_e_ind_fvt') is-invalid @enderror" id="id_e_ind_fvt" name="id_e_ind_fvt" required>
                            @foreach($entrevistas as $id => $descripcion)
                            <option value="{{ $id }}" {{ (old('id_e_ind_fvt') == $id || $id_entrevista_preselect == $id) ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Seleccione la entrevista a la que se dara acceso</small>
                    </div>

                    <div class="form-group">
                        <label for="id_tipo">Tipo de Permiso <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_tipo') is-invalid @enderror" id="id_tipo" name="id_tipo" required>
                            @foreach($tipos as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_tipo') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            <strong>Lectura:</strong> Solo ver |
                            <strong>Escritura:</strong> Ver y editar |
                            <strong>Completo:</strong> Ver, editar y eliminar
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="fecha_vencimiento">Fecha de Vencimiento</label>
                        <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}">
                        <small class="form-text text-muted">Dejar en blanco para permiso sin fecha de expiracion</small>
                    </div>

                    <div class="form-group">
                        <label for="justificacion">Justificacion <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('justificacion') is-invalid @enderror" id="justificacion" name="justificacion" rows="4" required>{{ old('justificacion') }}</textarea>
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
