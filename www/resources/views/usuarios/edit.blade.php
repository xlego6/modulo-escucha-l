@extends('layouts.app')

@section('title', 'Editar Usuario')
@section('content_header', 'Editar Usuario')

@section('content')
<div class="card">
    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST">
        @csrf
        @method('PUT')
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
                    <h5 class="mb-3">Datos de Cuenta</h5>

                    <div class="form-group">
                        <label for="name">Nombre Completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electronico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Nueva Contrasena</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        <small class="form-text text-muted">Dejar en blanco para mantener la contrasena actual. Minimo 6 caracteres.</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirmar Contrasena</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="mb-3">Perfil y Permisos</h5>

                    <div class="form-group">
                        <label for="id_nivel">Nivel de Acceso <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_nivel') is-invalid @enderror" id="id_nivel" name="id_nivel" required>
                            @foreach($niveles as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_nivel', $perfil->id_nivel ?? '') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_dependencia_origen">Dependencia de Origen</label>
                        <select class="form-control" id="id_dependencia_origen" name="id_dependencia_origen">
                            @foreach($dependencias as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_dependencia_origen', $perfil->id_dependencia_origen ?? '') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="solo_lectura" name="solo_lectura" value="1" {{ old('solo_lectura', $perfil->solo_lectura ?? 0) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="solo_lectura">Solo Lectura</label>
                        </div>
                        <small class="form-text text-muted">El usuario solo podra ver informacion, no crear ni editar.</small>
                    </div>

                    @if($perfil)
                    <div class="form-group">
                        <label>Numero de Entrevistador</label>
                        <input type="text" class="form-control" value="{{ $perfil->fmt_numero_entrevistador }}" disabled>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Actualizar
            </button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
