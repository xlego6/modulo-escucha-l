<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-file-alt mr-2"></i>Paso 1: Datos Testimoniales</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Editando entrevista: <strong>{{ $entrevista->entrevista_codigo }}</strong>
        </div>

        <div class="row">
            <!-- Titulo -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="titulo" class="required-field">Titulo de la Entrevista</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="500" value="{{ $entrevista->titulo }}">
                </div>
            </div>

            <!-- Dependencia y Tipo -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_dependencia_origen" class="required-field">Dependencia de Origen</label>
                    <select class="form-control select2" id="id_dependencia_origen" name="id_dependencia_origen" required>
                        <option value="">-- Seleccione --</option>
                        @foreach($catalogos['dependencias'] as $id => $descripcion)
                        <option value="{{ $id }}" {{ $entrevista->id_dependencia_origen == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_equipo_estrategia">Equipo/Estrategia</label>
                    <select class="form-control select2" id="id_equipo_estrategia" name="id_equipo_estrategia">
                        <option value="">-- Seleccione --</option>
                    </select>
                    <small class="form-text text-muted">Equipo o estrategia de la dependencia</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="nombre_proyecto">Nombre Proyecto/Investigacion/Caso</label>
                    <input type="text" class="form-control" id="nombre_proyecto" name="nombre_proyecto" maxlength="500" value="{{ $entrevista->nombre_proyecto }}">
                    <small class="form-text text-muted">Nombre del proyecto o investigacion al que pertenece</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_tipo_testimonio" class="required-field">Tipo de Testimonio</label>
                    <select class="form-control select2" id="id_tipo_testimonio" name="id_tipo_testimonio" required>
                        <option value="">-- Seleccione --</option>
                        @foreach($catalogos['tipos_testimonio'] as $id => $descripcion)
                        <option value="{{ $id }}" {{ $entrevista->id_tipo_testimonio == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Formato del testimonio -->
            <div class="col-md-6">
                <div class="form-group">
                    <label class="required-field">Formato del Testimonio</label>
                    <div class="row">
                        @foreach($catalogos['formatos'] as $id => $descripcion)
                        <div class="col-6">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="formato_{{ $id }}" name="formatos[]" value="{{ $id }}">
                                <label class="custom-control-label" for="formato_{{ $id }}">{{ $descripcion }}</label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Numero de testimoniantes -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="num_testimoniantes" class="required-field">Numero de Personas que Brindan Testimonio</label>
                    <input type="number" class="form-control" id="num_testimoniantes" name="num_testimoniantes" value="{{ $entrevista->num_testimoniantes ?? 1 }}" min="1" max="20" required>
                    <small class="form-text text-muted">Este valor determinara cuantos formularios de testimoniante se mostraran en el Paso 2</small>
                </div>
            </div>

            <!-- Lugar geografico -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_territorio" class="required-field">Departamento de Toma del Testimonio</label>
                    <select class="form-control select2 departamento-select" id="id_territorio" name="id_territorio" required>
                        <option value="">-- Seleccione --</option>
                        @foreach($catalogos['departamentos'] as $id => $descripcion)
                        <option value="{{ $id }}" {{ $entrevista->id_territorio == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="entrevista_lugar" class="required-field">Municipio de Toma del Testimonio</label>
                    <select class="form-control select2 municipio-select" id="entrevista_lugar" name="entrevista_lugar" required>
                        <option value="">-- Seleccione Departamento primero --</option>
                    </select>
                </div>
            </div>

            <!-- Modalidad -->
            <div class="col-md-6">
                <div class="form-group">
                    <label class="required-field">Modalidad</label>
                    <div>
                        @foreach($catalogos['modalidades'] as $id => $descripcion)
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="modalidad_{{ $id }}" name="modalidades[]" value="{{ $id }}">
                            <label class="custom-control-label" for="modalidad_{{ $id }}">{{ $descripcion }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Idioma -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="id_idioma" class="required-field">Idioma del Testimonio</label>
                    <select class="form-control" id="id_idioma" name="id_idioma" required>
                        <option value="">-- Seleccione --</option>
                        @foreach($catalogos['idiomas'] as $id => $descripcion)
                        <option value="{{ $id }}" {{ $entrevista->id_idioma == $id ? 'selected' : '' }}>{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Fechas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha_toma_inicial" class="required-field">Fecha Inicial de Toma</label>
                    <input type="date" class="form-control" id="fecha_toma_inicial" name="fecha_toma_inicial" required value="{{ $entrevista->fecha_toma_inicial }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha_toma_final" class="required-field">Fecha Final de Toma</label>
                    <input type="date" class="form-control" id="fecha_toma_final" name="fecha_toma_final" required value="{{ $entrevista->fecha_toma_final }}">
                </div>
            </div>

            <!-- Necesidades de reparacion -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Necesidades de Ruta de Reparacion</label>
                    <div>
                        @foreach($catalogos['necesidades_reparacion'] as $id => $descripcion)
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="necesidad_{{ $id }}" name="necesidades_reparacion[]" value="{{ $id }}">
                            <label class="custom-control-label" for="necesidad_{{ $id }}">{{ $descripcion }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Areas compatibles -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="areas_compatibles">Areas Compatibles con el Testimonio</label>
                    <select class="form-control select2" id="areas_compatibles" name="areas_compatibles[]" multiple>
                        @foreach($catalogos['dependencias'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Puede seleccionar varias areas</small>
                </div>
            </div>

            <!-- Anexos -->
            <div class="col-md-6">
                <div class="form-group">
                    <label class="required-field">Tiene Anexo(s) al Testimonio</label>
                    <div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" id="tiene_anexos_si" name="tiene_anexos" value="1" {{ $entrevista->tiene_anexos ? 'checked' : '' }}>
                            <label class="custom-control-label" for="tiene_anexos_si">Si</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" class="custom-control-input" id="tiene_anexos_no" name="tiene_anexos" value="0" {{ !$entrevista->tiene_anexos ? 'checked' : '' }}>
                            <label class="custom-control-label" for="tiene_anexos_no">No</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="descripcion_anexos">Descripcion de Anexo(s)</label>
                    <textarea class="form-control" id="descripcion_anexos" name="descripcion_anexos" rows="2">{{ $entrevista->descripcion_anexos }}</textarea>
                </div>
            </div>

            <!-- Observaciones -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="observaciones_toma">Observaciones sobre la Toma de Entrevista</label>
                    <textarea class="form-control" id="observaciones_toma" name="observaciones_toma" rows="3">{{ $entrevista->observaciones_toma }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Cargar municipio si hay departamento seleccionado
    @if($entrevista->id_territorio && $entrevista->entrevista_lugar)
    $.get('{{ route("api.municipios") }}', { id_departamento: '{{ $entrevista->id_territorio }}' }, function(data) {
        let muniSelect = $('#entrevista_lugar');
        muniSelect.empty().append('<option value="">-- Seleccione --</option>');
        $.each(data, function(id, nombre) {
            muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
        });
        muniSelect.val('{{ $entrevista->entrevista_lugar }}');
    });
    @endif

    // Cargar areas compatibles
    @php
        $areasCompatibles = \Illuminate\Support\Facades\DB::table('esclarecimiento.entrevista_area_compatible')
            ->where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->pluck('id_area')
            ->toArray();
    @endphp
    @if(count($areasCompatibles) > 0)
    $('#areas_compatibles').val({!! json_encode($areasCompatibles) !!}).trigger('change');
    @endif

    // Cargar equipos/estrategias si hay dependencia seleccionada
    @if($entrevista->id_dependencia_origen)
    var equiposData = {!! json_encode($catalogos['equipos_estrategias']) !!};
    var depId = '{{ $entrevista->id_dependencia_origen }}';
    var equipoSelect = $('#id_equipo_estrategia');

    equipoSelect.empty().append('<option value="">-- Seleccione --</option>');
    if (equiposData[depId]) {
        $.each(equiposData[depId], function(id, nombre) {
            equipoSelect.append('<option value="' + id + '">' + nombre + '</option>');
        });
    }
    @if($entrevista->id_equipo_estrategia)
    equipoSelect.val('{{ $entrevista->id_equipo_estrategia }}');
    @endif
    @endif
});
</script>
@endpush
