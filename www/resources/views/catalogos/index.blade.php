@extends('layouts.app')

@section('title', 'Catalogos')
@section('content_header', 'Gestion de Catalogos')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-2"></i>Listado de Catalogos</h3>
        <div class="card-tools">
            <a href="{{ route('catalogos.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i>Nuevo Catalogo
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th style="width: 60px">ID</th>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th style="width: 100px" class="text-center">Items</th>
                    <th style="width: 100px" class="text-center">Editable</th>
                    <th style="width: 150px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($catalogos as $catalogo)
                <tr>
                    <td>{{ $catalogo->id_cat }}</td>
                    <td>
                        <a href="{{ route('catalogos.show', $catalogo->id_cat) }}">
                            <strong>{{ $catalogo->nombre }}</strong>
                        </a>
                    </td>
                    <td class="text-muted">{{ $catalogo->descripcion ?? '-' }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $catalogo->rel_items_count }}</span>
                    </td>
                    <td class="text-center">
                        @if($catalogo->editable)
                            <span class="badge badge-success">Si</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('catalogos.show', $catalogo->id_cat) }}" class="btn btn-info btn-sm" title="Ver items">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($catalogo->editable)
                        <a href="{{ route('catalogos.edit', $catalogo->id_cat) }}" class="btn btn-warning btn-sm" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No hay catalogos registrados
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($catalogos->hasPages())
    <div class="card-footer">
        {{ $catalogos->links() }}
    </div>
    @endif
</div>

<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informacion</h3>
    </div>
    <div class="card-body">
        <p>Los catalogos contienen las listas cerradas utilizadas en los formularios del sistema, como:</p>
        <ul class="mb-0">
            <li><strong>Sexo, Etnia, Discapacidad:</strong> Datos demograficos de testimoniantes</li>
            <li><strong>Dependencias:</strong> Areas del CNMH que realizan entrevistas</li>
            <li><strong>Tipos de Testimonio:</strong> Clasificacion de entrevistas</li>
            <li><strong>Hechos Victimizantes:</strong> Categorias de hechos narrados</li>
            <li><strong>Responsables:</strong> Actores mencionados en testimonios</li>
        </ul>
    </div>
</div>
@endsection
