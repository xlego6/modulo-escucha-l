@extends('layouts.app')

@section('title', 'Permisos de Acceso')
@section('content_header', 'Permisos de Acceso a Entrevistas')

@section('content')

{{-- Solicitudes Pendientes (Admin / Gestor) --}}
@if($solicitudesPendientes->count() > 0)
<div class="card card-warning">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bell mr-2"></i>Solicitudes Pendientes ({{ $solicitudesPendientes->count() }})
        </h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Solicitante</th>
                    <th>Entrevista</th>
                    <th>Tipo</th>
                    <th>Justificación</th>
                    <th>Fecha</th>
                    <th width="140">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($solicitudesPendientes as $sol)
                <tr>
                    <td>
                        @if($sol->rel_entrevistador && $sol->rel_entrevistador->rel_usuario)
                            {{ $sol->rel_entrevistador->rel_usuario->name }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($sol->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $sol->id_e_ind_fvt) }}">
                                {{ $sol->codigo_entrevista }}
                            </a>
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($sol->rel_entrevista->titulo, 35) }}</small>
                        @else
                            {{ $sol->codigo_entrevista ?? 'N/A' }}
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $sol->tipo_solicitud === 'eliminacion' ? 'danger' : ($sol->tipo_solicitud === 'edicion' ? 'warning' : 'info') }}">
                            {{ $sol->fmt_tipo_solicitud }}
                        </span>
                    </td>
                    <td>
                        <small>{{ \Illuminate\Support\Str::limit($sol->justificacion, 60) }}</small>
                    </td>
                    <td>
                        <small>{{ $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y H:i') : '-' }}</small>
                    </td>
                    <td>
                        <form action="{{ route('permisos.aprobar', $sol->id_permiso) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Aprobar esta solicitud?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success" title="Aprobar">
                                <i class="fas fa-check"></i> Aprobar
                            </button>
                        </form>
                        <form action="{{ route('permisos.rechazar', $sol->id_permiso) }}" method="POST" style="display:inline" onsubmit="return confirm('¿Rechazar esta solicitud?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger" title="Rechazar">
                                <i class="fas fa-times"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Mis Solicitudes (Entrevistador / Gestor) --}}
@if($misSolicitudes->count() > 0)
<div class="card card-info card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-paper-plane mr-2"></i>Mis Solicitudes</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Entrevista</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Fecha Solicitud</th>
                    <th>Respuesta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($misSolicitudes as $sol)
                <tr>
                    <td>
                        @if($sol->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $sol->id_e_ind_fvt) }}">{{ $sol->codigo_entrevista }}</a>
                        @else
                            {{ $sol->codigo_entrevista ?? 'N/A' }}
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $sol->tipo_solicitud === 'eliminacion' ? 'danger' : ($sol->tipo_solicitud === 'edicion' ? 'warning' : 'info') }}">
                            {{ $sol->fmt_tipo_solicitud }}
                        </span>
                    </td>
                    <td>
                        @if($sol->estado_solicitud === 'pendiente')
                            <span class="badge badge-secondary"><i class="fas fa-clock"></i> Pendiente</span>
                        @elseif($sol->estado_solicitud === 'aprobado')
                            <span class="badge badge-success"><i class="fas fa-check"></i> Aprobada</span>
                        @elseif($sol->estado_solicitud === 'rechazado')
                            <span class="badge badge-danger"><i class="fas fa-times"></i> Rechazada</span>
                        @endif
                    </td>
                    <td><small>{{ $sol->fecha_solicitud ? $sol->fecha_solicitud->format('d/m/Y H:i') : '-' }}</small></td>
                    <td><small>{{ $sol->fecha_respuesta ? $sol->fecha_respuesta->format('d/m/Y H:i') : '-' }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Lista de Permisos (Admin ve todos, otros ven los suyos) --}}
@if(\App\Models\RolModuloPermiso::alcanceTodas(Auth::user()->id_nivel, 'permisos'))
<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-9">
                <form action="{{ route('permisos.index') }}" method="GET" class="form-inline">
                    <select name="id_entrevistador" class="form-control form-control-sm mr-2 mb-2">
                        @foreach($entrevistadores as $id => $nombre)
                        <option value="{{ $id }}" {{ request('id_entrevistador') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="codigo" class="form-control form-control-sm mr-2 mb-2" placeholder="Codigo entrevista" value="{{ request('codigo') }}">
                    <select name="estado" class="form-control form-control-sm mr-2 mb-2">
                        <option value="">-- Estado --</option>
                        <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>Vigentes</option>
                        <option value="2" {{ request('estado') == '2' ? 'selected' : '' }}>Revocados</option>
                    </select>
                    <select name="tipo" class="form-control form-control-sm mr-2 mb-2">
                        @foreach($tipos as $id => $nombre)
                        <option value="{{ $id }}" {{ request('tipo') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-default mr-2 mb-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('permisos.index') }}" class="btn btn-sm btn-secondary mb-2">
                        <i class="fas fa-times"></i>
                    </a>
                </form>
            </div>
            <div class="col-md-3 text-right">
                <a href="{{ route('permisos.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i> Otorgar
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Entrevista</th>
                    <th>Tipo</th>
                    <th>Rango/Vencimiento</th>
                    <th>Otorgado</th>
                    <th>Soporte</th>
                    <th>Estado</th>
                    <th width="120">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permisos as $permiso)
                <tr class="{{ $permiso->id_estado == 2 ? 'table-secondary' : '' }}">
                    <td>{{ $permiso->id_permiso }}</td>
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
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($permiso->rel_entrevista->titulo, 30) }}</small>
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
                        <small>{{ $permiso->fecha_otorgado ? $permiso->fecha_otorgado->format('d/m/Y') : 'N/A' }}</small>
                        @if($permiso->rel_otorgado_por && $permiso->rel_otorgado_por->rel_usuario)
                            <br><small class="text-muted">por {{ $permiso->rel_otorgado_por->rel_usuario->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($permiso->rel_adjunto)
                            <a href="{{ route('permisos.descargar_soporte', $permiso->id_permiso) }}" class="text-primary" title="Descargar soporte">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($permiso->id_estado == 2)
                            <span class="badge badge-danger">Revocado</span>
                        @elseif($permiso->esta_vigente)
                            <span class="badge badge-success">Vigente</span>
                        @else
                            <span class="badge badge-secondary">Vencido</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('permisos.show', $permiso->id_permiso) }}" class="btn btn-sm btn-info" title="Ver">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($permiso->id_estado != 2)
                        <form action="{{ route('permisos.destroy', $permiso->id_permiso) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Esta seguro de revocar este permiso?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Revocar">
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
                        <p class="mb-0">No se encontraron permisos</p>
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
@endif

@endsection
