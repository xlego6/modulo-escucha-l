<div class="card">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-book mr-2"></i>Paso 3: Contenido del Testimonio</h5>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            Esta seccion registra informacion sobre el contenido narrado en el testimonio, incluyendo poblaciones, hechos victimizantes, lugares y responsables mencionados.
        </div>

        <div class="row">
            <!-- Fechas de los hechos -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha_hechos_inicial">Fecha Inicial de los Hechos</label>
                    <input type="date" class="form-control" id="fecha_hechos_inicial" name="fecha_hechos_inicial">
                    <small class="form-text text-muted">Fecha del hecho mas antiguo mencionado</small>
                    <div class="mt-1">
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input fecha-sin-info-check" id="fecha_hechos_inicial_dia_si" name="fecha_hechos_inicial_dia_conocido" value="0">
                            <label class="custom-control-label" for="fecha_hechos_inicial_dia_si"><small>Dia sin informacion</small></label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input fecha-sin-info-check" id="fecha_hechos_inicial_mes_si" name="fecha_hechos_inicial_mes_conocido" value="0">
                            <label class="custom-control-label" for="fecha_hechos_inicial_mes_si"><small>Mes sin informacion</small></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="fecha_hechos_final">Fecha Final de los Hechos</label>
                    <input type="date" class="form-control" id="fecha_hechos_final" name="fecha_hechos_final">
                    <small class="form-text text-muted">Fecha del hecho mas reciente mencionado</small>
                    <div class="mt-1">
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input fecha-sin-info-check" id="fecha_hechos_final_dia_si" name="fecha_hechos_final_dia_conocido" value="0">
                            <label class="custom-control-label" for="fecha_hechos_final_dia_si"><small>Dia sin informacion</small></label>
                        </div>
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input fecha-sin-info-check" id="fecha_hechos_final_mes_si" name="fecha_hechos_final_mes_conocido" value="0">
                            <label class="custom-control-label" for="fecha_hechos_final_mes_si"><small>Mes sin informacion</small></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Poblaciones mencionadas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Poblacion(es) Mencionada(s) en el Testimonio</label>
                    <select class="form-control select2-paso3" id="contenido_poblaciones" name="contenido_poblaciones[]" multiple>
                        @foreach($catalogos['poblaciones'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Otras poblaciones mencionadas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="otras_poblaciones_mencionadas">Otra(s) Poblacion(es) Mencionada(s)</label>
                    <textarea class="form-control" id="otras_poblaciones_mencionadas" name="otras_poblaciones_mencionadas" rows="2" placeholder="Otras poblaciones no incluidas en el listado..."></textarea>
                </div>
            </div>

            <!-- Ocupaciones mencionadas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Ocupacion(es) Mencionada(s) en el Testimonio</label>
                    <select class="form-control select2-paso3" id="contenido_ocupaciones" name="contenido_ocupaciones[]" multiple>
                        @foreach($catalogos['ocupaciones'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Otras ocupaciones mencionadas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="otras_ocupaciones_mencionadas">Otra(s) Ocupacion(es) Mencionada(s)</label>
                    <textarea class="form-control" id="otras_ocupaciones_mencionadas" name="otras_ocupaciones_mencionadas" rows="2" placeholder="Otras ocupaciones no incluidas en el listado..."></textarea>
                </div>
            </div>

            <!-- Sexos mencionados -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Sexo(s) Mencionado(s)</label>
                    <select class="form-control select2-paso3" id="contenido_sexos" name="contenido_sexos[]" multiple>
                        @foreach($catalogos['sexos'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Identidades de genero mencionadas -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Identidad(es) de Genero Mencionada(s)</label>
                    <select class="form-control select2-paso3" id="contenido_identidades" name="contenido_identidades[]" multiple>
                        @foreach($catalogos['identidades_genero'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Orientaciones sexuales mencionadas -->
            <div class="col-md-4">
                <div class="form-group">
                    <label>Orientacion(es) Sexual(es) Mencionada(s)</label>
                    <select class="form-control select2-paso3" id="contenido_orientaciones" name="contenido_orientaciones[]" multiple>
                        @foreach($catalogos['orientaciones_sexuales'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Grupos etnicos mencionados -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Grupo(s) Etnico(s) Mencionado(s)</label>
                    <select class="form-control select2-paso3" id="contenido_etnias" name="contenido_etnias[]" multiple>
                        @foreach($catalogos['etnias'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Detalle grupos etnicos -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="detalle_grupos_etnicos">Detalle de Grupo(s) Etnico(s)</label>
                    <textarea class="form-control" id="detalle_grupos_etnicos" name="detalle_grupos_etnicos" rows="2" placeholder="Detalle adicional sobre los grupos etnicos mencionados..."></textarea>
                </div>
            </div>

            <!-- Rangos etarios mencionados -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Rango(s) de Edad Mencionado(s)</label>
                    <select class="form-control select2-paso3" id="contenido_rangos" name="contenido_rangos[]" multiple>
                        @foreach($catalogos['rangos_etarios'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Discapacidades mencionadas -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Discapacidad(es) Mencionada(s)</label>
                    <select class="form-control select2-paso3" id="contenido_discapacidades" name="contenido_discapacidades[]" multiple>
                        @foreach($catalogos['discapacidades'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Hechos victimizantes -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Hecho(s) Victimizante(s) Mencionado(s)</label>
                    <select class="form-control select2-paso3" id="contenido_hechos" name="contenido_hechos[]" multiple>
                        @foreach($catalogos['hechos_victimizantes'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Otros hechos victimizantes -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="otros_hechos_victimizantes">Otro(s) Hecho(s) Victimizante(s)</label>
                    <textarea class="form-control" id="otros_hechos_victimizantes" name="otros_hechos_victimizantes" rows="2" placeholder="Otros hechos victimizantes no incluidos en el listado..."></textarea>
                </div>
            </div>

            <!-- Practicas de resistencia -->
            <div class="col-md-6">
                <div class="form-group">
                    <label>Practica(s) de Resistencia Mencionada(s)</label>
                    <select class="form-control select2-paso3" id="contenido_practicas_resistencia" name="contenido_practicas_resistencia[]" multiple>
                        @foreach($catalogos['practicas_resistencia'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Detalle resistencias -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="detalle_resistencias">Detalle de Resistencia(s)</label>
                    <textarea class="form-control" id="detalle_resistencias" name="detalle_resistencias" rows="2" placeholder="Detalle adicional sobre las practicas de resistencia mencionadas..."></textarea>
                </div>
            </div>

            <!-- Responsables colectivos -->
            <div class="col-md-12">
                <div class="form-group">
                    <label>Responsable(s) Colectivo(s) Mencionado(s)</label>
                    <select class="form-control select2-paso3" id="contenido_responsables" name="contenido_responsables[]" multiple>
                        @foreach($catalogos['responsables_colectivos'] as $id => $descripcion)
                        <option value="{{ $id }}">{{ $descripcion }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Lugares geograficos mencionados -->
            <div class="col-md-12">
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt mr-1"></i>Lugar(es) Geografico(s) Mencionado(s) en el Testimonio</label>
                    <small class="form-text text-muted mb-2">Departamentos y municipios mencionados en el testimonio. Puede agregar multiples lugares.</small>

                    <div id="lugares-mencionados-container">
                        <!-- Los lugares se agregan dinamicamente -->
                    </div>

                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn-agregar-lugar">
                        <i class="fas fa-plus mr-1"></i>Agregar Lugar
                    </button>
                </div>
            </div>

            <!-- Responsables individuales -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="responsables_individuales">Responsable(s) Individual(es) Mencionado(s)</label>
                    <textarea class="form-control" id="responsables_individuales" name="responsables_individuales" rows="2" placeholder="Nombres, alias, filiaciones..."></textarea>
                    <small class="form-text text-muted">Personas especificas mencionadas con niveles de responsabilidad</small>
                </div>
            </div>

            <!-- Temas del Tesauro -->
            <div class="col-md-12">
                <div class="form-group">
                    <label for="temas_tesauro" class="required-field">Temas Abordados en el Testimonio (Tesauro DDHH)</label>
                    <select id="temas_tesauro" name="temas_tesauro[]" multiple class="form-control select2-tesauro" style="width:100%">
                        {{-- Los options se cargan dinámicamente vía AJAX o al editar --}}
                    </select>
                    <small class="form-text text-muted">
                        Escriba al menos 2 caracteres para buscar en el Tesauro de Derechos Humanos del CNMH.
                        <span id="tesauro-status" class="ml-2"></span>
                    </small>
                    {{-- Campo oculto que almacena el JSON final para enviar al servidor --}}
                    <input type="hidden" id="temas_abordados" name="temas_abordados" value="">
                </div>
            </div>
        </div>

        <!-- Prueba de Daño (aplica a toda la entrevista) -->
        <div class="prueba-dano-section mt-4" style="background-color: #f8d7da; padding: 15px; border-radius: 5px;">
            <h6><i class="fas fa-exclamation-triangle mr-2"></i>Prueba de Daño</h6>
            <small class="form-text text-muted mb-2 d-block">Esta valoracion aplica a la entrevista en su conjunto, no a cada testimoniante por separado.</small>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>1. ¿El testimonio afecta derechos privados (a la vida, seguridad, a la integridad; secretos comerciales o profesionales - Art. 18 Ley 1712 de 2014)?</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_privados_si" name="prueba_dano_derechos_privados" value="1">
                                <label class="custom-control-label" for="prueba_dano_privados_si">Si</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_privados_no" name="prueba_dano_derechos_privados" value="0">
                                <label class="custom-control-label" for="prueba_dano_privados_no">No</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_privados_ns" name="prueba_dano_derechos_privados" value="2" checked>
                                <label class="custom-control-label" for="prueba_dano_privados_ns">No sabe</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>2. ¿El testimonio afecta intereses publicos (a la defensa y seguridad nacional; las relaciones internacionales; procesos judiciales o disciplinarios abiertos; derechos de infancia y adolescencia - Art. 19 Ley 1712 de 2014)?</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_publicos_si" name="prueba_dano_intereses_publicos" value="1">
                                <label class="custom-control-label" for="prueba_dano_publicos_si">Si</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_publicos_no" name="prueba_dano_intereses_publicos" value="0">
                                <label class="custom-control-label" for="prueba_dano_publicos_no">No</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_publicos_ns" name="prueba_dano_intereses_publicos" value="2" checked>
                                <label class="custom-control-label" for="prueba_dano_publicos_ns">No sabe</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>3. ¿El testimonio tiene informacion de inteligencia y contrainteligencia (Ley 1621 de 2013)?</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_inteligencia_si" name="prueba_dano_inteligencia" value="1">
                                <label class="custom-control-label" for="prueba_dano_inteligencia_si">Si</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_inteligencia_no" name="prueba_dano_inteligencia" value="0">
                                <label class="custom-control-label" for="prueba_dano_inteligencia_no">No</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_inteligencia_ns" name="prueba_dano_inteligencia" value="2" checked>
                                <label class="custom-control-label" for="prueba_dano_inteligencia_ns">No sabe</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>4. ¿El testimonio fue realizado a poblacion de Niños, Niñas y adolescentes (NNA)?</label>
                        <div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_nna_si" name="prueba_dano_nna" value="1">
                                <label class="custom-control-label" for="prueba_dano_nna_si">Si</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_nna_no" name="prueba_dano_nna" value="0">
                                <label class="custom-control-label" for="prueba_dano_nna_no">No</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline">
                                <input type="radio" class="custom-control-input" id="prueba_dano_nna_ns" name="prueba_dano_nna" value="2" checked>
                                <label class="custom-control-label" for="prueba_dano_nna_ns">No sabe</label>
                            </div>
                        </div>
                        <small class="form-text text-muted" id="prueba_dano_nna_auto_hint" style="display:none;">
                            <i class="fas fa-info-circle mr-1"></i>Pre-marcado en "Si" porque al menos un(a) testimoniante fue registrado como menor de edad. Puede corregirlo si no corresponde.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
