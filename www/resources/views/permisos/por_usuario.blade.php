@extends('layouts.app')

@section('title', 'Permisos del Usuario')
@section('content_header', 'Permisos del Usuario')

@section('content')
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-user mr-2"></i>
            Usuario: {{ $entrevistador->rel_usuario->name ?? 'N/A' }}
        </h3>
    </div>
    <div class="card-body">
        <p><strong>Correo:</strong> {{ $entrevistador->rel_usuario->email ?? 'N/A' }}</p>
        <p><strong>Nivel:</strong> {{ $entrevistador->fmt_id_nivel }}</p>
        <p><strong>No. Entrevistador:</strong> {{ $entrevistador->fmt_numero_entrevistador }}</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-md-6">
                <h3 class="card-title">Entrevistas con Acceso</h3>
            </div>
            <div class="col-md-6 text-right">
                <a href="{{ route('permisos.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus mr-1"></i> Otorgar Permiso
                </a>
            </div>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Entrevista</th>
                    <th>Tipo</th>
                    <th>Otorgado</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th>Otorgado por</th>
                    <th width="100">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permisos as $permiso)
                <tr>
                    <td>
                        @if($permiso->rel_entrevista)
                            <a href="{{ route('entrevistas.show', $permiso->id_e_ind_fvt) }}">
                                {{ $permiso->rel_entrevista->entrevista_codigo }}
                            </a>
                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($permiso->rel_entrevista->titulo, 40) }}</small>
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ $permiso->id_tipo == 3 ? 'danger' : ($permiso->id_tipo == 2 ? 'warning' : 'info') }}">
                            {{ $permiso->fmt_tipo }}
                        </span>
                    </td>
                    <td>{{ $permiso->fecha_otorgado ? $permiso->fecha_otorgado->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $permiso->fecha_vencimiento ? $permiso->fecha_vencimiento->format('d/m/Y') : 'Sin limite' }}</td>
                    <td>
                        @if($permiso->esta_vigente)
                            <span class="badge badge-success">Vigente</span>
                        @else
                            <span class="badge badge-secondary">Vencido</span>
                        @endif
                    </td>
                    <td>
                        @if($permiso->rel_otorgado_por && $permiso->rel_otorgado_por->rel_usuario)
                            {{ $permiso->rel_otorgado_por->rel_usuario->name }}
                        @else
                            <span class="text-muted">Sistema</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('permisos.destroy', $permiso->id_permiso) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Revocar este permiso?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Revocar">
                                <i class="fas fa-ban"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">Este usuario no tiene permisos especiales otorgados</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<a href="{{ route('usuarios.show', $entrevistador->rel_usuario->id ?? 0) }}" class="btn btn-secondary">
    <i class="fas fa-arrow-left mr-1"></i> Volver a Usuario
</a>
@endsection
