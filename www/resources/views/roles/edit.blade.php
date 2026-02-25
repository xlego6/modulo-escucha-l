@extends('layouts.app')

@section('title', 'Permisos: ' . $rol->nombre)
@section('content_header', 'Configurar Permisos')

@section('content')
<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="card-title mb-0">
                    {{ $rol->nombre }}
                    @if($rol->es_sistema)
                        <span class="badge badge-secondary ml-2">Sistema</span>
                    @else
                        <span class="badge badge-info ml-2">Personalizado</span>
                    @endif
                    — Nivel {{ $rol->id_nivel }}
                </h3>
                @if($rol->descripcion)
                    <small class="text-muted">{{ $rol->descripcion }}</small>
                @endif
            </div>
            <div class="col-auto">
                <a href="{{ route('roles.index') }}" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            </div>
        </div>
    </div>

    @if($rol->id_nivel == 1)
    <div class="card-body pb-0">
        <div class="alert alert-warning mb-0">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <strong>Cuidado:</strong> Esta modificando el rol <strong>Administrador</strong>.
            Quitar permisos puede bloquear el acceso a funciones criticas del sistema.
        </div>
    </div>
    @endif

    <form action="{{ route('roles.update', $rol->id_nivel) }}" method="POST" id="form-permisos">
        @csrf
        @method('PUT')

        <div class="card-body">
            <div style="overflow-x: auto;">
                <table class="table table-bordered table-sm text-center" id="tabla-permisos">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-left" style="min-width:150px">Modulo</th>
                            <th style="min-width:55px">Ver</th>
                            <th style="min-width:65px">Crear</th>
                            <th style="min-width:65px">Editar</th>
                            <th style="min-width:70px">Eliminar</th>
                            <th style="min-width:75px" class="text-warning">Propias</th>
                            <th style="min-width:100px" class="text-warning">Dependencia</th>
                            <th style="min-width:60px" class="text-warning">Todas</th>
                        </tr>
                        <tr class="bg-light">
                            <td class="text-left text-muted small py-1">
                                <i class="fas fa-info-circle mr-1"></i>
                                Alcance (columnas amarillas)
                            </td>
                            <td colspan="4" class="text-muted small py-1">Acciones CRUD</td>
                            <td colspan="3" class="text-muted small py-1">Registros visibles</td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modulos as $clave => $etiqueta)
                        @php
                            $p = $permisos[$clave] ?? null;
                        @endphp
                        <tr data-modulo="{{ $clave }}">
                            <td class="text-left font-weight-bold">{{ $etiqueta }}</td>

                            {{-- VER --}}
                            <td>
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][puede_ver]"
                                       class="check-ver"
                                       value="1"
                                       {{ ($p && $p->puede_ver) ? 'checked' : '' }}
                                       @if($rol->id_nivel == 1 && $clave === 'roles') disabled checked @endif>
                                @if($rol->id_nivel == 1 && $clave === 'roles')
                                    <input type="hidden" name="modulos[{{ $clave }}][puede_ver]" value="1">
                                @endif
                            </td>

                            {{-- CREAR --}}
                            <td>
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][puede_crear]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->puede_crear) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>

                            {{-- EDITAR --}}
                            <td>
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][puede_editar]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->puede_editar) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>

                            {{-- ELIMINAR --}}
                            <td>
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][puede_eliminar]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->puede_eliminar) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>

                            {{-- PROPIAS --}}
                            <td class="table-warning">
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][alcance_propias]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->alcance_propias) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>

                            {{-- DEPENDENCIA --}}
                            <td class="table-warning">
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][alcance_dependencia]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->alcance_dependencia) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>

                            {{-- TODAS --}}
                            <td class="table-warning">
                                <input type="checkbox"
                                       name="modulos[{{ $clave }}][alcance_todas]"
                                       class="check-accion"
                                       value="1"
                                       {{ ($p && $p->alcance_todas) ? 'checked' : '' }}
                                       {{ ($p && !$p->puede_ver) ? 'disabled' : '' }}>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-muted small mt-2 mb-0">
                <i class="fas fa-info-circle mr-1"></i>
                Al desmarcar <strong>Ver</strong>, los demas permisos de esa fila se deshabilitan automaticamente.
                El <strong>Alcance</strong> define que registros puede ver el usuario en los modulos donde tiene acceso.
            </p>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Guardar Permisos
            </button>
            <a href="{{ route('roles.index') }}" class="btn btn-default ml-2">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabla = document.getElementById('tabla-permisos');

    tabla.querySelectorAll('.check-ver').forEach(function (checkVer) {
        checkVer.addEventListener('change', function () {
            var fila   = this.closest('tr');
            var acciones = fila.querySelectorAll('.check-accion');

            if (!this.checked) {
                acciones.forEach(function (cb) {
                    cb.checked  = false;
                    cb.disabled = true;
                });
            } else {
                acciones.forEach(function (cb) {
                    cb.disabled = false;
                });
            }
        });
    });
});
</script>
@endsection
