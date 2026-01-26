<div class="card">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="fas fa-users mr-2"></i>Paso 2: Informacion de Testimoniante(s)</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Complete la informacion de cada persona que brinda testimonio. El numero de formularios depende del valor indicado en el Paso 1.
        </div>

        <div id="testimoniantes-container">
            <!-- Los formularios se generan dinamicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- Template para testimoniante (oculto, usado por JS) -->
<template id="testimoniante-template">
    <div class="card testimoniante-card" data-index="__INDEX__">
        <div class="card-header">
            <h6 class="mb-0">
                <i class="fas fa-user mr-2"></i>Testimoniante #<span class="testimoniante-numero">__NUM__</span>
            </h6>
        </div>
        <div class="card-body">
            <input type="hidden" name="id_persona___INDEX__" value="">
            <input type="hidden" name="id_persona_entrevistada___INDEX__" value="">

            <div class="row">
                <!-- Nombre -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required-field">Nombre(s)</label>
                        <input type="text" class="form-control" name="nombre___INDEX__" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="required-field">Apellido(s)</label>
                        <input type="text" class="form-control" name="apellido___INDEX__" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nombre Identitario</label>
                        <input type="text" class="form-control" name="nombre_identitario___INDEX__">
                        <small class="form-text text-muted">Nombre con el que la persona elige ser reconocida</small>
                    </div>
                </div>

                <!-- Lugar de origen -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Departamento de Origen</label>
                        <select class="form-control select2 departamento-select" name="id_lugar_origen_depto___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['departamentos'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Municipio de Origen</label>
                        <select class="form-control select2 municipio-select" name="id_lugar_origen_muni___INDEX__">
                            <option value="">-- Seleccione --</option>
                        </select>
                    </div>
                </div>

                <!-- Poblacion - Select Multiple -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Poblacion</label>
                        <select class="form-control select2-multiple" name="poblaciones___INDEX__[]" multiple>
                            @foreach($catalogos['poblaciones'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Puede seleccionar varias opciones</small>
                    </div>
                </div>

                <!-- Ocupacion - Select Multiple -->
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Ocupacion</label>
                        <select class="form-control select2-multiple" name="ocupaciones___INDEX__[]" multiple>
                            @foreach($catalogos['ocupaciones'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Puede seleccionar varias opciones</small>
                    </div>
                </div>

                <!-- Sexo, Identidad, Orientacion -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Sexo</label>
                        <select class="form-control" name="id_sexo___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['sexos'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Identidad de Genero</label>
                        <select class="form-control" name="id_identidad_genero___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['identidades_genero'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Orientacion Sexual</label>
                        <select class="form-control" name="id_orientacion_sexual___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['orientaciones_sexuales'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Etnia, Etario, Edad, Discapacidad -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Grupo Etnico</label>
                        <select class="form-control" name="id_etnia___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['etnias'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Rango Etario</label>
                        <select class="form-control" name="id_rango_etario___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['rangos_etarios'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Edad</label>
                        <input type="number" class="form-control" name="edad___INDEX__" min="0" max="120">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Discapacidad</label>
                        <select class="form-control" name="id_discapacidad___INDEX__">
                            <option value="">-- Seleccione --</option>
                            @foreach($catalogos['discapacidades'] as $id => $descripcion)
                            <option value="{{ $id }}">{{ $descripcion }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Consentimiento Informado -->
            <div class="consentimiento-section">
                <h6><i class="fas fa-file-signature mr-2"></i>Consentimiento Informado</h6>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="required-field">Tiene documento de autorizacion</label>
                            <div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input tiene-documento-radio" id="tiene_documento___INDEX___si" name="tiene_documento___INDEX__" value="1">
                                    <label class="custom-control-label" for="tiene_documento___INDEX___si">Si</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" class="custom-control-input tiene-documento-radio" id="tiene_documento___INDEX___no" name="tiene_documento___INDEX__" value="0" checked>
                                    <label class="custom-control-label" for="tiene_documento___INDEX___no">No</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preguntas de consentimiento (visibles solo si tiene documento = Si) -->
                    <div class="col-md-12 preguntas-consentimiento" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Es menor de edad al momento de la entrevista</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="es_menor___INDEX___si" name="es_menor_edad___INDEX__" value="1">
                                            <label class="custom-control-label" for="es_menor___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="es_menor___INDEX___no" name="es_menor_edad___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="es_menor___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Autoriza ser entrevistado por CNMH</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_entrevista___INDEX___si" name="autoriza_entrevista___INDEX__" value="1">
                                            <label class="custom-control-label" for="autoriza_entrevista___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_entrevista___INDEX___no" name="autoriza_entrevista___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="autoriza_entrevista___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Permite que la entrevista sea grabada</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_grabacion___INDEX___si" name="permite_grabacion___INDEX__" value="1">
                                            <label class="custom-control-label" for="permite_grabacion___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_grabacion___INDEX___no" name="permite_grabacion___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="permite_grabacion___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Permite procesamiento con fines misionales</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_procesamiento___INDEX___si" name="permite_procesamiento___INDEX__" value="1">
                                            <label class="custom-control-label" for="permite_procesamiento___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_procesamiento___INDEX___no" name="permite_procesamiento___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="permite_procesamiento___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Permite uso, conservacion y consulta</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_uso___INDEX___si" name="permite_uso___INDEX__" value="1">
                                            <label class="custom-control-label" for="permite_uso___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="permite_uso___INDEX___no" name="permite_uso___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="permite_uso___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Considera que pone en riesgo su seguridad</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="considera_riesgo___INDEX___si" name="considera_riesgo___INDEX__" value="1">
                                            <label class="custom-control-label" for="considera_riesgo___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="considera_riesgo___INDEX___no" name="considera_riesgo___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="considera_riesgo___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Autoriza datos personales sin anonimizar</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_datos_personales___INDEX___si" name="autoriza_datos_personales___INDEX__" value="1">
                                            <label class="custom-control-label" for="autoriza_datos_personales___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_datos_personales___INDEX___no" name="autoriza_datos_personales___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="autoriza_datos_personales___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Autoriza datos sensibles sin anonimizar</label>
                                    <div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_datos_sensibles___INDEX___si" name="autoriza_datos_sensibles___INDEX__" value="1">
                                            <label class="custom-control-label" for="autoriza_datos_sensibles___INDEX___si">Si</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input type="radio" class="custom-control-input" id="autoriza_datos_sensibles___INDEX___no" name="autoriza_datos_sensibles___INDEX__" value="0" checked>
                                            <label class="custom-control-label" for="autoriza_datos_sensibles___INDEX___no">No</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones (siempre visible, obligatorio si no tiene documento) -->
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="observaciones-label">Observaciones del Consentimiento</label>
                            <textarea class="form-control observaciones-consentimiento" name="observaciones_consentimiento___INDEX__" rows="2" placeholder="Indique el motivo por el cual no se cuenta con documento de autorizacion..."></textarea>
                            <small class="form-text text-muted observaciones-ayuda">Este campo es obligatorio si no tiene documento de autorizacion</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
