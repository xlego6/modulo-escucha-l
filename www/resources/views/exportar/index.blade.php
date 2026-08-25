@extends('layouts.app')

@section('title', 'Exportar datos')
@section('content_header', 'Exportar datos')

@section('content')
<div class="row">

    {{-- ================================================================
         COLUMNA PRINCIPAL — Entrevistas
         ================================================================ --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-microphone mr-2 text-muted"></i>Exportar entrevistas</h3>
            </div>
            <form action="{{ route('exportar.entrevistas') }}" method="POST">
                @csrf
                <div class="card-body pb-1">

                    <small class="filter-hint d-block">
                        <i class="fas fa-info-circle mr-1"></i>En los filtros de lista puede seleccionar varias opciones: haga clic en cada una para agregarla.
                    </small>

                    {{-- Códigos --}}
                    <p class="filter-section-label">
                        <i class="fas fa-hashtag mr-1"></i>Códigos de entrevista
                    </p>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <textarea class="form-control form-control-sm" name="codigos" rows="2"
                                    placeholder="DAV-0001-001, DAV-0001-002 — coma, punto y coma o salto de línea"></textarea>
                                <small class="text-muted">Si se indican códigos, los demás filtros se ignoran.</small>
                            </div>
                        </div>
                    </div>

                    {{-- Fechas de toma --}}
                    <p class="filter-section-label mt-1">
                        <i class="fas fa-calendar-alt mr-1"></i>Fecha de toma
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Desde</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_desde">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Hasta</label>
                                <input type="date" class="form-control form-control-sm" name="fecha_hasta">
                            </div>
                        </div>
                    </div>

                    {{-- Fechas de carga --}}
                    <p class="filter-section-label mt-1">
                        <i class="fas fa-cloud-upload-alt mr-1"></i>Fecha de carga al sistema
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Desde</label>
                                <input type="date" class="form-control form-control-sm" name="carga_desde">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Hasta</label>
                                <input type="date" class="form-control form-control-sm" name="carga_hasta">
                            </div>
                        </div>
                    </div>

                    {{-- Lugar y entrevistador --}}
                    <p class="filter-section-label mt-1">
                        <i class="fas fa-map-marker-alt mr-1"></i>Lugar y entrevistador
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Departamento de toma</label>
                                <select class="form-control form-control-sm select2-exportar" name="id_territorio[]" multiple data-placeholder="-- Todos --">
                                    @foreach($territorios as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Entrevistador</label>
                                <select class="form-control form-control-sm select2-exportar" name="id_entrevistador[]" multiple data-placeholder="-- Todos --">
                                    @foreach($entrevistadores as $id => $nombre)
                                        <option value="{{ $id }}">{{ $nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Tipo de testimonio --}}
                    <p class="filter-section-label mt-1">
                        <i class="fas fa-file-alt mr-1"></i>Tipo de testimonio
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Dependencia de origen</label>
                                <select class="form-control form-control-sm select2-exportar" name="id_dependencia_origen[]" multiple data-placeholder="-- Todas --">
                                    @foreach($dependencias as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Tipo de testimonio</label>
                                <select class="form-control form-control-sm select2-exportar" name="id_tipo_testimonio[]" multiple data-placeholder="-- Todos --">
                                    @foreach($tipos_testimonio as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Adjuntos --}}
                    <p class="filter-section-label mt-1">
                        <i class="fas fa-paperclip mr-1"></i>Adjuntos
                    </p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Tiene adjuntos</label>
                                <select class="form-control form-control-sm" name="tiene_adjuntos">
                                    <option value="">— Todos —</option>
                                    <option value="1">Con adjuntos</option>
                                    <option value="0">Sin adjuntos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="filter-label">Tipo de adjunto</label>
                                <select class="form-control form-control-sm select2-exportar" name="id_tipo_adjunto[]" multiple data-placeholder="-- Todos --">
                                    @foreach($tipos_adjunto as $id => $descripcion)
                                        <option value="{{ $id }}">{{ $descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-excel mr-1"></i>Descargar Excel de entrevistas
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================================================================
         COLUMNA LATERAL — Otras exportaciones
         ================================================================ --}}
    <div class="col-lg-4">

        {{-- Personas --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users mr-2 text-muted"></i>Exportar personas</h3>
            </div>
            <form action="{{ route('exportar.personas') }}" method="POST">
                @csrf
                <div class="card-body pb-1">
                    <small class="filter-hint d-block">
                        <i class="fas fa-info-circle mr-1"></i>Puede seleccionar varias opciones: haga clic en cada una para agregarla.
                    </small>
                    <div class="form-group">
                        <label class="filter-label">Sexo</label>
                        <select class="form-control form-control-sm select2-exportar" name="id_sexo[]" multiple data-placeholder="-- Todos --">
                            @foreach($sexos as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="filter-label">Grupo étnico</label>
                        <select class="form-control form-control-sm select2-exportar" name="id_etnia[]" multiple data-placeholder="-- Todos --">
                            @foreach($etnias as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="filter-label">Departamento de residencia</label>
                        <select class="form-control form-control-sm select2-exportar" name="id_lugar_residencia_depto[]" multiple data-placeholder="-- Todos --">
                            @foreach($territorios as $id => $descripcion)
                                <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-excel mr-1"></i>Descargar Excel de personas
                    </button>
                </div>
            </form>
        </div>

        {{-- Usuarios (solo Admin) --}}
        @if(Auth::user()->id_nivel == 1)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-shield mr-2 text-muted"></i>Exportar usuarios</h3>
            </div>
            <form action="{{ route('exportar.usuarios') }}" method="POST">
                @csrf
                <div class="card-body">
                    <p class="text-muted small mb-0">
                        Listado completo de usuarios: nombre, correo, rol, dependencia, fecha de registro y texto de compromisos firmados.
                    </p>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-excel mr-1"></i>Descargar Excel de usuarios
                    </button>
                </div>
            </form>
        </div>

        {{-- Centro de control / Asignaciones (solo Admin y Líder) --}}
        @if(Auth::user()->id_nivel <= 2)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tasks mr-2 text-muted"></i>Exportar asignaciones</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    Todas las asignaciones de transcripción: transcriptor, audio, estado, fechas, tiempo activo de edición, calificación y revisión.
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('procesamientos.exportar-asignaciones') }}" class="btn btn-primary">
                    <i class="fas fa-file-excel mr-1"></i>Descargar Excel de asignaciones
                </a>
            </div>
        </div>
        @endif

        {{-- Traza (solo Admin) --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2 text-muted"></i>Exportar traza de actividad</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-0">
                    La traza se exporta desde su propia vista, donde se pueden aplicar filtros antes de descargar.
                </p>
            </div>
            <div class="card-footer">
                <a href="{{ route('traza.index') }}" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i>Ir a Traza
                </a>
            </div>
        </div>
        @endif

    </div>

</div>
@endsection

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet">
<style>
.filter-section-label {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6c757d;
    border-bottom: 1px solid #dee2e6;
    padding-bottom: .25rem;
    margin-bottom: .75rem;
}
.filter-label {
    font-size: .8rem;
    color: #495057;
    margin-bottom: .2rem;
}
.filter-hint {
    color: #6c757d;
    margin-top: -.35rem;
    margin-bottom: .5rem;
}
.select2-exportar + .select2-container--bootstrap4 .select2-selection {
    font-size: .875rem;
}
.select2-exportar + .select2-container--bootstrap4 .select2-selection__rendered {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .25rem;
}
.select2-exportar + .select2-container--bootstrap4 .select2-selection__choice {
    font-size: .8125rem;
    float: none;
    margin: 0;
}
.select2-exportar + .select2-container--bootstrap4 .select2-search--inline {
    flex: 1 1 auto;
    width: auto !important;
}
.select2-exportar + .select2-container--bootstrap4 .select2-search__field {
    width: 100% !important;
    text-align: center;
}
.select2-exportar + .select2-container--bootstrap4 .select2-search__field::placeholder {
    color: #6c757d;
    text-align: center;
}
</style>
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function() {
    $('.select2-exportar').select2({
        theme: 'bootstrap4',
        width: '100%',
        allowClear: true,
        placeholder: function() {
            return $(this).data('placeholder');
        }
    });
});
</script>
@endsection
