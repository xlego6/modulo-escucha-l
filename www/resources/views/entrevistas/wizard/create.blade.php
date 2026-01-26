@extends('layouts.app')

@section('title', 'Nueva Entrevista')
@section('content_header', 'Registrar Nueva Entrevista')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .wizard-nav { margin-bottom: 20px; }
    .wizard-nav .nav-link { padding: 15px 25px; font-weight: 500; }
    .wizard-nav .nav-link.active { background-color: #007bff; color: white; }
    .wizard-nav .nav-link.completed { background-color: #28a745; color: white; }
    .wizard-nav .nav-link:not(.active):not(.completed) { background-color: #e9ecef; }
    .step-content { display: none; }
    .step-content.active { display: block; }
    .testimoniante-card { border: 2px solid #dee2e6; margin-bottom: 15px; }
    .testimoniante-card .card-header { background-color: #f8f9fa; }
    .consentimiento-section { background-color: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 15px; }
    .required-field::after { content: ' *'; color: red; }
</style>
@endsection

@section('content')
<form id="entrevista-wizard-form">
    @csrf
    <input type="hidden" name="id_e_ind_fvt" id="id_e_ind_fvt" value="">

    <!-- Navegacion del Wizard -->
    <ul class="nav nav-pills wizard-nav justify-content-center" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="step1-tab" data-step="1" href="#">
                <i class="fas fa-file-alt mr-2"></i>1. Testimoniales
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="step2-tab" data-step="2" href="#">
                <i class="fas fa-users mr-2"></i>2. Testimoniantes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="step3-tab" data-step="3" href="#">
                <i class="fas fa-book mr-2"></i>3. Contenido
            </a>
        </li>
    </ul>

    <!-- Paso 1: Testimoniales -->
    <div class="step-content active" id="step1">
        @include('entrevistas.wizard.partials.paso1', ['catalogos' => $catalogos, 'entrevistador' => $entrevistador])
    </div>

    <!-- Paso 2: Testimoniantes -->
    <div class="step-content" id="step2">
        @include('entrevistas.wizard.partials.paso2', ['catalogos' => $catalogos])
    </div>

    <!-- Paso 3: Contenido -->
    <div class="step-content" id="step3">
        @include('entrevistas.wizard.partials.paso3', ['catalogos' => $catalogos])
    </div>

    <!-- Botones de navegacion -->
    <div class="card mt-3">
        <div class="card-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" id="btn-anterior" style="display:none;">
                <i class="fas fa-arrow-left mr-2"></i>Anterior
            </button>
            <div class="ml-auto">
                <button type="button" class="btn btn-info mr-2" id="btn-guardar-borrador">
                    <i class="fas fa-save mr-2"></i>Guardar Borrador
                </button>
                <button type="button" class="btn btn-primary" id="btn-siguiente">
                    Siguiente<i class="fas fa-arrow-right ml-2"></i>
                </button>
                <button type="button" class="btn btn-success" id="btn-finalizar" style="display:none;">
                    <i class="fas fa-check mr-2"></i>Finalizar
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    let currentStep = 1;
    const totalSteps = 3;

    // Inicializar Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Navegacion entre pasos
    function showStep(step) {
        $('.step-content').removeClass('active');
        $('#step' + step).addClass('active');

        $('.wizard-nav .nav-link').removeClass('active');
        $('#step' + step + '-tab').addClass('active');

        // Marcar pasos anteriores como completados
        for (let i = 1; i < step; i++) {
            $('#step' + i + '-tab').addClass('completed');
        }

        // Mostrar/ocultar botones
        $('#btn-anterior').toggle(step > 1);
        $('#btn-siguiente').toggle(step < totalSteps);
        $('#btn-finalizar').toggle(step === totalSteps);

        // Inicializar Select2 para paso 3 al mostrarlo
        if (step === 3) {
            $('#step3 .select2-paso3').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Seleccione opciones...',
                allowClear: true
            });
        }

        currentStep = step;
    }

    // Boton Siguiente
    $('#btn-siguiente').click(function() {
        if (validarPaso(currentStep)) {
            guardarPaso(currentStep, function() {
                showStep(currentStep + 1);
                // Generar testimoniantes si pasamos al paso 2
                if (currentStep === 2) {
                    generarFormulariosTestimoniantes();
                }
            });
        }
    });

    // Boton Anterior
    $('#btn-anterior').click(function() {
        showStep(currentStep - 1);
    });

    // Boton Finalizar
    $('#btn-finalizar').click(function() {
        if (validarPaso(currentStep)) {
            guardarPaso(currentStep, function(response) {
                if (response.redirect) {
                    window.location.href = response.redirect;
                }
            });
        }
    });

    // Boton Guardar Borrador
    $('#btn-guardar-borrador').click(function() {
        guardarPaso(currentStep, function() {
            alert('Borrador guardado correctamente.');
        });
    });

    // Validar paso actual
    function validarPaso(step) {
        let valid = true;
        let firstInvalid = null;

        $('#step' + step + ' [required]').each(function() {
            if (!$(this).val() || ($(this).is('select') && $(this).val() === '')) {
                $(this).addClass('is-invalid');
                valid = false;
                if (!firstInvalid) firstInvalid = $(this);
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validar checkboxes requeridos (al menos uno)
        if (step === 1) {
            if ($('input[name="formatos[]"]:checked').length === 0) {
                alert('Debe seleccionar al menos un formato de testimonio.');
                valid = false;
            }
            if ($('input[name="modalidades[]"]:checked').length === 0) {
                alert('Debe seleccionar al menos una modalidad.');
                valid = false;
            }
        }

        // Validar consentimiento en paso 2
        if (step === 2) {
            $('.testimoniante-card').each(function(index) {
                let card = $(this);
                let tieneDocumento = card.find('.tiene-documento-radio:checked').val() === '1';
                let observaciones = card.find('.observaciones-consentimiento').val();

                if (!tieneDocumento && (!observaciones || observaciones.trim() === '')) {
                    card.find('.observaciones-consentimiento').addClass('is-invalid');
                    alert('Testimoniante #' + (index + 1) + ': Las observaciones del consentimiento son obligatorias cuando no tiene documento de autorizacion.');
                    valid = false;
                } else {
                    card.find('.observaciones-consentimiento').removeClass('is-invalid');
                }
            });
        }

        if (firstInvalid) {
            firstInvalid.focus();
        }

        return valid;
    }

    // Guardar paso actual
    function guardarPaso(step, callback) {
        let url = '';
        let data = {};

        if (step === 1) {
            url = '{{ route("entrevistas.wizard.paso1") }}';
            data = obtenerDatosPaso1();
        } else if (step === 2) {
            url = '{{ route("entrevistas.wizard.paso2") }}';
            data = obtenerDatosPaso2();
        } else if (step === 3) {
            url = '{{ route("entrevistas.wizard.paso3") }}';
            data = obtenerDatosPaso3();
        }

        data._token = '{{ csrf_token() }}';
        data.id_e_ind_fvt = $('#id_e_ind_fvt').val();

        $.ajax({
            url: url,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function(response) {
                if (response.success) {
                    if (response.id_e_ind_fvt) {
                        $('#id_e_ind_fvt').val(response.id_e_ind_fvt);
                    }
                    if (callback) callback(response);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let msg = 'Error al guardar.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    }

    // Obtener datos del Paso 1
    function obtenerDatosPaso1() {
        let formatos = [];
        $('input[name="formatos[]"]:checked').each(function() {
            formatos.push($(this).val());
        });

        let modalidades = [];
        $('input[name="modalidades[]"]:checked').each(function() {
            modalidades.push($(this).val());
        });

        let necesidades = [];
        $('input[name="necesidades_reparacion[]"]:checked').each(function() {
            necesidades.push($(this).val());
        });

        return {
            titulo: $('#titulo').val(),
            id_dependencia_origen: $('#id_dependencia_origen').val(),
            id_equipo_estrategia: $('#id_equipo_estrategia').val(),
            nombre_proyecto: $('#nombre_proyecto').val(),
            id_tipo_testimonio: $('#id_tipo_testimonio').val(),
            formatos: formatos,
            num_testimoniantes: $('#num_testimoniantes').val(),
            id_territorio: $('#id_territorio').val(),
            entrevista_lugar: $('#entrevista_lugar').val(),
            modalidades: modalidades,
            fecha_toma_inicial: $('#fecha_toma_inicial').val(),
            fecha_toma_final: $('#fecha_toma_final').val(),
            id_idioma: $('#id_idioma').val(),
            necesidades_reparacion: necesidades,
            areas_compatibles: $('#areas_compatibles').val() || [],
            observaciones_toma: $('#observaciones_toma').val(),
            tiene_anexos: $('input[name="tiene_anexos"]:checked').val(),
            descripcion_anexos: $('#descripcion_anexos').val()
        };
    }

    // Obtener datos del Paso 2
    function obtenerDatosPaso2() {
        let testimoniantes = [];

        $('.testimoniante-card').each(function(index) {
            let card = $(this);
            // Obtener valores de selects multiples
            let poblaciones = card.find('[name="poblaciones_' + index + '[]"]').val() || [];
            let ocupaciones = card.find('[name="ocupaciones_' + index + '[]"]').val() || [];

            testimoniantes.push({
                id_persona: card.find('[name="id_persona_' + index + '"]').val(),
                id_persona_entrevistada: card.find('[name="id_persona_entrevistada_' + index + '"]').val(),
                nombre: card.find('[name="nombre_' + index + '"]').val(),
                apellido: card.find('[name="apellido_' + index + '"]').val(),
                nombre_identitario: card.find('[name="nombre_identitario_' + index + '"]').val(),
                id_lugar_origen_depto: card.find('[name="id_lugar_origen_depto_' + index + '"]').val(),
                id_lugar_origen_muni: card.find('[name="id_lugar_origen_muni_' + index + '"]').val(),
                poblaciones: poblaciones,
                ocupaciones: ocupaciones,
                id_sexo: card.find('[name="id_sexo_' + index + '"]').val(),
                id_identidad_genero: card.find('[name="id_identidad_genero_' + index + '"]').val(),
                id_orientacion_sexual: card.find('[name="id_orientacion_sexual_' + index + '"]').val(),
                id_etnia: card.find('[name="id_etnia_' + index + '"]').val(),
                id_rango_etario: card.find('[name="id_rango_etario_' + index + '"]').val(),
                edad: card.find('[name="edad_' + index + '"]').val(),
                id_discapacidad: card.find('[name="id_discapacidad_' + index + '"]').val(),
                consentimiento: {
                    tiene_documento: card.find('[name="tiene_documento_' + index + '"]:checked').val() || 0,
                    es_menor_edad: card.find('[name="es_menor_edad_' + index + '"]:checked').val() || 0,
                    autoriza_entrevista: card.find('[name="autoriza_entrevista_' + index + '"]:checked').val() || 0,
                    permite_grabacion: card.find('[name="permite_grabacion_' + index + '"]:checked').val() || 0,
                    permite_procesamiento: card.find('[name="permite_procesamiento_' + index + '"]:checked').val() || 0,
                    permite_uso: card.find('[name="permite_uso_' + index + '"]:checked').val() || 0,
                    considera_riesgo: card.find('[name="considera_riesgo_' + index + '"]:checked').val() || 0,
                    autoriza_datos_personales: card.find('[name="autoriza_datos_personales_' + index + '"]:checked').val() || 0,
                    autoriza_datos_sensibles: card.find('[name="autoriza_datos_sensibles_' + index + '"]:checked').val() || 0,
                    observaciones: card.find('[name="observaciones_consentimiento_' + index + '"]').val()
                }
            });
        });

        return { testimoniantes: testimoniantes };
    }

    // Obtener datos del Paso 3
    function obtenerDatosPaso3() {
        // Recolectar lugares mencionados
        let lugares = [];
        $('.lugar-mencionado-row').each(function() {
            let depto = $(this).find('.lugar-depto').val();
            let muni = $(this).find('.lugar-muni').val();
            if (depto || muni) {
                lugares.push({
                    id_departamento: depto || null,
                    id_municipio: muni || null
                });
            }
        });

        return {
            fecha_hechos_inicial: $('#fecha_hechos_inicial').val(),
            fecha_hechos_final: $('#fecha_hechos_final').val(),
            contenido_poblaciones: $('#contenido_poblaciones').val() || [],
            contenido_ocupaciones: $('#contenido_ocupaciones').val() || [],
            contenido_sexos: $('#contenido_sexos').val() || [],
            contenido_identidades: $('#contenido_identidades').val() || [],
            contenido_orientaciones: $('#contenido_orientaciones').val() || [],
            contenido_etnias: $('#contenido_etnias').val() || [],
            contenido_rangos: $('#contenido_rangos').val() || [],
            contenido_discapacidades: $('#contenido_discapacidades').val() || [],
            contenido_hechos: $('#contenido_hechos').val() || [],
            contenido_responsables: $('#contenido_responsables').val() || [],
            contenido_lugares: lugares,
            responsables_individuales: $('#responsables_individuales').val(),
            temas_abordados: $('#temas_abordados').val()
        };
    }

    // Generar formularios de testimoniantes
    function generarFormulariosTestimoniantes() {
        let num = parseInt($('#num_testimoniantes').val()) || 1;
        let container = $('#testimoniantes-container');
        let currentCount = container.find('.testimoniante-card').length;

        // Agregar los que faltan
        for (let i = currentCount; i < num; i++) {
            container.append(generarCardTestimoniante(i));
        }

        // Remover los que sobran
        container.find('.testimoniante-card').each(function(index) {
            if (index >= num) {
                $(this).remove();
            }
        });

        // Re-inicializar Select2 simple
        container.find('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });

        // Re-inicializar Select2 multiple
        container.find('.select2-multiple').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Seleccione opciones...',
            allowClear: true
        });

        // Inicializar lógica de consentimiento para cada card
        container.find('.testimoniante-card').each(function() {
            inicializarConsentimiento($(this));
        });
    }

    // Generar card de testimoniante
    function generarCardTestimoniante(index) {
        let template = $('#testimoniante-template').html();
        template = template.replace(/__INDEX__/g, index);
        template = template.replace(/__NUM__/g, index + 1);
        return template;
    }

    // Inicializar lógica de consentimiento
    function inicializarConsentimiento(card) {
        let radioTieneDoc = card.find('.tiene-documento-radio');
        let preguntasDiv = card.find('.preguntas-consentimiento');
        let observaciones = card.find('.observaciones-consentimiento');
        let observacionesLabel = card.find('.observaciones-label');
        let observacionesAyuda = card.find('.observaciones-ayuda');

        // Función para actualizar la visibilidad
        function actualizarConsentimiento() {
            let tieneDocumento = card.find('.tiene-documento-radio:checked').val() === '1';

            if (tieneDocumento) {
                preguntasDiv.slideDown();
                observaciones.removeAttr('required');
                observacionesLabel.removeClass('required-field');
                observacionesAyuda.hide();
            } else {
                preguntasDiv.slideUp();
                observaciones.attr('required', 'required');
                observacionesLabel.addClass('required-field');
                observacionesAyuda.show();
            }
        }

        // Ejecutar al inicio
        actualizarConsentimiento();

        // Evento change
        radioTieneDoc.on('change', actualizarConsentimiento);
    }

    // Cambio en departamento para cargar municipios
    $(document).on('change', '.departamento-select', function() {
        let depto = $(this).val();
        let muniSelect = $(this).closest('.row').find('.municipio-select');

        if (depto) {
            $.get('{{ route("api.municipios") }}', { id_departamento: depto }, function(data) {
                muniSelect.empty().append('<option value="">-- Seleccione --</option>');
                $.each(data, function(id, nombre) {
                    muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
                });
            });
        } else {
            muniSelect.empty().append('<option value="">-- Seleccione --</option>');
        }
    });

    // Delegación de eventos para radio de consentimiento (para elementos dinámicos)
    $(document).on('change', '.tiene-documento-radio', function() {
        let card = $(this).closest('.testimoniante-card');
        let tieneDocumento = $(this).val() === '1';
        let preguntasDiv = card.find('.preguntas-consentimiento');
        let observaciones = card.find('.observaciones-consentimiento');
        let observacionesLabel = card.find('.observaciones-label');
        let observacionesAyuda = card.find('.observaciones-ayuda');

        if (tieneDocumento) {
            preguntasDiv.slideDown();
            observaciones.removeAttr('required');
            observacionesLabel.removeClass('required-field');
            observacionesAyuda.hide();
        } else {
            preguntasDiv.slideUp();
            observaciones.attr('required', 'required');
            observacionesLabel.addClass('required-field');
            observacionesAyuda.show();
        }
    });

    // === LUGARES MENCIONADOS ===
    let lugarIndex = 0;

    // === DEPARTAMENTO/MUNICIPIO DE TOMA DEL TESTIMONIO ===
    // Manejador específico para el departamento de toma del testimonio
    $('#id_territorio').on('change', function() {
        var deptoId = $(this).val();
        var muniSelect = $('#entrevista_lugar');

        muniSelect.empty().append('<option value="">-- Seleccione --</option>');

        if (deptoId) {
            $.get('{{ route("api.municipios") }}', { id_departamento: deptoId }, function(data) {
                $.each(data, function(id, nombre) {
                    muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
                });
            });
        }
    });

    // === EQUIPO/ESTRATEGIA DEPENDIENTE DE DEPENDENCIA ===
    var equiposData = {!! json_encode($catalogos['equipos_estrategias']) !!};

    // Cuando cambia Dependencia de Origen, actualizar opciones de Equipo/Estrategia
    $('#id_dependencia_origen').on('change', function() {
        var depId = $(this).val();
        var equipoSelect = $('#id_equipo_estrategia');

        equipoSelect.empty().append('<option value="">-- Seleccione --</option>');

        if (depId && equiposData[depId]) {
            $.each(equiposData[depId], function(id, nombre) {
                equipoSelect.append('<option value="' + id + '">' + nombre + '</option>');
            });
        }
    });

    // Construir opciones de departamentos desde PHP
    var departamentosData = {!! json_encode($catalogos['departamentos']) !!};
    function buildDepartamentosOptions() {
        var html = '<option value="">-- Seleccione Departamento --</option>';
        $.each(departamentosData, function(id, nombre) {
            html += '<option value="' + id + '">' + nombre + '</option>';
        });
        return html;
    }

    // Agregar lugar mencionado (usando delegacion de eventos)
    $(document).on('click', '#btn-agregar-lugar', function(e) {
        e.preventDefault();
        agregarLugarMencionado();
    });

    function agregarLugarMencionado(deptoId, muniId) {
        var deptosHtml = buildDepartamentosOptions();
        var html = '<div class="lugar-mencionado-row border rounded p-2 mb-2" data-index="' + lugarIndex + '">' +
            '<div class="row">' +
                '<div class="col-md-5">' +
                    '<select class="form-control form-control-sm lugar-depto" name="lugar_depto_' + lugarIndex + '">' +
                        deptosHtml +
                    '</select>' +
                '</div>' +
                '<div class="col-md-5">' +
                    '<select class="form-control form-control-sm lugar-muni" name="lugar_muni_' + lugarIndex + '">' +
                        '<option value="">-- Seleccione Municipio --</option>' +
                    '</select>' +
                '</div>' +
                '<div class="col-md-2">' +
                    '<button type="button" class="btn btn-danger btn-sm btn-eliminar-lugar" title="Eliminar">' +
                        '<i class="fas fa-trash"></i>' +
                    '</button>' +
                '</div>' +
            '</div>' +
        '</div>';

        $('#lugares-mencionados-container').append(html);

        // Si hay valores preseleccionados
        if (deptoId) {
            var row = $('.lugar-mencionado-row[data-index="' + lugarIndex + '"]');
            row.find('.lugar-depto').val(deptoId).trigger('change');
            if (muniId) {
                // Esperar a que se carguen los municipios
                setTimeout(function() {
                    row.find('.lugar-muni').val(muniId);
                }, 500);
            }
        }

        lugarIndex++;
    }

    // Eliminar lugar mencionado
    $(document).on('click', '.btn-eliminar-lugar', function() {
        $(this).closest('.lugar-mencionado-row').remove();
    });

    // Cargar municipios al cambiar departamento (lugares mencionados)
    $(document).on('change', '.lugar-depto', function() {
        let depto = $(this).val();
        let muniSelect = $(this).closest('.lugar-mencionado-row').find('.lugar-muni');

        if (depto) {
            $.get('{{ route("api.municipios") }}', { id_departamento: depto }, function(data) {
                muniSelect.empty().append('<option value="">-- Seleccione Municipio --</option>');
                $.each(data, function(id, nombre) {
                    muniSelect.append('<option value="' + id + '">' + nombre + '</option>');
                });
            });
        } else {
            muniSelect.empty().append('<option value="">-- Seleccione Municipio --</option>');
        }
    });
});
</script>
@endsection
