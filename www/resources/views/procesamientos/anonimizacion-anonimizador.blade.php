@extends('layouts.app')

@section('title', 'Mis Anonimizaciones')
@section('content_header', 'Mis Anonimizaciones Asignadas')

@section('css')
<style>
.stat-block { border-radius:6px; padding:10px 12px; color:#fff; min-height:80px; display:flex; flex-direction:column; justify-content:space-between; }
.stat-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; opacity:.85; }
.stat-main  { font-size:28px; font-weight:700; line-height:1.1; }
.stat-row   { font-size:11px; opacity:.9; display:flex; justify-content:space-between; flex-wrap:wrap; gap:4px; margin-top:2px; }
.bg-asignadas   { background: linear-gradient(135deg,#4a1f1f,#7a2e2e); }
.bg-en-edicion  { background: linear-gradient(135deg,#8e0000,#c62828); }
.bg-en-revision { background: linear-gradient(135deg,#e65100,#fb8c00); }
.bg-rechazadas  { background: linear-gradient(135deg,#4a0e0e,#8b1a1a); }
.bg-aprobadas   { background: linear-gradient(135deg,#1b5e20,#388e3c); }
</style>
@endsection

@section('content')
{{-- Bloques estadísticos --}}
@php $bt = $stats; @endphp
<div class="row mb-3">
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-asignadas">
            <div class="stat-label">Asignadas</div>
            <div class="stat-main">{{ number_format($bt['asignada']['cantidad_entrevistas'] ?? 0) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-user-secret"></i> {{ number_format($bt['asignada']['cantidad_entidades'] ?? 0) }} entidades</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-edicion">
            <div class="stat-label">En edición</div>
            <div class="stat-main">{{ number_format($bt['en_edicion']['cantidad_entrevistas'] ?? 0) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-user-secret"></i> {{ number_format($bt['en_edicion']['cantidad_entidades'] ?? 0) }} entidades</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-en-revision">
            <div class="stat-label">En revisión</div>
            <div class="stat-main">{{ number_format($bt['enviada_revision']['cantidad_entrevistas'] ?? 0) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-user-secret"></i> {{ number_format($bt['enviada_revision']['cantidad_entidades'] ?? 0) }} entidades</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-rechazadas">
            <div class="stat-label">Rechazadas</div>
            <div class="stat-main">{{ number_format($bt['rechazada']['cantidad_entrevistas'] ?? 0) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-user-secret"></i> {{ number_format($bt['rechazada']['cantidad_entidades'] ?? 0) }} entidades</span>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-6 mb-2">
        <div class="stat-block bg-aprobadas">
            <div class="stat-label">Finalizadas</div>
            <div class="stat-main">{{ number_format($bt['aprobada']['cantidad_entrevistas'] ?? 0) }}</div>
            <div class="stat-row">
                <span><i class="fas fa-user-secret"></i> {{ number_format($bt['aprobada']['cantidad_entidades'] ?? 0) }} entidades</span>
            </div>
        </div>
    </div>
</div>

{{-- Lista de Asignaciones --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Anonimizaciones Asignadas</h3>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Codigo</th>
                    <th>Titulo</th>
                    <th>F. Asignación</th>
                    <th>Estado</th>
                    <th>Revisión</th>
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
                        {{ \Illuminate\Support\Str::limit($asignacion->rel_entrevista->titulo ?? '', 40) }}
                        @if($asignacion->id_adjunto && $asignacion->rel_adjunto)
                            <br><small class="text-muted"><i class="fas fa-file-alt"></i> {{ \Illuminate\Support\Str::limit($asignacion->rel_adjunto->nombre_original, 35) }}</small>
                        @endif
                    </td>
                    <td>
                        @if($asignacion->fecha_asignacion)
                            {{ $asignacion->fecha_asignacion->format('d/m/Y') }}
                            <br><small class="text-muted">{{ $asignacion->fecha_asignacion->format('H:i') }}</small>
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
                        @elseif($asignacion->estado == 'aprobada' && $asignacion->comentario_revision)
                            <br>
                            <small class="text-success" title="{{ $asignacion->comentario_revision }}">
                                <i class="fas fa-comment-dots"></i>
                                {{ \Illuminate\Support\Str::limit($asignacion->comentario_revision, 30) }}
                            </small>
                        @endif
                    </td>
                    <td>
                        @if($asignacion->fecha_revision)
                            <small>
                                {{ $asignacion->fecha_revision->format('d/m/Y H:i') }}
                            </small>
                            @if($asignacion->rel_revisor)
                                <br><small class="text-muted">
                                    <i class="fas fa-user"></i>
                                    {{ $asignacion->rel_revisor->name ?? 'N/A' }}
                                </small>
                            @endif
                        @elseif($asignacion->fecha_envio_revision)
                            <small class="text-muted">
                                <i class="fas fa-hourglass-half"></i>
                                Enviada {{ $asignacion->fecha_envio_revision->format('d/m/Y') }}
                            </small>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if(in_array($asignacion->estado, ['asignada', 'en_edicion', 'rechazada']))
                            <a href="{{ route('procesamientos.editar-anonimizacion-asignada', $asignacion->id_asignacion) }}"
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-edit mr-1"></i>
                                {{ $asignacion->estado == 'asignada' ? 'Iniciar' : 'Continuar' }}
                            </a>
                        @elseif($asignacion->estado == 'enviada_revision')
                            <span class="text-muted">
                                <i class="fas fa-hourglass-half"></i> En revisión
                            </span>
                        @elseif($asignacion->estado == 'aprobada')
                            @if(!$asignacion->fecha_revision || $asignacion->fecha_revision->diffInDays(now()) <= 30)
                                <a href="{{ route('procesamientos.editar-anonimizacion-asignada', $asignacion->id_asignacion) }}"
                                   class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                            @else
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> Completada
                                </span>
                            @endif
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
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

{{-- Ayuda --}}
<div class="card card-outline card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Flujo de Trabajo</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col">
                <span class="badge badge-secondary p-2 mb-2">1. Asignada</span>
                <p class="small mb-0">Se le asigna una anonimización</p>
            </div>
            <div class="col">
                <span class="badge badge-primary p-2 mb-2">2. En Edición</span>
                <p class="small mb-0">Trabaja en la anonimización</p>
            </div>
            <div class="col">
                <span class="badge badge-warning p-2 mb-2">3. En Revisión</span>
                <p class="small mb-0">Enviada para revisión</p>
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
