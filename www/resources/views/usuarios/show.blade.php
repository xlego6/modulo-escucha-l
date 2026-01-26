@extends('layouts.app')

@section('title', 'Ver Usuario')
@section('content_header', 'Detalle de Usuario')

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">Datos de Cuenta</h3>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-4">ID:</dt>
                    <dd class="col-sm-8">{{ $usuario->id }}</dd>

                    <dt class="col-sm-4">Nombre:</dt>
                    <dd class="col-sm-8">{{ $usuario->name }}</dd>

                    <dt class="col-sm-4">Correo:</dt>
                    <dd class="col-sm-8">{{ $usuario->email }}</dd>

                    <dt class="col-sm-4">Registrado:</dt>
                    <dd class="col-sm-8">{{ $usuario->created_at ? $usuario->created_at->format('d/m/Y H:i') : 'N/A' }}</dd>

                    <dt class="col-sm-4">Actualizado:</dt>
                    <dd class="col-sm-8">{{ $usuario->updated_at ? $usuario->updated_at->format('d/m/Y H:i') : 'N/A' }}</dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">Perfil y Permisos</h3>
            </div>
            <div class="card-body">
                @if($perfil)
                <dl class="row">
                    <dt class="col-sm-5">Nivel de Acceso:</dt>
                    <dd class="col-sm-7">
                        <span class="badge badge-{{ $perfil->id_nivel == 1 ? 'danger' : ($perfil->id_nivel <= 4 ? 'warning' : 'info') }}">
                            {{ $perfil->fmt_id_nivel }}
                        </span>
                    </dd>

                    <dt class="col-sm-5">No. Entrevistador:</dt>
                    <dd class="col-sm-7">{{ $perfil->fmt_numero_entrevistador }}</dd>

                    <dt class="col-sm-5">Dependencia:</dt>
                    <dd class="col-sm-7">{{ $perfil->fmt_dependencia_origen }}</dd>

                    <dt class="col-sm-5">Solo Lectura:</dt>
                    <dd class="col-sm-7">
                        @if($perfil->solo_lectura)
                            <span class="badge badge-secondary">Si</span>
                        @else
                            <span class="badge badge-success">No</span>
                        @endif
                    </dd>
                </dl>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Este usuario no tiene un perfil de entrevistador asignado.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-footer">
        <a href="{{ route('usuarios.edit', $usuario->id) }}" class="btn btn-warning">
            <i class="fas fa-edit mr-1"></i> Editar
        </a>
        <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
</div>
@endsection
