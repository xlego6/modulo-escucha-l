@extends('layouts.app')

@section('title', 'Exportar Datos')
@section('content_header', 'Exportar Datos a Excel')

@section('content')
<div class="row">
    <!-- Exportar Entrevistas -->
    <div class="col-lg-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microphone mr-2"></i>Exportar Entrevistas</h3>
            </div>
            <form action="{{ route('exportar.entrevistas') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Seccion: Filtros por Fecha -->
                        <div class="col-12">
                            <h6 class="text-muted border-bottom pb-2 mb-3">
                                <i class="fas fa-calendar-alt mr-2"></i>Filtros por Fecha
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_desde">Fecha Desde</label>
                                <input type="date" class="form-control" id="fecha_desde" name="fecha_desde">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="fecha_hasta">Fecha Hasta</label>
                                <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta">
                            </div>
                        </div>

                        <!-- Seccion: Filtros por Ubicacion y Entrevistador -->
                        <div class="col-12">
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-2">
                                <i class="fas fa-map-marker-alt mr-2"></i>Ubicacion y Entrevistador
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_territorio">Departamento</label>
                                <select class="form-control" id="id_territorio" name="id_territorio">
                                    @foreach($territorios as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_entrevistador">Entrevistador</label>
                                <select class="form-control" id="id_entrevistador" name="id_entrevistador">
                                    @foreach($entrevistadores as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Seccion: Filtros por Tipo de Testimonio -->
                        <div class="col-12">
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-2">
                                <i class="fas fa-file-alt mr-2"></i>Tipo de Testimonio
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_dependencia_origen">Dependencia de Origen</label>
                                <select class="form-control" id="id_dependencia_origen" name="id_dependencia_origen">
                                    @foreach($dependencias as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_tipo_testimonio">Tipo de Testimonio</label>
                                <select class="form-control" id="id_tipo_testimonio" name="id_tipo_testimonio">
                                    @foreach($tipos_testimonio as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Seccion: Filtros por Adjuntos -->
                        <div class="col-12">
                            <h6 class="text-muted border-bottom pb-2 mb-3 mt-2">
                                <i class="fas fa-paperclip mr-2"></i>Filtros por Adjuntos
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tiene_adjuntos">Tiene Adjuntos</label>
                                <select class="form-control" id="tiene_adjuntos" name="tiene_adjuntos">
                                    <option value="">-- Todos --</option>
                                    <option value="1">Si - Con adjuntos</option>
                                    <option value="0">No - Sin adjuntos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="id_tipo_adjunto">Tipo de Adjunto</label>
                                <select class="form-control" id="id_tipo_adjunto" name="id_tipo_adjunto">
                                    @foreach($tipos_adjunto as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Filtra entrevistas que contengan este tipo de adjunto</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-file-excel mr-2"></i>Descargar Excel de Entrevistas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Exportar Personas -->
    <div class="col-lg-4">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2"></i>Exportar Personas</h3>
            </div>
            <form action="{{ route('exportar.personas') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="id_sexo">Sexo</label>
                        <select class="form-control" id="id_sexo" name="id_sexo">
                            @foreach($sexos as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_etnia">Grupo Etnico</label>
                        <select class="form-control" id="id_etnia" name="id_etnia">
                            @foreach($etnias as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="id_lugar_residencia_depto">Departamento de Residencia</label>
                        <select class="form-control" id="id_lugar_residencia_depto" name="id_lugar_residencia_depto">
                            @foreach($territorios as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-file-excel mr-2"></i>Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Contenido de los Archivos Excel</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-microphone text-primary mr-2"></i>Exportacion de Entrevistas</h6>
                        <p class="text-muted">El archivo Excel incluye los siguientes campos organizados por secciones:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Datos Tecnicos:</strong>
                                <span class="text-muted">ID, Codigo, Fecha de creacion</span>
                            </li>
                            <li class="mb-2">
                                <strong>Datos Testimoniales:</strong>
                                <span class="text-muted">Titulo, Dependencia, Tipo testimonio, Formato(s), Num. testimoniantes, Lugar de toma, Modalidad, Idioma, Fechas de toma, Necesidades reparacion, Areas compatibles, Anexos, Observaciones, Entrevistador</span>
                            </li>
                            <li class="mb-2">
                                <strong>Testimoniantes:</strong>
                                <span class="text-muted">Nombres, Tipo (victima/testigo/familiar), Estado de consentimiento</span>
                            </li>
                            <li class="mb-2">
                                <strong>Contenido:</strong>
                                <span class="text-muted">Fechas de hechos, Poblaciones mencionadas, Ocupaciones, Hechos victimizantes, Responsables colectivos e individuales, Temas abordados</span>
                            </li>
                            <li class="mb-2">
                                <strong>Adjuntos:</strong>
                                <span class="text-muted">Tiene adjuntos, Cantidad total, Tipos de adjuntos, Cantidad por tipo (audio/video/documento), Duracion total</span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users text-success mr-2"></i>Exportacion de Personas</h6>
                        <p class="text-muted">El archivo Excel incluye:</p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <strong>Identificacion:</strong>
                                <span class="text-muted">Nombres, Apellidos, Tipo documento, Numero documento</span>
                            </li>
                            <li class="mb-2">
                                <strong>Datos personales:</strong>
                                <span class="text-muted">Fecha y lugar de nacimiento, Sexo, Etnia, Ocupacion</span>
                            </li>
                            <li class="mb-2">
                                <strong>Contacto:</strong>
                                <span class="text-muted">Lugar de residencia, Telefono, Email</span>
                            </li>
                        </ul>

                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Nota de seguridad:</strong> Todas las exportaciones quedan registradas en la traza de actividad del sistema. Maneje la informacion con responsabilidad.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
