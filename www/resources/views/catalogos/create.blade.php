@extends('layouts.app')

@section('title', 'Nuevo catálogo')
@section('content_header', 'Crear nuevo catálogo')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Datos del catálogo</h3>
            </div>
            <form action="{{ route('catalogos.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="nombre" class="required-field">Nombre</label>
                        <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100">
                        @error('nombre')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Identificador único del catálogo (ej: sexo, etnia, dependencias)</small>
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="3" maxlength="255">{{ old('descripcion') }}</textarea>
                        @error('descripcion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Descripción del propósito del catálogo</small>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('catalogos.index') }}" class="btn btn-default">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
