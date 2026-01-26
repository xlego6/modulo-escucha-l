@extends('layouts.app')

@section('title', 'Procesamientos')
@section('content_header', 'Centro de Procesamientos')

@section('content')
<div class="row">
    <!-- Estadísticas generales -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($stats['total_entrevistas']) }}</h3>
                <p>Total Entrevistas</p>
            </div>
            <div class="icon">
                <i class="fas fa-microphone"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($stats['transcritas']) }}</h3>
                <p>Transcritas</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($stats['con_entidades']) }}</h3>
                <p>Con Entidades</p>
            </div>
            <div class="icon">
                <i class="fas fa-tags"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format($stats['anonimizadas']) }}</h3>
                <p>Anonimizadas</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-secret"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Transcripción Automatizada -->
    <div class="col-md-6">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-robot mr-2"></i>Transcripcion Automatizada
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Convierte archivos de audio a texto utilizando tecnologia de reconocimiento de voz (WhisperX).
                    Incluye diarizacion para identificar diferentes hablantes.
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.transcripcion') }}" class="btn btn-primary">
                    <i class="fas fa-play mr-2"></i>Iniciar Transcripcion
                </a>
            </div>
        </div>
    </div>

    <!-- Edición de Transcripciones -->
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>Edicion de Transcripciones
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Revise y corrija las transcripciones automaticas. Incluye reproductor de audio sincronizado
                    con el texto para facilitar la edicion.
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.edicion') }}" class="btn btn-success">
                    <i class="fas fa-edit mr-2"></i>Editar Transcripciones
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Detección de Entidades -->
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tags mr-2"></i>Deteccion de Entidades
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Identifica automaticamente personas, lugares, organizaciones, fechas y otros elementos
                    relevantes en las transcripciones usando procesamiento de lenguaje natural (spaCy).
                </p>
                <div class="row">
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <li><span class="badge badge-primary mr-2">PER</span>Personas</li>
                            <li><span class="badge badge-success mr-2">LOC</span>Lugares</li>
                            <li><span class="badge badge-info mr-2">ORG</span>Organizaciones</li>
                            <li><span class="badge badge-secondary mr-2">DATE</span>Fechas</li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <ul class="list-unstyled">
                            <li><span class="badge badge-warning mr-2">EVENT</span>Eventos</li>
                            <li><span class="badge badge-danger mr-2">GUN</span>Armas</li>
                            <li><span class="badge badge-dark mr-2">MISC</span>Otros</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.entidades') }}" class="btn btn-warning">
                    <i class="fas fa-search mr-2"></i>Detectar Entidades
                </a>
            </div>
        </div>
    </div>

    <!-- Anonimización -->
    <div class="col-md-6">
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-secret mr-2"></i>Anonimizacion
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Genera versiones publicas de los testimonios con informacion sensible anonimizada.
                    Protege la identidad de los testimoniantes y personas mencionadas.
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.anonimizacion') }}" class="btn btn-danger">
                    <i class="fas fa-mask mr-2"></i>Anonimizar Testimonios
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Cola de Trabajos -->
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2"></i>Cola de Trabajos</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-clock text-info mr-2"></i>En espera</span>
                    <span class="badge badge-info badge-pill px-3">{{ $stats['trabajos_en_cola'] ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span><i class="fas fa-cog fa-spin text-warning mr-2"></i>Procesando</span>
                    <span class="badge badge-warning badge-pill px-3">{{ $stats['trabajos_procesando'] ?? 0 }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-check-circle text-success mr-2"></i>Transcritas</span>
                    <span class="badge badge-success badge-pill px-3">{{ $stats['transcritas'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Flujo de Trabajo -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>Flujo de Procesamiento</h3>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <i class="fas fa-microphone fa-3x text-primary mb-3"></i>
                    <h5>1. Audio</h5>
                    <p class="text-muted small mb-0">Entrevista grabada</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <i class="fas fa-file-alt fa-3x text-success mb-3"></i>
                    <h5>2. Transcripcion</h5>
                    <p class="text-muted small mb-0">Audio a texto</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <i class="fas fa-tags fa-3x text-warning mb-3"></i>
                    <h5>3. Entidades</h5>
                    <p class="text-muted small mb-0">Identificacion NER</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 border rounded">
                    <i class="fas fa-user-secret fa-3x text-danger mb-3"></i>
                    <h5>4. Anonimizacion</h5>
                    <p class="text-muted small mb-0">Version publica</p>
                </div>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-center">
                <i class="fas fa-arrow-right text-muted mx-4 d-none d-md-inline"></i>
                <i class="fas fa-arrow-right text-muted mx-4 d-none d-md-inline"></i>
                <i class="fas fa-arrow-right text-muted mx-4 d-none d-md-inline"></i>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Inicializacion
});
</script>
@endsection
