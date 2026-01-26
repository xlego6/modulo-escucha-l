@extends('layouts.app')

@section('title', 'Traza de Actividad')
@section('content_header', 'Traza de Actividad del Sistema')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filtros de Busqueda</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form action="{{ route('traza.index') }}" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="id_usuario">Usuario</label>
                        <select class="form-control" id="id_usuario" name="id_usuario">
                            @foreach($usuarios as $id => $nombre)
                                <option value="{{ $id }}" {{ request('id_usuario') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="accion">Accion</label>
                        <select class="form-control" id="accion" name="accion">
                            @foreach($acciones as $key => $valor)
                                <option value="{{ $key }}" {{ request('accion') == $key ? 'selected' : '' }}>{{ $valor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="objeto">Objeto</label>
                        <select class="form-control" id="objeto" name="objeto">
                            @foreach($objetos as $key => $valor)
                                <option value="{{ $key }}" {{ request('objeto') == $key ? 'selected' : '' }}>{{ $valor }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="fecha_desde">Fecha Desde</label>
                        <input type="date" class="form-control" id="fecha_desde" name="fecha_desde" value="{{ request('fecha_desde') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="fecha_hasta">Fecha Hasta</label>
                        <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta" value="{{ request('fecha_hasta') }}">
                    </div>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-group mb-0 w-100">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label for="busqueda">Buscar en Codigo/Referencia</label>
                        <input type="text" class="form-control" id="busqueda" name="busqueda" value="{{ request('busqueda') }}" placeholder="Buscar...">
                    </div>
                </div>
                <div class="col-md-8 d-flex align-items-end justify-content-end">
                    <a href="{{ route('traza.index') }}" class="btn btn-default mr-2">
                        <i class="fas fa-eraser mr-1"></i>Limpiar
                    </a>
                    <a href="{{ route('traza.estadisticas') }}" class="btn btn-info">
                        <i class="fas fa-chart-bar mr-1"></i>Estadisticas
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Registro de Actividad</h3>
        <div class="card-tools">
            <span class="badge badge-info">{{ $trazas->total() }} registros</span>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover table-sm">
            <thead>
                <tr>
                    <th style="width: 150px">Fecha/Hora</th>
                    <th style="width: 180px">Usuario</th>
                    <th style="width: 120px">Accion</th>
                    <th style="width: 100px">Objeto</th>
                    <th>Codigo</th>
                    <th>Referencia</th>
                    <th style="width: 80px">IP</th>
                    <th style="width: 60px">Ver</th>
                </tr>
            </thead>
            <tbody>
                @forelse($trazas as $traza)
                <tr>
                    <td>
                        <small>{{ $traza->fmt_fecha_hora }}</small>
                    </td>
                    <td>
                        @if($traza->rel_usuario)
                            <span title="{{ $traza->rel_usuario->email }}">{{ $traza->rel_usuario->name }}</span>
                        @else
                            <span class="text-muted">Usuario eliminado</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $traza->badge_class }}">{{ $traza->fmt_accion }}</span>
                    </td>
                    <td>
                        <small>{{ $traza->fmt_objeto }}</small>
                    </td>
                    <td>
                        @if($traza->codigo)
                            <code>{{ \Illuminate\Support\Str::limit($traza->codigo, 25) }}</code>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        <small class="text-muted">{{ \Illuminate\Support\Str::limit($traza->referencia, 40) }}</small>
                    </td>
                    <td>
                        <small class="text-muted">{{ $traza->ip }}</small>
                    </td>
                    <td>
                        <a href="{{ route('traza.show', $traza->id_traza_actividad) }}" class="btn btn-xs btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        No se encontraron registros de actividad
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($trazas->hasPages())
    <div class="card-footer">
        {{ $trazas->links() }}
    </div>
    @endif
</div>
@endsection
