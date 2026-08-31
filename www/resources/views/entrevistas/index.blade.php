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
                    <label>Fecha de toma desde</label>
                    <input type="date" name="fecha_desde" class="form-control form-control-sm" value="{{ request('fecha_desde') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fecha de toma hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="{{ request('fecha_hasta') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fecha de carga desde</label>
                    <input type="date" name="carga_desde" class="form-control form-control-sm" value="{{ request('carga_desde') }}">
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Fecha de carga hasta</label>
                    <input type="date" name="carga_hasta" class="form-control form-control-sm" value="{{ request('carga_hasta') }}">
                </div>
            </div>
            @if($entrevistadores->isNotEmpty())
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
            @endif
            @if($dependencias->isNotEmpty())
            <div class="col-md-2">
                <div class="form-group">
                    <label>Dependencia</label>
                    <select name="id_dependencia" class="form-control form-control-sm">
                        @foreach($dependencias as $id => $nombre)
                            <option value="{{ $id }}" {{ request('id_dependencia') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
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
        @php
            $sortLink = function($column, $label) use ($sortColumn, $sortDir) {
                $newDir = ($sortColumn === $column && $sortDir === 'asc') ? 'desc' : 'asc';
                $icon = 'fa-sort text-muted';
                if ($sortColumn === $column) {
                    $icon = $sortDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
                }
                $params = array_merge(request()->except(['sort', 'dir', 'page']), ['sort' => $column, 'dir' => $newDir]);
                $url = route('entrevistas.index', $params);
                return '<a href="' . e($url) . '" class="text-dark">' . e($label) . ' <i class="fas ' . $icon . ' fa-xs ml-1"></i></a>';
            };
        @endphp
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th style="width: 120px">{!! $sortLink('entrevista_codigo', 'Codigo') !!}</th>
                    <th>{!! $sortLink('titulo', 'Titulo') !!}</th>
                    <th style="width: 100px">{!! $sortLink('entrevista_fecha', 'Fecha de toma') !!}</th>
                    <th style="width: 130px">{!! $sortLink('created_at', 'Fecha de carga') !!}</th>
                    <th style="width: 180px">{!! $sortLink('nombre_entrevistador', 'Entrevistador / Carga') !!}</th>
                    <th style="width: 80px">{!! $sortLink('tiempo_entrevista', 'Duracion') !!}</th>
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
                    <td>{{ $entrevista->created_at?->format('d/m/Y') }}</td>
                    <td>
                        @if($entrevista->nombre_entrevistador)
                            {{ $entrevista->nombre_entrevistador }}
                        @else
                            <span class="text-muted">Sin asignar</span>
                        @endif
                        @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                            <br><small class="text-muted"><i class="fas fa-upload fa-xs"></i> {{ $entrevista->rel_entrevistador->rel_usuario->name }}</small>
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
                    <td colspan="7" class="text-center text-muted py-4">
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
