@extends('layouts.app')

@section('title', 'Buscadora')
@section('content_header', 'Buscadora')

@section('css')
<style>
    .seccion-resultado {
        border-left: 4px solid #007bff;
        margin-bottom: 1rem;
    }
    .seccion-resultado.entrevistas { border-left-color: #28a745; }
    .seccion-resultado.personas { border-left-color: #17a2b8; }
    .seccion-resultado.documentos { border-left-color: #ffc107; }

    .seccion-header {
        cursor: pointer;
        user-select: none;
    }
    .seccion-header:hover {
        background-color: #f8f9fa;
    }

    .badge-coincidencia {
        font-size: 0.75rem;
        font-weight: normal;
    }

    .resultado-item {
        border-bottom: 1px solid #eee;
        padding: 0.75rem 1rem;
        transition: background-color 0.2s;
    }
    .resultado-item:hover {
        background-color: #f8f9fa;
    }
    .resultado-item:last-child {
        border-bottom: none;
    }

    .extracto-texto {
        font-size: 0.85em;
        color: #666;
        background-color: #f9f9f9;
        padding: 0.5rem;
        border-radius: 4px;
        margin-top: 0.5rem;
        max-height: 80px;
        overflow: hidden;
    }

    .icono-fuente {
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        margin-right: 0.5rem;
    }
    .icono-fuente.entrevista { background-color: #d4edda; color: #28a745; }
    .icono-fuente.persona { background-color: #d1ecf1; color: #17a2b8; }
    .icono-fuente.documento { background-color: #fff3cd; color: #856404; }

    .sin-resultados {
        text-align: center;
        padding: 2rem;
        color: #6c757d;
    }

    .contador-seccion {
        font-size: 0.9rem;
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Formulario de busqueda -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Buscadora</h3>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('buscador.index') }}">
                    <div class="row">
                        <div class="col-md-10">
                            <div class="form-group mb-0">
                                <div class="input-group input-group-lg">
                                    <input type="text" name="q" id="q" class="form-control"
                                        value="{{ $termino }}"
                                        placeholder="Buscar en entrevistas, personas y documentos..."
                                        autofocus>
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted mt-2">
                                    <i class="fas fa-info-circle"></i>
                                    Busca simultaneamente en: codigos y titulos de entrevistas, nombres de personas, y contenido de documentos (transcripciones, PDFs)
                                </small>
                            </div>
                        </div>
                        <div class="col-md-2">
                            @if($tiene_busqueda)
                            <a href="{{ route('buscador.index') }}" class="btn btn-outline-secondary btn-lg btn-block">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($tiene_busqueda)
            <!-- Resumen de resultados -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-chart-bar"></i>
                        <strong>{{ $resultados['total'] }}</strong> resultado(s) encontrados para "<strong>{{ $termino }}</strong>":
                        <span class="badge badge-success ml-2">
                            <i class="fas fa-microphone"></i> {{ $resultados['entrevistas']->count() }} Entrevistas
                        </span>
                        <span class="badge badge-info ml-1">
                            <i class="fas fa-users"></i> {{ $resultados['personas']->count() }} Personas
                        </span>
                        <span class="badge badge-warning ml-1">
                            <i class="fas fa-file-alt"></i> {{ $resultados['documentos']->count() }} Documentos
                        </span>
                    </div>
                </div>
            </div>

            @if($resultados['total'] > 0)
                <!-- Seccion Entrevistas -->
                @if($resultados['entrevistas']->count() > 0)
                <div class="card seccion-resultado entrevistas">
                    <div class="card-header seccion-header" data-toggle="collapse" data-target="#seccion-entrevistas">
                        <h3 class="card-title">
                            <i class="fas fa-microphone text-success"></i>
                            Entrevistas
                            <span class="badge badge-success contador-seccion ml-2">{{ $resultados['entrevistas']->count() }}</span>
                        </h3>
                        <div class="card-tools">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapse show" id="seccion-entrevistas">
                        <div class="card-body p-0">
                            @foreach($resultados['entrevistas'] as $entrevista)
                            <div class="resultado-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="icono-fuente entrevista">
                                                <i class="fas fa-microphone"></i>
                                            </span>
                                            <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="font-weight-bold">
                                                {{ $entrevista->entrevista_codigo }}
                                            </a>
                                            <span class="text-muted ml-2">-</span>
                                            <span class="ml-2">{{ \Illuminate\Support\Str::limit($entrevista->titulo, 60) }}</span>
                                        </div>

                                        <div class="text-muted small">
                                            <i class="far fa-calendar"></i> {{ $entrevista->entrevista_fecha ? \Carbon\Carbon::parse($entrevista->entrevista_fecha)->format('d/m/Y') : 'Sin fecha' }}
                                            @if($entrevista->rel_lugar_entrevista)
                                                <span class="ml-2"><i class="fas fa-map-marker-alt"></i> {{ $entrevista->rel_lugar_entrevista->descripcion }}</span>
                                            @endif
                                            @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                                                <span class="ml-2"><i class="fas fa-user"></i> {{ $entrevista->rel_entrevistador->rel_usuario->name }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small mt-1">
                                            @if($entrevista->rel_dependencia_origen)
                                                <span class="badge badge-light"><i class="fas fa-building"></i> {{ $entrevista->rel_dependencia_origen->descripcion }}</span>
                                            @endif
                                            @if($entrevista->rel_equipo_estrategia)
                                                <span class="badge badge-light ml-1"><i class="fas fa-users-cog"></i> {{ $entrevista->rel_equipo_estrategia->descripcion }}</span>
                                            @endif
                                            @if($entrevista->nombre_proyecto)
                                                <span class="badge badge-light ml-1"><i class="fas fa-project-diagram"></i> {{ \Illuminate\Support\Str::limit($entrevista->nombre_proyecto, 30) }}</span>
                                            @endif
                                        </div>

                                        <!-- Mostrar coincidencias -->
                                        <div class="mt-2">
                                            @if($entrevista->fuente_coincidencia === 'entrevista')
                                                @foreach($entrevista->coincidencias as $campo)
                                                    <span class="badge badge-light badge-coincidencia">
                                                        <i class="fas fa-check-circle text-success"></i> {{ $campo }}
                                                    </span>
                                                @endforeach
                                            @else
                                                <span class="badge badge-warning badge-coincidencia">
                                                    <i class="fas fa-file-alt"></i> Encontrado en documento(s)
                                                </span>
                                                @foreach($entrevista->coincidencias as $doc)
                                                    <div class="mt-1 ml-3 small">
                                                        <i class="fas fa-paperclip text-muted"></i>
                                                        <strong>{{ $doc['nombre'] }}</strong>
                                                        @if($doc['extracto'])
                                                            <div class="extracto-texto">{!! $doc['extracto'] !!}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('entrevistas.show', $entrevista->id_e_ind_fvt) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Seccion Personas -->
                @if($resultados['personas']->count() > 0)
                <div class="card seccion-resultado personas">
                    <div class="card-header seccion-header" data-toggle="collapse" data-target="#seccion-personas">
                        <h3 class="card-title">
                            <i class="fas fa-users text-info"></i>
                            Personas
                            <span class="badge badge-info contador-seccion ml-2">{{ $resultados['personas']->count() }}</span>
                        </h3>
                        <div class="card-tools">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapse show" id="seccion-personas">
                        <div class="card-body p-0">
                            @foreach($resultados['personas'] as $persona)
                            <div class="resultado-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="icono-fuente persona">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <a href="{{ route('personas.show', $persona->id_persona) }}" class="font-weight-bold">
                                                {{ $persona->nombre }} {{ $persona->apellido }}
                                            </a>
                                            @if($persona->nombre_identitario)
                                                <span class="text-muted ml-2">({{ $persona->nombre_identitario }})</span>
                                            @endif
                                        </div>

                                        <div class="text-muted small">
                                            @if($persona->num_documento)
                                                <span><i class="fas fa-id-card"></i> {{ $persona->num_documento }}</span>
                                            @endif
                                            @if($persona->rel_sexo)
                                                <span class="ml-2"><i class="fas fa-venus-mars"></i> {{ $persona->rel_sexo->descripcion }}</span>
                                            @endif
                                            @if($persona->rel_etnia)
                                                <span class="ml-2"><i class="fas fa-users"></i> {{ $persona->rel_etnia->descripcion }}</span>
                                            @endif
                                            @if($persona->num_entrevistas > 0)
                                                <span class="ml-2 badge badge-secondary">
                                                    <i class="fas fa-microphone"></i> {{ $persona->num_entrevistas }} entrevista(s)
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Mostrar coincidencias -->
                                        <div class="mt-2">
                                            @foreach($persona->coincidencias as $campo)
                                                <span class="badge badge-light badge-coincidencia">
                                                    <i class="fas fa-check-circle text-info"></i> {{ $campo }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="ml-3">
                                        <a href="{{ route('personas.show', $persona->id_persona) }}" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye"></i> Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Seccion Documentos -->
                @if($resultados['documentos']->count() > 0)
                <div class="card seccion-resultado documentos">
                    <div class="card-header seccion-header" data-toggle="collapse" data-target="#seccion-documentos">
                        <h3 class="card-title">
                            <i class="fas fa-file-alt text-warning"></i>
                            Documentos
                            <span class="badge badge-warning contador-seccion ml-2">{{ $resultados['documentos']->count() }}</span>
                        </h3>
                        <div class="card-tools">
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                    <div class="collapse show" id="seccion-documentos">
                        <div class="card-body p-0">
                            @foreach($resultados['documentos'] as $documento)
                            <div class="resultado-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="icono-fuente documento">
                                                @php
                                                    $ext = strtolower(pathinfo($documento->nombre_original, PATHINFO_EXTENSION));
                                                    $icono = match($ext) {
                                                        'pdf' => 'fa-file-pdf',
                                                        'doc', 'docx' => 'fa-file-word',
                                                        'txt' => 'fa-file-alt',
                                                        'mp3', 'wav', 'ogg' => 'fa-file-audio',
                                                        'mp4', 'avi', 'mov' => 'fa-file-video',
                                                        'jpg', 'jpeg', 'png', 'gif' => 'fa-file-image',
                                                        default => 'fa-file'
                                                    };
                                                @endphp
                                                <i class="fas {{ $icono }}"></i>
                                            </span>
                                            <span class="font-weight-bold">{{ $documento->nombre_original }}</span>
                                            @if($documento->rel_tipo)
                                                <span class="badge badge-secondary ml-2">{{ $documento->rel_tipo->descripcion }}</span>
                                            @endif
                                        </div>

                                        <div class="text-muted small">
                                            @if($documento->rel_entrevista)
                                                <span>
                                                    <i class="fas fa-microphone"></i>
                                                    <a href="{{ route('entrevistas.show', $documento->rel_entrevista->id_e_ind_fvt) }}">
                                                        {{ $documento->rel_entrevista->entrevista_codigo }}
                                                    </a>
                                                </span>
                                            @endif
                                            <span class="ml-2"><i class="fas fa-hdd"></i> {{ number_format($documento->tamano / 1024, 1) }} KB</span>
                                            <span class="ml-2"><i class="far fa-calendar"></i> {{ $documento->created_at ? $documento->created_at->format('d/m/Y') : '' }}</span>
                                        </div>

                                        <!-- Mostrar coincidencias -->
                                        <div class="mt-2">
                                            @foreach($documento->coincidencias as $campo)
                                                <span class="badge badge-light badge-coincidencia">
                                                    <i class="fas fa-check-circle text-warning"></i> {{ $campo }}
                                                </span>
                                            @endforeach
                                        </div>

                                        <!-- Extracto de texto encontrado -->
                                        @if($documento->extracto)
                                        <div class="extracto-texto mt-2">
                                            {!! $documento->extracto !!}
                                        </div>
                                        @endif
                                    </div>
                                    <div class="ml-3">
                                        @if($documento->rel_entrevista)
                                        <a href="{{ route('adjuntos.gestionar', $documento->rel_entrevista->id_e_ind_fvt) }}" class="btn btn-sm btn-outline-warning">
                                            <i class="fas fa-folder-open"></i> Ir
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            @else
                <!-- Sin resultados -->
                <div class="card">
                    <div class="card-body sin-resultados">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h5>No se encontraron resultados</h5>
                        <p>No hay coincidencias para "<strong>{{ $termino }}</strong>" en entrevistas, personas o documentos.</p>
                        <p class="small text-muted">Intente con otros terminos de busqueda.</p>
                    </div>
                </div>
            @endif

        @else
            <!-- Estado inicial -->
            <div class="card">
                <div class="card-body sin-resultados">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>Realice una busqueda</h5>
                    <p class="text-muted">
                        Ingrese al menos 2 caracteres para buscar en:
                    </p>
                    <div class="row justify-content-center mt-4">
                        <div class="col-md-3 text-center">
                            <div class="icono-fuente entrevista mx-auto mb-2" style="width:48px;height:48px;font-size:1.5rem;">
                                <i class="fas fa-microphone"></i>
                            </div>
                            <strong>Entrevistas</strong>
                            <p class="small text-muted">Codigos, titulos, anotaciones y contenido de documentos adjuntos</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="icono-fuente persona mx-auto mb-2" style="width:48px;height:48px;font-size:1.5rem;">
                                <i class="fas fa-user"></i>
                            </div>
                            <strong>Personas</strong>
                            <p class="small text-muted">Nombres, apellidos, alias, nombres identitarios y documentos</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="icono-fuente documento mx-auto mb-2" style="width:48px;height:48px;font-size:1.5rem;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <strong>Documentos</strong>
                            <p class="small text-muted">Nombres de archivos y texto extraido de PDFs y transcripciones</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
// Toggle icono chevron al colapsar/expandir secciones
$('.seccion-header').on('click', function() {
    var icon = $(this).find('.card-tools i');
    if ($(this).attr('aria-expanded') === 'true') {
        icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
});

// Actualizar icono cuando se colapsa/expande
$('.collapse').on('shown.bs.collapse', function() {
    $(this).prev('.seccion-header').find('.card-tools i')
        .removeClass('fa-chevron-down').addClass('fa-chevron-up');
});

$('.collapse').on('hidden.bs.collapse', function() {
    $(this).prev('.seccion-header').find('.card-tools i')
        .removeClass('fa-chevron-up').addClass('fa-chevron-down');
});
</script>
@endsection
