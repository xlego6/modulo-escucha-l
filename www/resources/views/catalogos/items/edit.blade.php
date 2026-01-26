@extends('layouts.app')

@section('title', 'Editar Item')
@section('content_header', 'Editar Item del Catalogo: ' . $catalogo->nombre)

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Datos del Item</h3>
            </div>
            <form action="{{ route('catalogos.items.update', [$catalogo->id_cat, $item->id_item]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="form-group">
                        <label for="descripcion" class="required-field">Descripcion</label>
                        <input type="text" class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" value="{{ old('descripcion', $item->descripcion) }}" required maxlength="255">
                        @error('descripcion')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="abreviado">Abreviado</label>
                        <input type="text" class="form-control @error('abreviado') is-invalid @enderror" id="abreviado" name="abreviado" value="{{ old('abreviado', $item->abreviado) }}" maxlength="50">
                        @error('abreviado')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="orden" class="required-field">Orden</label>
                        <input type="number" class="form-control @error('orden') is-invalid @enderror" id="orden" name="orden" value="{{ old('orden', $item->orden) }}" required min="0">
                        @error('orden')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="habilitado" name="habilitado" value="1" {{ old('habilitado', $item->habilitado) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="habilitado">Habilitado</label>
                        </div>
                        <small class="form-text text-muted">Los items deshabilitados no aparecen en los selectores</small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="predeterminado" name="predeterminado" value="1" {{ old('predeterminado', $item->predeterminado) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="predeterminado">Valor predeterminado</label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('catalogos.show', $catalogo->id_cat) }}" class="btn btn-default">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save mr-1"></i>Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informacion</h3>
            </div>
            <div class="card-body">
                <dl>
                    <dt>ID del Item</dt>
                    <dd>{{ $item->id_item }}</dd>
                    <dt>Catalogo</dt>
                    <dd>{{ $catalogo->nombre }}</dd>
                    <dt>Estado actual</dt>
                    <dd>
                        @if($item->habilitado)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-danger">Inactivo</span>
                        @endif
                    </dd>
                </dl>

                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Precaucion:</strong> Modificar o deshabilitar items puede afectar registros existentes que usen este valor.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
