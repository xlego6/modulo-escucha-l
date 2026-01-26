@extends('layouts.app')

@section('title', 'Detalle de Actividad')
@section('content_header', 'Detalle del Registro de Actividad')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informacion del Registro</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <dl>
                            <dt>ID</dt>
                            <dd>{{ $traza->id_traza_actividad }}</dd>

                            <dt>Fecha y Hora</dt>
                            <dd>{{ $traza->fmt_fecha_hora }}</dd>

                            <dt>Usuario</dt>
                            <dd>
                                @if($traza->rel_usuario)
                                    {{ $traza->rel_usuario->name }}
                                    <br><small class="text-muted">{{ $traza->rel_usuario->email }}</small>
                                @else
                                    <span class="text-muted">Usuario eliminado (ID: {{ $traza->id_usuario }})</span>
                                @endif
                            </dd>

                            <dt>Direccion IP</dt>
                            <dd>{{ $traza->ip ?? 'No registrada' }}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl>
                            <dt>Accion</dt>
                            <dd>
                                <span class="badge badge-{{ $traza->badge_class }}">{{ $traza->fmt_accion }}</span>
                            </dd>

                            <dt>Objeto</dt>
                            <dd>{{ $traza->fmt_objeto ?: '-' }}</dd>

                            <dt>ID Registro</dt>
                            <dd>{{ $traza->id_registro ?? '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <hr>

                <dl>
                    <dt>Codigo</dt>
                    <dd>
                        @if($traza->codigo)
                            <code class="d-block p-2 bg-light">{{ $traza->codigo }}</code>
                        @else
                            <span class="text-muted">Sin codigo</span>
                        @endif
                    </dd>

                    <dt>Referencia</dt>
                    <dd>
                        @if($traza->referencia)
                            <p class="mb-0">{{ $traza->referencia }}</p>
                        @else
                            <span class="text-muted">Sin referencia</span>
                        @endif
                    </dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="{{ route('traza.index') }}" class="btn btn-default">
                    <i class="fas fa-arrow-left mr-1"></i>Volver al listado
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Contexto Temporal</h3>
            </div>
            <div class="card-body">
                @php
                    $fecha = \Carbon\Carbon::parse($traza->fecha_hora);
                @endphp
                <p><strong>Hace:</strong> {{ $fecha->diffForHumans() }}</p>
                <p><strong>Dia:</strong> {{ $fecha->isoFormat('dddd') }}</p>
                <p><strong>Fecha completa:</strong> {{ $fecha->isoFormat('D [de] MMMM [de] YYYY') }}</p>
                <p class="mb-0"><strong>Hora:</strong> {{ $fecha->format('H:i:s') }}</p>
            </div>
        </div>

        @if($traza->id_personificador)
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-secret mr-2"></i>Personificacion</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-0">
                    Esta accion fue realizada por un administrador personificando al usuario.
                </p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
