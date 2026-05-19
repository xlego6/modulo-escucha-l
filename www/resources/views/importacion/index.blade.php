@extends('layouts.app')

@section('title', 'Importaciones masivas')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Importación masiva de expedientes</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ asset('plantillas/plantilla_crear.csv') }}" download class="btn btn-outline-success btn-sm mr-1">
                    <i class="fas fa-file-csv mr-1"></i> Plantilla carga
                </a>
                <a href="{{ asset('plantillas/plantilla_actualizar.csv') }}" download class="btn btn-outline-secondary btn-sm mr-2">
                    <i class="fas fa-file-csv mr-1"></i> Plantilla actualización
                </a>
                <a href="{{ route('importacion.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Nueva importación
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        @endif

        @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}
            <hr class="my-2">
            <small>Para resolver el problema de permisos, corre en el servidor:
            <code>sudo chown -R www-data:www-data /var/www/storage/app/importaciones/transcripciones/</code></small>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        @endif

        @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            {{ $errors->first() }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Historial de importaciones</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Archivo CSV</th>
                            <th>Usuario</th>
                            <th>Expedientes</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($importaciones as $imp)
                        <tr>
                            <td>{{ $imp->id_importacion }}</td>
                            <td>
                                <i class="fas fa-file-csv text-success mr-1"></i>
                                {{ $imp->nombre_archivo }}
                            </td>
                            <td>{{ $imp->rel_usuario->name ?? '—' }}</td>
                            <td>
                                <span class="text-success">{{ $imp->procesados }}</span>
                                /
                                <span class="text-danger">{{ $imp->con_error }}</span>
                                /
                                {{ $imp->total_expedientes }}
                                <small class="text-muted">(ok / err / total)</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $imp->clase_estado }}">
                                    {{ $imp->etiqueta_estado }}
                                </span>
                            </td>
                            <td>{{ $imp->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                @if(in_array($imp->estado, ['mapeando']))
                                <a href="{{ route('importacion.mapear', $imp->id_importacion) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-map"></i> Mapear
                                </a>
                                @elseif(in_array($imp->estado, ['confirmado']))
                                <a href="{{ route('importacion.confirmar', $imp->id_importacion) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-check"></i> Confirmar
                                </a>
                                @elseif(in_array($imp->estado, ['procesando', 'completado', 'con_errores']))
                                <a href="{{ route('importacion.monitor', $imp->id_importacion) }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-chart-line"></i> Monitor
                                </a>
                                @endif

                                @if($imp->estado !== 'completado')
                                <form method="POST" action="{{ route('importacion.destroy', $imp->id_importacion) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Borrar importación #{{ $imp->id_importacion }}? Se eliminarán los datos de la sesión (no los expedientes ya creados).')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger ml-1" title="Cancelar y borrar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No hay importaciones registradas.
                                <a href="{{ route('importacion.create') }}">Iniciar una nueva</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($importaciones->hasPages())
            <div class="card-footer">
                {{ $importaciones->links() }}
            </div>
            @endif
        </div>

        {{-- Gestión de carpeta de transcripciones --}}
        <div class="card card-outline card-secondary mt-4">
            <div class="card-header py-2">
                <h3 class="card-title">
                    <i class="fas fa-folder-open mr-2"></i>Carpeta de archivos en el servidor
                    <small class="text-muted ml-2" style="font-size:0.78rem; font-weight:normal;">
                        storage/app/importaciones/transcripciones/
                    </small>
                </h3>
            </div>
            <div class="card-body">
                <div class="row">

                    {{-- Subir archivos --}}
                    <div class="col-md-8">
                        <p class="text-muted text-sm mb-2">
                            Sube audios, videos o transcripciones (.txt) desde tu computador directamente a la carpeta del servidor.
                        </p>
                        <form method="POST" action="{{ route('importacion.subir_archivos') }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="archivos"
                                           name="archivos[]" multiple required>
                                    <label class="custom-file-label" for="archivos">
                                        Seleccionar archivos...
                                    </label>
                                </div>
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-upload mr-1"></i> Subir al servidor
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Puedes seleccionar múltiples archivos. Tamaño máximo: 2 GB por archivo.</small>
                        </form>
                    </div>

                    {{-- Vaciar carpeta --}}
                    <div class="col-md-4 border-left">
                        <p class="text-muted text-sm mb-2">
                            Elimina todo el contenido de la carpeta del servidor (los archivos ya importados están a salvo en la base de datos).
                        </p>
                        <form method="POST" action="{{ route('importacion.vaciar_carpeta') }}"
                              onsubmit="return confirm('¿Vaciar la carpeta de transcripciones del servidor? Esta acción no se puede deshacer.')">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-trash mr-1"></i> Vaciar carpeta del servidor
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</section>

@section('js')
<script>
// Mostrar nombres de archivos seleccionados en el input
document.getElementById('archivos').addEventListener('change', function () {
    const label = this.nextElementSibling;
    const count = this.files.length;
    label.textContent = count === 1 ? this.files[0].name : count + ' archivos seleccionados';
});
</script>
@endsection

@endsection
