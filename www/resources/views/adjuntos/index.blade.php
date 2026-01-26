@extends('layouts.app')

@section('title', 'Archivos Adjuntos')
@section('content_header', 'Todos los Archivos Adjuntos')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filtros de busqueda</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('adjuntos.index') }}" class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Codigo Entrevista</label>
                    <input type="text" name="codigo" class="form-control form-control-sm" value="{{ request('codigo') }}" placeholder="VI-0001-001">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Nombre Archivo</label>
                    <input type="text" name="nombre" class="form-control form-control-sm" value="{{ request('nombre') }}" placeholder="Buscar por nombre...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tipo</label>
                    <select name="id_tipo" class="form-control form-control-sm">
                        @foreach($tipos as $id => $nombre)
                            <option value="{{ $id }}" {{ request('id_tipo') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-info btn-sm btn-block">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Archivos ({{ $adjuntos->total() }} registros)</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th style="width: 40px"></th>
                    <th>Nombre</th>
                    <th style="width: 120px">Entrevista</th>
                    <th style="width: 100px">Tipo</th>
                    <th style="width: 100px">Tamano</th>
                    <th style="width: 120px">Fecha</th>
                    <th style="width: 120px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($adjuntos as $adjunto)
                <tr>
                    <td class="text-center">
                        @if($adjunto->es_audio)
                            <i class="fas fa-file-audio fa-lg text-info"></i>
                        @elseif($adjunto->es_video)
                            <i class="fas fa-file-video fa-lg text-danger"></i>
                        @elseif($adjunto->es_documento)
                            <i class="fas fa-file-pdf fa-lg text-warning"></i>
                        @else
                            <i class="fas fa-file fa-lg text-secondary"></i>
                        @endif
                    </td>
                    <td>
                        <strong>{{ \Illuminate\Support\Str::limit($adjunto->nombre_original, 40) }}</strong>
                        <br><small class="text-muted">{{ $adjunto->tipo_mime }}</small>
                    </td>
                    <td>
                        @if($adjunto->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $adjunto->rel_entrevista->id_e_ind_fvt) }}">
                                {{ $adjunto->rel_entrevista->entrevista_codigo }}
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        @if($adjunto->rel_tipo)
                            <span class="badge badge-info">{{ $adjunto->rel_tipo->descripcion }}</span>
                        @else
                            <span class="badge badge-secondary">-</span>
                        @endif
                    </td>
                    <td>{{ $adjunto->fmt_tamano }}</td>
                    <td>{{ $adjunto->created_at ? \Carbon\Carbon::parse($adjunto->created_at)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            @if($adjunto->rel_entrevista)
                            <a href="{{ route('adjuntos.gestionar', $adjunto->rel_entrevista->id_e_ind_fvt) }}" class="btn btn-secondary" title="Ir a entrevista">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                            @endif
                            <a href="{{ route('adjuntos.descargar', $adjunto->id_adjunto) }}" class="btn btn-success" title="Descargar">
                                <i class="fas fa-download"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="fas fa-folder-open fa-3x mb-3"></i>
                        <p>No se encontraron archivos</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($adjuntos->hasPages())
    <div class="card-footer">
        {{ $adjuntos->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
