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
                <a href="{{ route('importacion.create') }}" class="btn btn-primary">
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
                                <a href="{{ route('importacion.mapear', $imp->id_importacion) }}" class="btn btn-xs btn-info">
                                    <i class="fas fa-map"></i> Mapear
                                </a>
                                @elseif(in_array($imp->estado, ['confirmado']))
                                <a href="{{ route('importacion.confirmar', $imp->id_importacion) }}" class="btn btn-xs btn-warning">
                                    <i class="fas fa-check"></i> Confirmar
                                </a>
                                @elseif(in_array($imp->estado, ['procesando', 'completado', 'con_errores']))
                                <a href="{{ route('importacion.monitor', $imp->id_importacion) }}" class="btn btn-xs btn-secondary">
                                    <i class="fas fa-chart-line"></i> Monitor
                                </a>
                                @endif

                                @if($imp->estado !== 'completado')
                                <form method="POST" action="{{ route('importacion.destroy', $imp->id_importacion) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('¿Borrar importación #{{ $imp->id_importacion }}? Se eliminarán los datos de la sesión (no los expedientes ya creados).')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger ml-1" title="Cancelar y borrar">
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

    </div>
</section>
@endsection
