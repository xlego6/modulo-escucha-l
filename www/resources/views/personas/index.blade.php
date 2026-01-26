@extends('layouts.app')

@section('title', 'Personas')
@section('content_header', 'Listado de Personas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros de busqueda</h3>
        <div class="card-tools">
            <a href="{{ route('personas.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nueva Persona
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('personas.index') }}" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Nombre / Apellido</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" value="{{ request('nombre') }}" placeholder="Buscar por nombre...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Documento</label>
                    <input type="text" name="documento" class="form-control form-control-sm" value="{{ request('documento') }}" placeholder="Numero de documento">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Sexo</label>
                    <select name="id_sexo" class="form-control form-control-sm">
                        @foreach($sexos as $id => $nombre)
                            <option value="{{ $id }}" {{ request('id_sexo') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Grupo Etnico</label>
                    <select name="id_etnia" class="form-control form-control-sm">
                        @foreach($etnias as $id => $nombre)
                            <option value="{{ $id }}" {{ request('id_etnia') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-1">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-info btn-sm btn-block">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Personas ({{ $personas->total() }} registros)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th style="width: 120px">Documento</th>
                    <th style="width: 100px">Sexo</th>
                    <th style="width: 150px">Grupo Etnico</th>
                    <th style="width: 120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($personas as $persona)
                <tr>
                    <td>
                        <a href="{{ route('personas.show', $persona->id_persona) }}">
                            <strong>{{ $persona->fmt_nombre_completo }}</strong>
                        </a>
                        @if($persona->alias)
                            <br><small class="text-muted">Alias: {{ $persona->alias }}</small>
                        @endif
                    </td>
                    <td>
                        @if($persona->num_documento)
                            <small class="text-muted">{{ $persona->rel_tipo_documento->descripcion ?? 'DOC' }}:</small><br>
                            {{ $persona->num_documento }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>{{ $persona->fmt_sexo }}</td>
                    <td>
                        @if($persona->rel_etnia)
                            {{ $persona->rel_etnia->descripcion }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('personas.show', $persona->id_persona) }}" class="btn btn-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('personas.edit', $persona->id_persona) }}" class="btn btn-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('personas.destroy', $persona->id_persona) }}" method="POST" style="display:inline" onsubmit="return confirm('Esta seguro de eliminar esta persona?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p>No se encontraron personas</p>
                        <a href="{{ route('personas.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Registrar primera persona
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($personas->hasPages())
    <div class="card-footer">
        {{ $personas->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
