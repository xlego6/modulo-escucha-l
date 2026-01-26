@extends('layouts.app')

@section('title', 'Accesos Otorgados')
@section('content_header', 'Accesos Otorgados')

@section('content')
{{-- Estadisticas --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['total_vigentes'] }}</h3>
                <p>Accesos Vigentes</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['total_revocados'] }}</h3>
                <p>Accesos Revocados</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['con_soporte'] }}</h3>
                <p>Con Documento Soporte</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-pdf"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['otorgados_hoy'] }}</h3>
                <p>Otorgados Hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-calendar-day"></i>
            </div>
        </div>
    </div>
</div>

{{-- Filtros y tabla --}}
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-10">
                <form action="{{ route('permisos.accesos_otorgados') }}" method="GET" class="form-inline">
                    <select name="id_entrevistador" class="form-control form-control-sm mr-2 mb-2">
                        @foreach($entrevistadores as $id => $nombre)
                        <option value="{{ $id }}" {{ request('id_entrevistador') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="codigo" class="form-control form-control-sm mr-2 mb-2" placeholder="Codigo entrevista" value="{{ request('codigo') }}">
                    <select name="vigencia" class="form-control form-control-sm mr-2 mb-2">
                        <option value="">-- Vigencia --</option>
                        <option value="vigente" {{ request('vigencia') == 'vigente' ? 'selected' : '' }}>Vigentes ahora</option>
                        <option value="vencido" {{ request('vigencia') == 'vencido' ? 'selected' : '' }}>Vencidos</option>
                    </select>
                    <select name="con_soporte" class="form-control form-control-sm mr-2 mb-2">
                        <option value="">-- Soporte --</option>
                        <option value="1" {{ request('con_soporte') == '1' ? 'selected' : '' }}>Con documento</option>
                        <option value="0" {{ request('con_soporte') == '0' ? 'selected' : '' }}>Sin documento</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-default mr-2 mb-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('permisos.accesos_otorgados') }}" class="btn btn-sm btn-secondary mb-2">
                        <i class="fas fa-times"></i>
                    </a>
                </form>
            </div>
            <div class="col-md-2 text-right">
                <a href="{{ route('permisos.desclasificar') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-unlock-alt mr-1"></i> Desclasificar
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Entrevista</th>
                    <th>Tipo</th>
                    <th>Rango Acceso</th>
                    <th>Otorgado por</th>
                    <th>Fecha</th>
                    <th>Soporte</th>
                    <th>Estado</th>
                    <th width="100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permisos as $permiso)
                <tr class="{{ !$permiso->esta_vigente ? 'text-muted' : '' }}">
                    <td>
                        @if($permiso->rel_entrevistador && $permiso->rel_entrevistador->rel_usuario)
                            <a href="{{ route('permisos.por_usuario', $permiso->id_entrevistador) }}">
                                {{ $permiso->rel_entrevistador->rel_usuario->name }}
                            </a>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($permiso->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $permiso->id_e_ind_fvt) }}">
                                {{ $permiso->codigo_entrevista ?? $permiso->rel_entrevista->entrevista_codigo }}
                            </a>
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($permiso->rel_entrevista->titulo, 25) }}</small>
                        @else
                            <span class="text-muted">{{ $permiso->codigo_entrevista ?? 'N/A' }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $permiso->id_tipo == 3 ? 'danger' : ($permiso->id_tipo == 2 ? 'warning' : 'info') }}">
                            {{ $permiso->fmt_tipo }}
                        </span>
                    </td>
                    <td>
                        @if($permiso->fecha_desde || $permiso->fecha_hasta)
                            <small>{{ $permiso->fmt_rango_fechas }}</small>
                        @elseif($permiso->fecha_vencimiento)
                            <small>Hasta {{ $permiso->fecha_vencimiento->format('d/m/Y') }}</small>
                        @else
                            <small class="text-muted">Sin limite</small>
                        @endif
                    </td>
                    <td>
                        @if($permiso->rel_otorgado_por && $permiso->rel_otorgado_por->rel_usuario)
                            <small>{{ $permiso->rel_otorgado_por->rel_usuario->name }}</small>
                        @else
                            <small class="text-muted">N/A</small>
                        @endif
                    </td>
                    <td>
                        <small>{{ $permiso->fecha_otorgado ? $permiso->fecha_otorgado->format('d/m/Y H:i') : 'N/A' }}</small>
                    </td>
                    <td class="text-center">
                        @if($permiso->rel_adjunto)
                            <a href="{{ route('permisos.descargar_soporte', $permiso->id_permiso) }}" class="btn btn-xs btn-outline-primary" title="Descargar soporte">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($permiso->esta_vigente)
                            <span class="badge badge-success">Vigente</span>
                        @elseif($permiso->id_estado == 2)
                            <span class="badge badge-danger">Revocado</span>
                        @else
                            <span class="badge badge-secondary">Vencido</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('permisos.show', $permiso->id_permiso) }}" class="btn btn-xs btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($permiso->id_estado != 2)
                        <form action="{{ route('permisos.destroy', $permiso->id_permiso) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Esta seguro de revocar este permiso?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-danger" title="Revocar">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No se encontraron accesos otorgados</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($permisos->hasPages())
    <div class="card-footer">
        {{ $permisos->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
