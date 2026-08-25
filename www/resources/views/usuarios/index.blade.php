@extends('layouts.app')

@section('title', 'Usuarios')
@section('content_header', 'Gestion de Usuarios')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <form action="{{ route('usuarios.index') }}" method="GET" class="form-inline">
                    <div class="input-group mr-2 mb-1">
                        <input type="text" name="buscar" class="form-control" placeholder="Buscar usuario..." value="{{ request('buscar') }}">
                    </div>
                    <div class="input-group mr-2 mb-1">
                        <select name="dependencia" class="form-control">
                            <option value="">Todas las dependencias</option>
                            @foreach($dependencias as $id => $descripcion)
                            <option value="{{ $id }}" {{ (string) request('dependencia') === (string) $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group mb-1">
                        <button type="submit" class="btn btn-default">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('buscar') || request('dependencia'))
                        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary ml-1" title="Limpiar filtros">
                            <i class="fas fa-times"></i>
                        </a>
                        @endif
                    </div>
                </form>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('usuarios.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Nuevo Usuario
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Nivel</th>
                    <th>Dependencia</th>
                    <th width="150">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($usuarios as $usuario)
                <tr>
                    <td>{{ $usuario->id }}</td>
                    <td>{{ $usuario->name }}</td>
                    <td>{{ $usuario->email }}</td>
                    <td>
                        <span class="badge badge-{{ $usuario->id_nivel == 1 ? 'danger' : ($usuario->id_nivel <= 4 ? 'warning' : 'info') }}">
                            {{ $usuario->fmt_privilegios }}
                        </span>
                    </td>
                    <td>{{ $usuario->fmt_dependencia_origen }}</td>
                    <td>
                        <a href="{{ route('usuarios.show', $usuario->id) }}" class="btn btn-sm btn-info" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-warning" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($usuario->id != Auth::id())
                        <form action="{{ route('usuarios.destroy', $usuario->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Esta seguro de eliminar este usuario?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">No se encontraron usuarios</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($usuarios->hasPages())
    <div class="card-footer">
        {{ $usuarios->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
