@extends('layouts.app')

@section('title', 'Nuevo Rol')
@section('content_header', 'Crear Rol Personalizado')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Datos del Rol</h3>
            </div>
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="card-body">

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Tras crear el rol, podra configurar sus permisos modulo a modulo.
                        El rol se asignara a usuarios desde la gestion de <a href="{{ route('usuarios.index') }}">Usuarios</a>.
                    </div>

                    <div class="form-group">
                        <label for="nombre">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre"
                               class="form-control @error('nombre') is-invalid @enderror"
                               value="{{ old('nombre') }}" required maxlength="100"
                               placeholder="Ej: Supervisor Regional">
                        @error('nombre')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripcion</label>
                        <textarea name="descripcion" id="descripcion"
                                  class="form-control @error('descripcion') is-invalid @enderror"
                                  rows="3" placeholder="Descripcion opcional del rol...">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Crear y Configurar Permisos
                    </button>
                    <a href="{{ route('roles.index') }}" class="btn btn-default ml-2">
                        <i class="fas fa-arrow-left mr-1"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
