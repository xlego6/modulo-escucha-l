@extends('layouts.app')

@section('title', 'Confirmar importación #' . $importacion->id_importacion)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Importación #{{ $importacion->id_importacion }} — Paso 3 de 4</h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-file-csv text-success"></i> {{ $importacion->nombre_archivo }}
                </p>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @include('importacion._pasos', ['paso_actual' => 3])

    @php
        use Illuminate\Support\Str;
        $sinArchivos    = $expedientes->filter(fn($e) => collect($e->archivos)->where('existe', false)->count() > 0);
        $conAdvertencias = $expedientes->filter(fn($e) => count($e->advertencias) > 0);
        $archivosAConvertir = $expedientes->flatMap(fn($e) => collect($e->archivos)->where('convertir', true));
    @endphp

    {{-- Resumen --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-folder-open"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total expedientes</span>
                    <span class="info-box-number">{{ $expedientes->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Con advertencias</span>
                    <span class="info-box-number">{{ $conAdvertencias->count() }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-unlink"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Archivos no encontrados</span>
                    <span class="info-box-number">
                        {{ $expedientes->sum(fn($e) => collect($e->archivos)->where('existe', false)->count()) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-compress-arrows-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">A convertir a m4a</span>
                    <span class="info-box-number">{{ $archivosAConvertir->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla de expedientes --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Vista previa de expedientes a crear</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>ID CSV</th>
                        <th>Título</th>
                        <th>Personas</th>
                        <th>Archivos</th>
                        <th>Advertencias</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expedientes as $exp)
                    @php
                        $cols        = $exp->datos_csv['cols'] ?? [];
                        $personas    = $exp->datos_csv['personas'] ?? [];
                        $archivosExp = collect($exp->archivos);
                        $tieneError  = $archivosExp->where('existe', false)->count() > 0;
                    @endphp
                    <tr class="{{ count($exp->advertencias) > 0 ? 'table-warning' : '' }}">
                        <td><strong>{{ $exp->id_csv }}</strong></td>
                        <td>{{ Str::limit($cols[8] ?? '—', 60) }}</td>
                        <td>
                            @foreach($personas as $p)
                            <span class="badge badge-light">{{ Str::limit($p['nombre'], 25) }}</span>
                            @endforeach
                            @if(empty($personas))
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @foreach($archivosExp as $arch)
                            @php
                                $iconos = [310 => 'fa-film', 311 => 'fa-file-contract', 313 => 'fa-file-alt', 315 => 'fa-paperclip'];
                                $icono  = $iconos[$arch['id_tipo']] ?? 'fa-file';
                                $color  = $arch['existe'] ? ($arch['es_directorio'] ? 'text-warning' : 'text-success') : 'text-danger';
                                $titulo = $arch['existe'] ? ($arch['es_directorio'] ? 'Carpeta (no es archivo)' : ($arch['convertir'] ? 'Se convertirá a m4a' : 'OK')) : 'Archivo no encontrado';
                            @endphp
                            <i class="fas {{ $icono }} {{ $color }} mr-1" title="{{ $titulo }}"></i>
                            @endforeach
                            @if($archivosExp->isEmpty())
                            <span class="text-muted small">sin archivos</span>
                            @endif
                        </td>
                        <td>
                            @foreach($exp->advertencias as $adv)
                            <div class="small text-warning"><i class="fas fa-exclamation-triangle"></i> {{ $adv }}</div>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Leyenda --}}
    <div class="alert alert-info small">
        <strong>Leyenda de archivos:</strong>
        <i class="fas fa-film text-success"></i> Audio/Video ·
        <i class="fas fa-file-contract text-success"></i> Consentimiento ·
        <i class="fas fa-file-alt text-success"></i> Transcripción ·
        <i class="fas fa-paperclip text-success"></i> Otros ·
        <span class="text-danger"><i class="fas fa-times"></i> No encontrado</span> ·
        <span class="text-warning"><i class="fas fa-exclamation-triangle"></i> Es carpeta</span>
    </div>

    {{-- Botones de acción --}}
    <div class="d-flex justify-content-between mb-4">
        <a href="{{ route('importacion.mapear', $importacion->id_importacion) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver a mapeos
        </a>

        <form method="POST" action="{{ route('importacion.procesar', $importacion->id_importacion) }}"
              onsubmit="return confirm('¿Confirmar el inicio del procesamiento? Se crearán {{ $expedientes->count() }} expedientes.')">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">
                <i class="fas fa-play"></i> Iniciar procesamiento
            </button>
        </form>
    </div>

</div>
</section>
@endsection
