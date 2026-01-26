@extends('layouts.app')

@section('title', 'Mis Transcripciones Asignadas')
@section('content_header', 'Mis Transcripciones Asignadas')

@section('content')
{{-- Estadísticas --}}
<div class="row">
    <div class="col">
        <div class="info-box bg-secondary">
            <span class="info-box-icon"><i class="fas fa-inbox"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Asignadas</span>
                <span class="info-box-number">{{ $stats['asignadas'] }}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-edit"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En Edicion</span>
                <span class="info-box-number">{{ $stats['en_edicion'] }}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">En Revision</span>
                <span class="info-box-number">{{ $stats['enviadas'] }}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-undo"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Rechazadas</span>
                <span class="info-box-number">{{ $stats['rechazadas'] }}</span>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Finalizadas</span>
                <span class="info-box-number">{{ $stats['aprobadas'] }}</span>
            </div>
        </div>
    </div>
</div>

{{-- Lista de Asignaciones --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Transcripciones Asignadas</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>Fecha Asignacion</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asignaciones as $asignacion)
                @php
                    $rowClass = '';
                    if ($asignacion->estado == 'rechazada') $rowClass = 'table-danger';
                    elseif ($asignacion->estado == 'aprobada') $rowClass = 'table-success';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td><code>{{ $asignacion->rel_entrevista->entrevista_codigo ?? 'N/A' }}</code></td>
                    <td>
                        {{ \Illuminate\Support\Str::limit($asignacion->rel_entrevista->titulo ?? '', 45) }}
                    </td>
                    <td>
                        @if($asignacion->fecha_asignacion)
                            {{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $asignacion->estado_badge_class }}">
                            {{ $asignacion->estado == 'aprobada' ? 'Finalizada' : $asignacion->fmt_estado }}
                        </span>
                        @if($asignacion->estado == 'rechazada' && $asignacion->comentario_revision)
                            <br>
                            <small class="text-danger" title="{{ $asignacion->comentario_revision }}">
                                <i class="fas fa-exclamation-circle"></i>
                                {{ \Illuminate\Support\Str::limit($asignacion->comentario_revision, 30) }}
                            </small>
                        @endif
                    </td>
                    <td>
                        @if(in_array($asignacion->estado, ['asignada', 'en_edicion', 'rechazada']))
                            <a href="{{ route('procesamientos.editar-asignacion', $asignacion->id_asignacion) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        @elseif($asignacion->estado == 'enviada_revision')
                            <span class="text-muted">
                                <i class="fas fa-hourglass-half"></i> En revision
                            </span>
                        @elseif($asignacion->estado == 'aprobada')
                            <span class="text-success">
                                <i class="fas fa-check-circle"></i> Completada
                            </span>
                            @if($asignacion->fecha_revision)
                                <br><small class="text-muted">{{ $asignacion->fecha_revision->format('d/m/Y') }}</small>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        No tiene transcripciones asignadas
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($asignaciones->hasPages())
    <div class="card-footer">
        {{ $asignaciones->links() }}
    </div>
    @endif
</div>

{{-- Ayuda --}}
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Flujo de Trabajo</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col">
                <span class="badge badge-secondary p-2 mb-2">1. Asignada</span>
                <p class="small mb-0">Se le asigna una transcripcion</p>
            </div>
            <div class="col">
                <span class="badge badge-primary p-2 mb-2">2. En Edicion</span>
                <p class="small mb-0">Trabaja en la transcripcion</p>
            </div>
            <div class="col">
                <span class="badge badge-warning p-2 mb-2">3. En Revision</span>
                <p class="small mb-0">Enviada para revision</p>
            </div>
            <div class="col">
                <span class="badge badge-danger p-2 mb-2">Rechazada</span>
                <p class="small mb-0">Requiere correcciones</p>
            </div>
            <div class="col">
                <span class="badge badge-success p-2 mb-2">4. Finalizada</span>
                <p class="small mb-0">Aprobada y completada</p>
            </div>
        </div>
    </div>
</div>
@endsection
