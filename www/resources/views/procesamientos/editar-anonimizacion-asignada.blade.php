@extends('layouts.app')

@section('title', 'Editar anonimización')
@section('content_header')
Anonimizar: {{ $entrevista->entrevista_codigo }}
@endsection

@section('css')
@include('procesamientos.partials.anonimizacion-editor-css')
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12">
        @include('procesamientos.partials.anonimizacion-editor', ['tituloEditor' => 'Texto Anonimizado (Editable)', 'iconoEditor' => 'fa-user-secret'])

        {{-- Boton Enviar a Revision --}}
        <div class="card card-outline card-success">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-1">¿Finalizo la anonimización?</h5>
                        <p class="text-muted mb-0">
                            Una vez enviada, un revisor verificara su trabajo.
                        </p>
                    </div>
                    <div class="col-md-4 text-right">
                        <form action="{{ route('procesamientos.enviar-anonimizacion-revision', $asignacion->id_asignacion) }}"
                              method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success"
                                    onclick="return confirm('¿Enviar a revision? No podra editarla hasta que sea revisada.')">
                                <i class="fas fa-paper-plane mr-1"></i> Enviar a revisión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tarjetas de información (parte inferior) --}}
<div class="row">
    <div class="col-md-4">
        {{-- Estado de la asignacion --}}
        <div class="card card-danger">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Asignación</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Estado:</dt>
                    <dd class="col-sm-7">
                        <span class="badge {{ $asignacion->estado_badge_class }}">
                            {{ $asignacion->fmt_estado }}
                        </span>
                    </dd>
                    <dt class="col-sm-5">Asignada:</dt>
                    <dd class="col-sm-7">{{ $asignacion->fecha_asignacion->format('d/m/Y') }}</dd>
                </dl>

                @if($asignacion->estado == 'rechazada' && $asignacion->comentario_revision)
                <hr>
                <div class="alert alert-danger mb-0">
                    <strong><i class="fas fa-exclamation-circle mr-1"></i>Motivo del rechazo:</strong><br>
                    {{ $asignacion->comentario_revision }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-8">
        @include('procesamientos.partials.anonimizacion-etiquetas')
    </div>
</div>

<a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i> Volver
</a>
@endsection

@section('js')
@include('procesamientos.partials.anonimizacion-editor-js')
@endsection
