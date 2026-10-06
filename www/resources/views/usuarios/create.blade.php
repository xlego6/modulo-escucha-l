@extends('layouts.app')

@section('title', 'Nuevo usuario')
@section('content_header', 'Crear usuario')

@section('content')
<div class="card">
    <form action="{{ route('usuarios.store') }}" method="POST">
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
                    <h5 class="seccion-titulo"><i class="fas fa-user"></i> Datos de cuenta</h5>

                    <div class="form-group">
                        <label for="name">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Correo electrónico <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        <small class="form-text text-muted">Mínimo 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirmar contraseña <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <div class="col-md-6">
                    <h5 class="seccion-titulo"><i class="fas fa-shield-alt"></i> Perfil y permisos</h5>

                    <div class="form-group">
                        <label for="id_nivel">Nivel de acceso <span class="text-danger">*</span></label>
                        <select class="form-control @error('id_nivel') is-invalid @enderror" id="id_nivel" name="id_nivel" required>
                            @foreach($niveles as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_nivel') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_dependencia_origen">Dependencia de origen</label>
                        <select class="form-control" id="id_dependencia_origen" name="id_dependencia_origen">
                            @foreach($dependencias as $id => $descripcion)
                            <option value="{{ $id }}" {{ old('id_dependencia_origen') == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="solo_lectura" name="solo_lectura" value="1" {{ old('solo_lectura') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="solo_lectura">Solo lectura</label>
                        </div>
                        <small class="form-text text-muted">El usuario solo podrá ver información, no crear ni editar.</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Guardar
            </button>
            <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
                <i class="fas fa-times mr-1"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
