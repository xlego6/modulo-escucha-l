@extends('layouts.app')

@section('title', 'Entrevistas')
@section('content_header', 'Listado de Entrevistas')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros de busqueda</h3>
        <div class="card-tools">
            <a href="{{ route('entrevistas.wizard.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nueva Entrevista
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('entrevistas.index') }}" class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Codigo</label>
                    <input type="text" name="codigo" class="form-control form-control-sm" value="{{ request('codigo') }}" placeholder="VI-0001-001">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Titulo</label>
                    <input type="text" name="titulo" class="form-control form-control-sm" value="{{ request('titulo') }}" placeholder="Buscar en titulo...">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fecha desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fecha hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Entrevistador</label>
                    <select name="id_entrevistador" class="form-control form-control-sm">
                        @foreach($entrevistadores as $id => $nombre)
                            <option value="{{ $id }}" {{ request('id_entrevistador') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
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
        <h3 class="card-title">Entrevistas ({{ $entrevistas->total() }} registros)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th style="width: 120px">Codigo</th>
                    <th>Titulo</th>
                    <th style="width: 100px">Fecha</th>
                    <th style="width: 150px">Entrevistador</th>
                    <th style="width: 80px">Duracion</th>
                    <th style="width: 120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entrevistas as $entrevista)
                <tr>
                    <td>
                        <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}">
                            <strong>{{ $entrevista->entrevista_codigo }}</strong>
                        </a>
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($entrevista->titulo, 60) }}</td>
                    <td>{{ $entrevista->fmt_fecha }}</td>
                    <td>
                        @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                            {{ $entrevista->rel_entrevistador->rel_usuario->name }}
                        @else
                            <span class="text-muted">Sin asignar</span>
                        @endif
                    </td>
                    <td>
                        @if($entrevista->tiempo_entrevista)
                            {{ $entrevista->tiempo_entrevista }} min
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('entrevistas.wizard.edit', $entrevista->id_e_ind_fvt) }}" class="btn btn-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('entrevistas.destroy', $entrevista->id_e_ind_fvt) }}" method="POST" style="display:inline" onsubmit="return confirm('Esta seguro de eliminar esta entrevista?')">
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
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No se encontraron entrevistas</p>
                        <a href="{{ route('entrevistas.wizard.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear primera entrevista
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entrevistas->hasPages())
    <div class="card-footer">
        {{ $entrevistas->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
