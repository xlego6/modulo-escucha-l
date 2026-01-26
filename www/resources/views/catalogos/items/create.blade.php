@extends('layouts.app')

@section('title', 'Nuevo Item')
@section('content_header', 'Agregar Item al Catalogo: ' . $catalogo->nombre)

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus mr-2"></i>Datos del Item</h3>
            </div>
            <form action="{{ route('catalogos.items.store', $catalogo->id_cat) }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="descripcion" class="required-field">Descripcion</label>
                        <input type="text" class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" value="{{ old('descripcion') }}" required maxlength="255">
                        @error('descripcion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Texto que se mostrara en los selectores</small>
                    </div>

                    <div class="form-group">
                        <label for="abreviado">Abreviado</label>
                        <input type="text" class="form-control @error('abreviado') is-invalid @enderror" id="abreviado" name="abreviado" value="{{ old('abreviado') }}" maxlength="50">
                        @error('abreviado')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Codigo corto opcional (ej: AB, VS, DF)</small>
                    </div>

                    <div class="form-group">
                        <label for="orden" class="required-field">Orden</label>
                        <input type="number" class="form-control @error('orden') is-invalid @enderror" id="orden" name="orden" value="{{ old('orden', $maxOrden + 1) }}" required min="0">
                        @error('orden')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                        <small class="form-text text-muted">Posicion en la lista (menor = primero)</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="predeterminado" name="predeterminado" value="1" {{ old('predeterminado') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="predeterminado">Valor predeterminado</label>
                        </div>
                        <small class="form-text text-muted">Si se marca, este valor se seleccionara automaticamente</small>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('catalogos.show', $catalogo->id_cat) }}" class="btn btn-default">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Catalogo</h3>
            </div>
            <div class="card-body">
                <dl>
                    <dt>Nombre</dt>
                    <dd>{{ $catalogo->nombre }}</dd>
                    <dt>Descripcion</dt>
                    <dd>{{ $catalogo->descripcion ?? 'Sin descripcion' }}</dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
