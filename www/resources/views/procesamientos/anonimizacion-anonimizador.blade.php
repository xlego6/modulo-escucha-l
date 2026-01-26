@extends('layouts.app')

@section('title', 'Mis Anonimizaciones')
@section('content_header', 'Mis Anonimizaciones Asignadas')

@section('content')
{{-- Estadisticas --}}
<div class="row">
    <div class="col">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ $stats['asignadas'] }}</h3>
                <p>Asignadas</p>
            </div>
            <div class="icon"><i class="fas fa-inbox"></i></div>
        </div>
    </div>
    <div class="col">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['en_edicion'] }}</h3>
                <p>En Edicion</p>
            </div>
            <div class="icon"><i class="fas fa-edit"></i></div>
        </div>
    </div>
    <div class="col">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['enviadas'] }}</h3>
                <p>En Revision</p>
            </div>
            <div class="icon"><i class="fas fa-paper-plane"></i></div>
        </div>
    </div>
    <div class="col">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['rechazadas'] }}</h3>
                <p>Rechazadas</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
        </div>
    </div>
    <div class="col">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['aprobadas'] }}</h3>
                <p>Finalizadas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
</div>

{{-- Lista de asignaciones --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-user-secret mr-2"></i>Mis Anonimizaciones</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>Estado</th>
                    <th>Tipos</th>
                    <th>Fecha Asignacion</th>
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
                    <td>{{ \Illuminate\Support\Str::limit($asignacion->rel_entrevista->titulo ?? '', 40) }}</td>
                    <td>
                        <span class="badge {{ $asignacion->estado_badge_class }}">
                            {{ $asignacion->estado == 'aprobada' ? 'Finalizada' : $asignacion->fmt_estado }}
                        </span>
                    </td>
                    <td>
                        @foreach(explode(',', $asignacion->tipos_anonimizar ?? 'PER,LOC') as $tipo)
                            <span class="badge badge-dark">{{ $tipo }}</span>
                        @endforeach
                    </td>
                    <td>{{ $asignacion->fecha_asignacion->format('d/m/Y H:i') }}</td>
                    <td>
                        @if($asignacion->estado == 'rechazada')
                            <div class="mb-2">
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $asignacion->comentario_revision }}
                                </small>
                            </div>
                        @endif

                        @if(in_array($asignacion->estado, ['asignada', 'en_edicion', 'rechazada']))
                            <a href="{{ route('procesamientos.editar-anonimizacion-asignada', $asignacion->id_asignacion) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit mr-1"></i>
                                {{ $asignacion->estado == 'asignada' ? 'Iniciar' : 'Continuar' }}
                            </a>
                        @elseif($asignacion->estado == 'enviada_revision')
                            <span class="text-muted">
                                <i class="fas fa-hourglass-half mr-1"></i> En revision
                            </span>
                        @elseif($asignacion->estado == 'aprobada')
                            <span class="text-success">
                                <i class="fas fa-check-circle mr-1"></i> Completada
                            </span>
                            @if($asignacion->fecha_revision)
                                <br><small class="text-muted">{{ $asignacion->fecha_revision->format('d/m/Y') }}</small>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-user-secret fa-2x mb-2"></i><br>
                        No tiene anonimizaciones asignadas
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

{{-- Flujo de Trabajo --}}
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Flujo de Trabajo</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col">
                <span class="badge badge-secondary p-2 mb-2">1. Asignada</span>
                <p class="small mb-0">Se le asigna una anonimizacion</p>
            </div>
            <div class="col">
                <span class="badge badge-primary p-2 mb-2">2. En Edicion</span>
                <p class="small mb-0">Trabaja en la anonimizacion</p>
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
