@extends('layouts.app')

@section('title', 'Roles')
@section('content_header', 'Gestion de Roles')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-8">
                <p class="mb-0 text-muted">Administre los roles del sistema y sus permisos por modulo.</p>
            </div>
            <div class="col-md-4 text-right">
                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus mr-1"></i> Nuevo Rol
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th width="60">Nivel</th>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th width="120">Tipo</th>
                    <th width="80">Estado</th>
                    <th width="130">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $rol)
                <tr>
                    <td>{{ $rol->id_nivel }}</td>
                    <td><strong>{{ $rol->nombre }}</strong></td>
                    <td class="text-muted">{{ $rol->descripcion ?? '—' }}</td>
                    <td>
                        @if($rol->es_sistema)
                            <span class="badge badge-secondary">Sistema</span>
                        @else
                            <span class="badge badge-info">Personalizado</span>
                        @endif
                    </td>
                    <td>
                        @if($rol->habilitado)
                            <span class="badge badge-success">Activo</span>
                        @else
                            <span class="badge badge-warning">Inactivo</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('roles.edit', $rol->id_nivel) }}" class="btn btn-sm btn-warning" title="Configurar permisos">
                            <i class="fas fa-shield-alt"></i> Configurar
                        </a>
                        @if(!$rol->es_sistema)
                        <form action="{{ route('roles.destroy', $rol->id_nivel) }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('¿Esta seguro de eliminar el rol {{ addslashes($rol->nombre) }}?');">
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
                    <td colspan="6" class="text-center text-muted">No hay roles registrados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
