@extends('layouts.app')

@section('title', 'Catalogo: ' . $catalogo->nombre)
@section('content_header', 'Items del Catalogo')

@section('css')
<style>
    .sortable-row { cursor: move; }
    .sortable-row:hover { background-color: #f8f9fa; }
    .sortable-ghost { opacity: 0.4; background-color: #bee5eb; }
    .orden-badge { min-width: 35px; }
    .btn-orden { padding: 0.1rem 0.3rem; font-size: 0.7rem; }
</style>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informacion del Catalogo</h3>
            </div>
            <div class="card-body">
                <dl>
                    <dt>ID</dt>
                    <dd>{{ $catalogo->id_cat }}</dd>

                    <dt>Nombre</dt>
                    <dd>{{ $catalogo->nombre }}</dd>

                    <dt>Descripcion</dt>
                    <dd>{{ $catalogo->descripcion ?? 'Sin descripcion' }}</dd>

                    <dt>Editable</dt>
                    <dd>
                        @if($catalogo->editable)
                            <span class="badge badge-success">Si</span>
                        @else
                            <span class="badge badge-secondary">No</span>
                        @endif
                    </dd>

                    <dt>Total Items</dt>
                    <dd><span class="badge badge-info">{{ $items->total() }}</span></dd>
                </dl>
            </div>
            <div class="card-footer">
                <a href="{{ route('catalogos.index') }}" class="btn btn-default">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>
                @if($catalogo->editable)
                <a href="{{ route('catalogos.edit', $catalogo->id_cat) }}" class="btn btn-warning">
                    <i class="fas fa-edit mr-1"></i>Editar
                </a>
                @endif
            </div>
        </div>

        <!-- Tarjeta de ayuda para ordenar -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sort mr-2"></i>Ordenar Items</h3>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">Puede cambiar el orden de los items de dos formas:</p>
                <ul class="text-muted small mb-0">
                    <li>Arrastrando las filas en la tabla</li>
                    <li>Usando los botones <i class="fas fa-arrow-up"></i> <i class="fas fa-arrow-down"></i></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ul mr-2"></i>Items del Catalogo</h3>
                <div class="card-tools">
                    <a href="{{ route('catalogos.items.create', $catalogo->id_cat) }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i>Agregar Item
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped table-hover" id="items-table">
                    <thead>
                        <tr>
                            <th style="width: 100px">Orden</th>
                            <th>Descripcion</th>
                            <th style="width: 100px">Abreviado</th>
                            <th style="width: 80px" class="text-center">Estado</th>
                            <th style="width: 120px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="sortable-items">
                        @forelse($items as $item)
                        <tr data-id="{{ $item->id_item }}" class="sortable-row">
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-secondary orden-badge mr-2">{{ $item->orden }}</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary btn-orden btn-move-up" data-id="{{ $item->id_item }}" title="Subir">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-orden btn-move-down" data-id="{{ $item->id_item }}" title="Bajar">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <i class="fas fa-grip-vertical text-muted mr-2" title="Arrastrar para reordenar"></i>
                                {{ $item->descripcion }}
                                @if($item->predeterminado)
                                    <span class="badge badge-warning ml-1">Default</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $item->abreviado ?? '-' }}</td>
                            <td class="text-center">
                                @if($item->habilitado)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('catalogos.items.edit', [$catalogo->id_cat, $item->id_item]) }}" class="btn btn-warning btn-xs" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('catalogos.items.toggle', [$catalogo->id_cat, $item->id_item]) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-{{ $item->habilitado ? 'danger' : 'success' }} btn-xs" title="{{ $item->habilitado ? 'Deshabilitar' : 'Habilitar' }}">
                                        <i class="fas fa-{{ $item->habilitado ? 'ban' : 'check' }}"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No hay items en este catalogo
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($items->hasPages())
            <div class="card-footer">
                {{ $items->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    const catalogoId = {{ $catalogo->id_cat }};

    // Inicializar Sortable para drag & drop
    const sortable = new Sortable(document.getElementById('sortable-items'), {
        animation: 150,
        ghostClass: 'sortable-ghost',
        handle: '.fa-grip-vertical',
        onEnd: function(evt) {
            actualizarOrden();
        }
    });

    // Boton subir
    $(document).on('click', '.btn-move-up', function() {
        const row = $(this).closest('tr');
        const prevRow = row.prev('tr');
        if (prevRow.length) {
            row.insertBefore(prevRow);
            actualizarOrden();
        }
    });

    // Boton bajar
    $(document).on('click', '.btn-move-down', function() {
        const row = $(this).closest('tr');
        const nextRow = row.next('tr');
        if (nextRow.length) {
            row.insertAfter(nextRow);
            actualizarOrden();
        }
    });

    // Actualizar orden en servidor
    function actualizarOrden() {
        const items = [];
        $('#sortable-items tr').each(function(index) {
            const id = $(this).data('id');
            if (id) {
                items.push({ id: id, orden: index + 1 });
                // Actualizar badge visual
                $(this).find('.orden-badge').text(index + 1);
            }
        });

        if (items.length > 0) {
            $.ajax({
                url: '{{ route("catalogos.items.reorder", $catalogo->id_cat) }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    items: items
                },
                success: function(response) {
                    if (response.success) {
                        // Mostrar indicador de exito breve
                        showToast('Orden actualizado', 'success');
                    }
                },
                error: function() {
                    showToast('Error al actualizar orden', 'error');
                }
            });
        }
    }

    // Toast simple
    function showToast(message, type) {
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const toast = $('<div class="position-fixed" style="top: 20px; right: 20px; z-index: 9999;"><div class="toast show ' + bgClass + ' text-white"><div class="toast-body">' + message + '</div></div></div>');
        $('body').append(toast);
        setTimeout(function() { toast.fadeOut(function() { $(this).remove(); }); }, 2000);
    }
});
</script>
@endsection
