@extends('layouts.app')

@section('title', 'Detalle de Permiso')
@section('content_header', 'Detalle del Permiso')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Informacion del Permiso</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">ID Permiso:</dt>
                    <dd class="col-sm-8">{{ $permiso->id_permiso }}</dd>

                    <dt class="col-sm-4">Tipo:</dt>
                    <dd class="col-sm-8">
                        <span class="badge badge-{{ $permiso->id_tipo == 3 ? 'danger' : ($permiso->id_tipo == 2 ? 'warning' : 'info') }}">
                            {{ $permiso->fmt_tipo }}
                        </span>
                    </dd>

                    <dt class="col-sm-4">Estado:</dt>
                    <dd class="col-sm-8">
                        @if($permiso->id_estado == 2)
                            <span class="badge badge-danger">Revocado</span>
                        @elseif($permiso->esta_vigente)
                            <span class="badge badge-success">Vigente</span>
                        @else
                            <span class="badge badge-secondary">Vencido</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Otorgado:</dt>
                    <dd class="col-sm-8">{{ $permiso->fecha_otorgado ? $permiso->fecha_otorgado->format('d/m/Y H:i') : 'N/A' }}</dd>

                    @if($permiso->fecha_desde || $permiso->fecha_hasta)
                    <dt class="col-sm-4">Rango de acceso:</dt>
                    <dd class="col-sm-8">{{ $permiso->fmt_rango_fechas }}</dd>
                    @endif

                    @if($permiso->fecha_vencimiento)
                    <dt class="col-sm-4">Vencimiento:</dt>
                    <dd class="col-sm-8">{{ $permiso->fecha_vencimiento->format('d/m/Y') }}</dd>
                    @endif

                    <dt class="col-sm-4">Justificacion:</dt>
                    <dd class="col-sm-8">{{ $permiso->justificacion ?: 'No especificada' }}</dd>
                </dl>
            </div>
        </div>

        @if($permiso->rel_adjunto)
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-pdf mr-2"></i>Documento de Soporte</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Archivo:</dt>
                    <dd class="col-sm-8">{{ $permiso->rel_adjunto->nombre_original }}</dd>

                    <dt class="col-sm-4">Tamano:</dt>
                    <dd class="col-sm-8">{{ number_format($permiso->rel_adjunto->tamano / 1024, 2) }} KB</dd>
                </dl>
                <a href="{{ route('permisos.descargar_soporte', $permiso->id_permiso) }}" class="btn btn-outline-primary">
                    <i class="fas fa-download mr-1"></i> Descargar Soporte
                </a>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">Usuario y Entrevista</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Usuario:</dt>
                    <dd class="col-sm-8">
                        @if($permiso->rel_entrevistador && $permiso->rel_entrevistador->rel_usuario)
                            <a href="{{ route('permisos.por_usuario', $permiso->id_entrevistador) }}">
                                {{ $permiso->rel_entrevistador->rel_usuario->name }}
                            </a>
                            <br><small class="text-muted">{{ $permiso->rel_entrevistador->rel_usuario->email }}</small>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Entrevista:</dt>
                    <dd class="col-sm-8">
                        @if($permiso->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $permiso->id_e_ind_fvt) }}">
                                {{ $permiso->codigo_entrevista ?? $permiso->rel_entrevista->entrevista_codigo }}
                            </a>
                            <br><small class="text-muted">{{ $permiso->rel_entrevista->titulo }}</small>
                        @else
                            <span class="text-muted">{{ $permiso->codigo_entrevista ?? 'N/A' }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Otorgado por:</dt>
                    <dd class="col-sm-8">
                        @if($permiso->rel_otorgado_por && $permiso->rel_otorgado_por->rel_usuario)
                            {{ $permiso->rel_otorgado_por->rel_usuario->name }}
                        @else
                            <span class="text-muted">Sistema</span>
                        @endif
                    </dd>
                </dl>
            </div>
        </div>

        @if($permiso->id_estado == 2)
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-ban mr-2"></i>Informacion de Revocacion</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">Revocado por:</dt>
                    <dd class="col-sm-8">
                        @if($permiso->rel_revocado_por && $permiso->rel_revocado_por->rel_usuario)
                            {{ $permiso->rel_revocado_por->rel_usuario->name }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4">Fecha:</dt>
                    <dd class="col-sm-8">{{ $permiso->fecha_revocado ? $permiso->fecha_revocado->format('d/m/Y H:i') : 'N/A' }}</dd>
                </dl>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-footer">
        @if($permiso->id_estado != 2 && $permiso->esta_vigente)
        <form action="{{ route('permisos.destroy', $permiso->id_permiso) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Esta seguro de revocar este permiso?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-ban mr-1"></i> Revocar Permiso
            </button>
        </form>
        @endif
        <a href="{{ route('permisos.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
        <a href="{{ route('permisos.por_entrevista', $permiso->id_e_ind_fvt) }}" class="btn btn-info">
            <i class="fas fa-list mr-1"></i> Ver todos los permisos de esta entrevista
        </a>
    </div>
</div>
@endsection
