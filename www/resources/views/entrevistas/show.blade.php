@extends('layouts.app')

@section('title', 'Ver Entrevista')
@section('content_header', 'Detalle de Entrevista')

@section('content')
<div class="row">
    <div class="col-md-12">
        <!-- Header -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-microphone"></i>
                    {{ $entrevista->entrevista_codigo }}
                </h3>
                <div class="card-tools">
                    @if($puedeEditar)
                    <a href="{{ route('entrevistas.wizard.edit', $entrevista->id_e_ind_fvt) }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    @endif
                    <a href="{{ route('entrevistas.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            <div class="card-body">
                <h4>{{ $entrevista->titulo }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <!-- PASO 1: Datos Testimoniales -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-file-alt mr-2"></i>Datos Testimoniales</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 45%">Codigo:</th>
                                <td><strong>{{ $entrevista->entrevista_codigo }}</strong></td>
                            </tr>
                            <tr>
                                <th>Numero:</th>
                                <td>{{ $entrevista->entrevista_numero }}</td>
                            </tr>
                            <tr>
                                <th>Correlativo:</th>
                                <td>{{ $entrevista->entrevista_correlativo }}</td>
                            </tr>
                            <tr>
                                <th>Dependencia Origen:</th>
                                <td>{{ $entrevista->rel_dependencia_origen->descripcion ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Equipo/Estrategia:</th>
                                <td>{{ $entrevista->rel_equipo_estrategia->descripcion ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Proyecto/Investigacion:</th>
                                <td>{{ $entrevista->nombre_proyecto ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Tipo Testimonio:</th>
                                <td>{{ $entrevista->rel_tipo_testimonio->descripcion ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>No. Testimoniantes:</th>
                                <td>{{ $entrevista->num_testimoniantes ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Idioma(s):</th>
                                <td>
                                    @if($entrevista->rel_idiomas && $entrevista->rel_idiomas->count() > 0)
                                        @foreach($entrevista->rel_idiomas as $idioma)
                                            <span class="badge badge-info mr-1">{{ $idioma->descripcion }}</span>
                                        @endforeach
                                    @elseif($entrevista->rel_idioma)
                                        {{ $entrevista->rel_idioma->descripcion }}
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </td>
                            </tr>
                            @if($entrevista->detalle_idiomas)
                            <tr>
                                <th>Detalle Idiomas:</th>
                                <td>{{ $entrevista->detalle_idiomas }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 45%">Entrevistador:</th>
                                <td>
                                    @if($entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario)
                                        {{ $entrevista->rel_entrevistador->rel_usuario->name }}
                                    @else
                                        <span class="text-muted">Sin asignar</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Incluye NNA:</th>
                                <td>
                                    @if($entrevista->nna)
                                        <span class="badge badge-warning">Si</span>
                                    @else
                                        <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Tiene Anexos:</th>
                                <td>
                                    @if($entrevista->tiene_anexos)
                                        <span class="badge badge-info">Si</span>
                                    @else
                                        <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                            @if($entrevista->descripcion_anexos)
                            <tr>
                                <th>Descripcion Anexos:</th>
                                <td>{{ $entrevista->descripcion_anexos }}</td>
                            </tr>
                            @endif
                            <tr>
                                <th>Creada:</th>
                                <td>{{ $entrevista->created_at ? \Carbon\Carbon::parse($entrevista->created_at)->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                            <tr>
                                <th>Actualizada:</th>
                                <td>{{ $entrevista->updated_at ? \Carbon\Carbon::parse($entrevista->updated_at)->format('d/m/Y H:i') : '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Fecha y Lugar de Toma -->
                <hr>
                <h6><i class="fas fa-calendar-alt mr-2"></i>Fecha y Lugar de la Toma</h6>
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 45%">Fecha Inicial:</th>
                                <td>{{ $entrevista->fecha_toma_inicial ? \Carbon\Carbon::parse($entrevista->fecha_toma_inicial)->format('d/m/Y') : 'No especificada' }}</td>
                            </tr>
                            <tr>
                                <th>Fecha Final:</th>
                                <td>{{ $entrevista->fecha_toma_final ? \Carbon\Carbon::parse($entrevista->fecha_toma_final)->format('d/m/Y') : 'No especificada' }}</td>
                            </tr>
                            <tr>
                                <th>Virtual:</th>
                                <td>
                                    @if($entrevista->es_virtual)
                                        <span class="badge badge-info">Si</span>
                                    @else
                                        <span class="badge badge-secondary">No</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width: 45%">Departamento:</th>
                                <td>{{ $depto_toma->descripcion ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Municipio:</th>
                                <td>{{ $muni_toma->descripcion ?? 'No especificado' }}</td>
                            </tr>
                            <tr>
                                <th>Duracion:</th>
                                <td>
                                    @if($entrevista->tiempo_entrevista)
                                        {{ $entrevista->tiempo_entrevista }} minutos
                                    @else
                                        <span class="text-muted">No especificada</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Formatos y Modalidades -->
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-file-video mr-2"></i>Formato(s) del Testimonio</h6>
                        @if($entrevista->rel_formatos && $entrevista->rel_formatos->count() > 0)
                            @foreach($entrevista->rel_formatos as $formato)
                                <span class="badge badge-info mr-1">{{ $formato->descripcion }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No especificado</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-handshake mr-2"></i>Modalidad(es)</h6>
                        @if($entrevista->rel_modalidades && $entrevista->rel_modalidades->count() > 0)
                            @foreach($entrevista->rel_modalidades as $modalidad)
                                <span class="badge badge-success mr-1">{{ $modalidad->descripcion }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No especificado</span>
                        @endif
                    </div>
                </div>

                <!-- Areas Compatibles y Necesidades -->
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-building mr-2"></i>Areas Compatibles</h6>
                        @if($areas_compatibles && $areas_compatibles->count() > 0)
                            @foreach($areas_compatibles as $area)
                                <span class="badge badge-primary mr-1">{{ $area }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No especificado</span>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-heart mr-2"></i>Necesidades de Reparacion</h6>
                        @if($entrevista->rel_necesidades_reparacion && $entrevista->rel_necesidades_reparacion->count() > 0)
                            @foreach($entrevista->rel_necesidades_reparacion as $necesidad)
                                <span class="badge badge-warning mr-1">{{ $necesidad->descripcion }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No especificado</span>
                        @endif
                    </div>
                </div>

                @if($entrevista->observaciones_toma)
                <hr>
                <h6><i class="fas fa-sticky-note mr-2"></i>Observaciones de la Toma</h6>
                <div class="callout callout-info">
                    {!! nl2br(e($entrevista->observaciones_toma)) !!}
                </div>
                @endif

            </div>
        </div>

        <!-- PASO 2: Testimoniantes -->
        <div class="card card-success card-outline">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-users mr-2"></i>Testimoniantes</h5>
            </div>
            <div class="card-body">
                @if($entrevista->rel_personas_entrevistadas && $entrevista->rel_personas_entrevistadas->count() > 0)
                    @foreach($entrevista->rel_personas_entrevistadas as $index => $pe)
                    <div class="card card-outline card-secondary mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="fas fa-user mr-2"></i>Testimoniante #{{ $index + 1 }}:
                                @if(Auth::user()->id_nivel == 1)
                                    @if($pe->rel_persona)
                                        <strong>{{ $pe->rel_persona->nombre }} {{ $pe->rel_persona->apellido }}</strong>
                                        @if($pe->rel_persona->nombre_identitario)
                                            <small class="text-muted">({{ $pe->rel_persona->nombre_identitario }})</small>
                                        @endif
                                    @else
                                        <span class="text-muted">Sin datos</span>
                                    @endif
                                @else
                                    <strong>Testimoniante {{ $index + 1 }}</strong>
                                @endif
                            </h6>
                        </div>
                        <div class="card-body">
                            @if($pe->rel_persona)
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th style="width: 40%">Sexo:</th>
                                            <td>{{ $pe->rel_persona->rel_sexo->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Identidad de Genero:</th>
                                            <td>{{ $pe->rel_persona->rel_identidad->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Orientacion Sexual:</th>
                                            <td>{{ $pe->rel_persona->rel_orientacion->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Grupo Etnico:</th>
                                            <td>{{ $pe->rel_persona->rel_etnia->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th style="width: 40%">Rango Etario:</th>
                                            <td>{{ $pe->rel_persona->rel_rango_etario->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Edad:</th>
                                            <td>{{ $pe->edad ?? 'No especificada' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Discapacidad:</th>
                                            <td>{{ $pe->rel_persona->rel_discapacidad->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Lugar Origen:</th>
                                            <td>{{ $pe->rel_persona->rel_lugar_nacimiento->descripcion ?? 'No especificado' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Poblaciones y Ocupaciones -->
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <strong>Poblacion(es):</strong>
                                    @if($pe->rel_persona->rel_poblaciones && $pe->rel_persona->rel_poblaciones->count() > 0)
                                        @foreach($pe->rel_persona->rel_poblaciones as $pob)
                                            <span class="badge badge-info mr-1">{{ $pob->descripcion }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <strong>Ocupacion(es):</strong>
                                    @if($pe->rel_persona->rel_ocupaciones && $pe->rel_persona->rel_ocupaciones->count() > 0)
                                        @foreach($pe->rel_persona->rel_ocupaciones as $ocu)
                                            <span class="badge badge-secondary mr-1">{{ $ocu->descripcion }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No especificado</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Consentimiento Informado -->
                            @if($pe->rel_consentimiento)
                            <hr>
                            <h6><i class="fas fa-file-signature mr-2"></i>Consentimiento Informado</h6>
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-sm table-bordered">
                                        <tr>
                                            <td style="width: 50%">
                                                <strong>Tiene documento de autorizacion:</strong>
                                            </td>
                                            <td>
                                                @if($pe->rel_consentimiento->tiene_documento_autorizacion)
                                                    <span class="badge badge-success">Si</span>
                                                @else
                                                    <span class="badge badge-danger">No</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($pe->rel_consentimiento->tiene_documento_autorizacion)
                                        <tr>
                                            <td><strong>Es menor de edad:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->es_menor_edad ? '<span class="badge badge-warning">Si</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Autoriza ser entrevistado:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->autoriza_ser_entrevistado ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Permite grabacion:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->permite_grabacion ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Permite procesamiento misional:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->permite_procesamiento_misional ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Permite uso, conservacion y consulta:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->permite_uso_conservacion_consulta ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Considera riesgo seguridad:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->considera_riesgo_seguridad ? '<span class="badge badge-danger">Si</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Autoriza datos personales sin anonimizar:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->autoriza_datos_personales_sin_anonimizar ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Autoriza datos sensibles sin anonimizar:</strong></td>
                                            <td>{!! $pe->rel_consentimiento->autoriza_datos_sensibles_sin_anonimizar ? '<span class="badge badge-success">Si</span>' : '<span class="badge badge-danger">No</span>' !!}</td>
                                        </tr>
                                        @endif
                                        @if($pe->rel_consentimiento->observaciones)
                                        <tr>
                                            <td><strong>Observaciones:</strong></td>
                                            <td>{{ $pe->rel_consentimiento->observaciones }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>No hay testimoniantes registrados para esta entrevista.
                </div>
                @endif
            </div>
        </div>

        <!-- PASO 3: Contenido del Testimonio -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-book mr-2"></i>Contenido del Testimonio</h5>
            </div>
            <div class="card-body">
                @if($entrevista->rel_contenido)
                    <!-- Fechas de los hechos -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Fecha Inicial de los Hechos:</strong>
                            {{ $entrevista->rel_contenido->fecha_hechos_inicial ? \Carbon\Carbon::parse($entrevista->rel_contenido->fecha_hechos_inicial)->format('d/m/Y') : 'No especificada' }}
                        </div>
                        <div class="col-md-6">
                            <strong>Fecha Final de los Hechos:</strong>
                            {{ $entrevista->rel_contenido->fecha_hechos_final ? \Carbon\Carbon::parse($entrevista->rel_contenido->fecha_hechos_final)->format('d/m/Y') : 'No especificada' }}
                        </div>
                    </div>

                    <hr>
                    <h6>Caracteristicas Mencionadas en el Testimonio</h6>

                    <!-- Poblaciones y Ocupaciones -->
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <strong>Poblacion(es):</strong><br>
                            @if($entrevista->rel_contenido->rel_poblaciones && $entrevista->rel_contenido->rel_poblaciones->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_poblaciones as $item)
                                    <span class="badge badge-info mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <strong>Ocupacion(es):</strong><br>
                            @if($entrevista->rel_contenido->rel_ocupaciones && $entrevista->rel_contenido->rel_ocupaciones->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_ocupaciones as $item)
                                    <span class="badge badge-secondary mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    @if($entrevista->rel_contenido->otras_poblaciones_mencionadas || $entrevista->rel_contenido->otras_ocupaciones_mencionadas)
                    <div class="row mb-2">
                        @if($entrevista->rel_contenido->otras_poblaciones_mencionadas)
                        <div class="col-md-6">
                            <strong>Otra(s) Poblacion(es):</strong><br>
                            <span class="text-muted">{{ $entrevista->rel_contenido->otras_poblaciones_mencionadas }}</span>
                        </div>
                        @endif
                        @if($entrevista->rel_contenido->otras_ocupaciones_mencionadas)
                        <div class="col-md-6">
                            <strong>Otra(s) Ocupacion(es):</strong><br>
                            <span class="text-muted">{{ $entrevista->rel_contenido->otras_ocupaciones_mencionadas }}</span>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Sexos, Identidades, Orientaciones -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <strong>Sexo(s):</strong><br>
                            @if($entrevista->rel_contenido->rel_sexos && $entrevista->rel_contenido->rel_sexos->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_sexos as $item)
                                    <span class="badge badge-primary mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Identidad(es) de Genero:</strong><br>
                            @if($entrevista->rel_contenido->rel_identidades_genero && $entrevista->rel_contenido->rel_identidades_genero->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_identidades_genero as $item)
                                    <span class="badge badge-success mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Orientacion(es) Sexual(es):</strong><br>
                            @if($entrevista->rel_contenido->rel_orientaciones_sexuales && $entrevista->rel_contenido->rel_orientaciones_sexuales->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_orientaciones_sexuales as $item)
                                    <span class="badge badge-warning mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    <!-- Etnias, Rangos, Discapacidades -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <strong>Grupo(s) Etnico(s):</strong><br>
                            @if($entrevista->rel_contenido->rel_etnias && $entrevista->rel_contenido->rel_etnias->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_etnias as $item)
                                    <span class="badge badge-info mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Rango(s) de Edad:</strong><br>
                            @if($entrevista->rel_contenido->rel_rangos_etarios && $entrevista->rel_contenido->rel_rangos_etarios->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_rangos_etarios as $item)
                                    <span class="badge badge-secondary mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <strong>Discapacidad(es):</strong><br>
                            @if($entrevista->rel_contenido->rel_discapacidades && $entrevista->rel_contenido->rel_discapacidades->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_discapacidades as $item)
                                    <span class="badge badge-danger mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    @if($entrevista->rel_contenido->detalle_grupos_etnicos)
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Detalle Grupo(s) Etnico(s):</strong><br>
                            <span class="text-muted">{{ $entrevista->rel_contenido->detalle_grupos_etnicos }}</span>
                        </div>
                    </div>
                    @endif

                    <hr>

                    <!-- Hechos Victimizantes -->
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Hecho(s) Victimizante(s) y de Resistencia:</strong><br>
                            @if($entrevista->rel_contenido->rel_hechos_victimizantes && $entrevista->rel_contenido->rel_hechos_victimizantes->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_hechos_victimizantes as $item)
                                    <span class="badge badge-danger mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    @if($entrevista->rel_contenido->otros_hechos_victimizantes)
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Otro(s) Hecho(s) Victimizante(s):</strong><br>
                            <span class="text-muted">{{ $entrevista->rel_contenido->otros_hechos_victimizantes }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Practicas de Resistencia -->
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Practica(s) de Resistencia:</strong><br>
                            @if($entrevista->rel_contenido->rel_practicas_resistencia && $entrevista->rel_contenido->rel_practicas_resistencia->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_practicas_resistencia as $item)
                                    <span class="badge badge-success mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    @if($entrevista->rel_contenido->detalle_resistencias)
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Detalle Resistencia(s):</strong><br>
                            <span class="text-muted">{{ $entrevista->rel_contenido->detalle_resistencias }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Responsables -->
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Responsable(s) Colectivo(s):</strong><br>
                            @if($entrevista->rel_contenido->rel_responsables && $entrevista->rel_contenido->rel_responsables->count() > 0)
                                @foreach($entrevista->rel_contenido->rel_responsables as $item)
                                    <span class="badge badge-dark mr-1 mb-1">{{ $item->descripcion }}</span>
                                @endforeach
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    <!-- Lugares Geograficos Mencionados -->
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong><i class="fas fa-map-marker-alt mr-1"></i>Lugar(es) Geografico(s) Mencionado(s):</strong><br>
                            @if(isset($lugares_mencionados) && $lugares_mencionados->count() > 0)
                                <ul class="list-unstyled mt-2">
                                @foreach($lugares_mencionados as $lugar)
                                    <li>
                                        <i class="fas fa-map-pin text-danger mr-1"></i>
                                        @if($lugar->departamento)
                                            <strong>{{ $lugar->departamento }}</strong>
                                        @endif
                                        @if($lugar->municipio)
                                            - {{ $lugar->municipio }}
                                        @endif
                                    </li>
                                @endforeach
                                </ul>
                            @else
                                <span class="text-muted">No especificado</span>
                            @endif
                        </div>
                    </div>

                    @if($entrevista->rel_contenido->responsables_individuales)
                    <div class="row mb-2">
                        <div class="col-md-12">
                            <strong>Responsable(s) Individual(es):</strong><br>
                            <div class="callout callout-warning">
                                {!! nl2br(e($entrevista->rel_contenido->responsables_individuales)) !!}
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($entrevista->rel_contenido->temas_abordados)
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <strong>Temas Abordados:</strong><br>
                            <div class="callout callout-info">
                                {!! nl2br(e($entrevista->rel_contenido->temas_abordados)) !!}
                            </div>
                        </div>
                    </div>
                    @endif
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>No hay contenido del testimonio registrado.
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Adjuntos: visible según permiso del rol -->
        @if(\App\Models\RolModuloPermiso::puedeVer(Auth::user()->id_nivel, 'adjuntos'))
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paperclip"></i> Archivos Adjuntos</h3>
                <div class="card-tools">
                    <a href="{{ route('adjuntos.gestionar', $entrevista->id_e_ind_fvt) }}" class="btn btn-info btn-sm">
                        <i class="fas fa-cog"></i> Gestionar
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($entrevista->rel_adjuntos && $entrevista->rel_adjuntos->count() > 0)
                <ul class="list-group list-group-flush">
                    @foreach($entrevista->rel_adjuntos as $adjunto)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            @if($adjunto->es_audio)
                                <i class="fas fa-file-audio text-info"></i>
                            @elseif($adjunto->es_video)
                                <i class="fas fa-file-video text-danger"></i>
                            @elseif($adjunto->es_documento)
                                <i class="fas fa-file-pdf text-warning"></i>
                            @else
                                <i class="fas fa-file text-secondary"></i>
                            @endif
                            {{ \Illuminate\Support\Str::limit($adjunto->nombre_original ?? 'Archivo', 25) }}
                        </span>
                        <span class="badge badge-info">{{ $adjunto->fmt_tamano }}</span>
                    </li>
                    @endforeach
                </ul>
                <a href="{{ route('adjuntos.gestionar', $entrevista->id_e_ind_fvt) }}" class="btn btn-outline-info btn-block btn-sm mt-2">
                    Ver todos los archivos
                </a>
                @else
                <p class="text-muted text-center mb-2">
                    <i class="fas fa-folder-open"></i> Sin archivos adjuntos
                </p>
                <a href="{{ route('adjuntos.gestionar', $entrevista->id_e_ind_fvt) }}" class="btn btn-info btn-block btn-sm">
                    <i class="fas fa-upload"></i> Subir archivos
                </a>
                @endif
            </div>
        </div>
        @endif

        <!-- Acciones -->
        @php $nivelActual = Auth::user()->id_nivel; @endphp
        @if($puedeEditar || ($nivelActual == 1) || ($nivelActual == 3) || ($nivelActual == 5))
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cogs"></i> Acciones</h3>
            </div>
            <div class="card-body">
                {{-- Editar: admin o quien tiene permiso --}}
                @if($puedeEditar)
                <a href="{{ route('entrevistas.wizard.edit', $entrevista->id_e_ind_fvt) }}" class="btn btn-warning btn-block mb-2">
                    <i class="fas fa-edit"></i> Editar Entrevista
                </a>
                @endif

                {{-- Solicitar edición: Entrevistador (no propietario, sin permiso) o Gestor --}}
                @if(in_array($nivelActual, [3, 5]) && !$puedeEditar && !$solicitudEdicionPendiente)
                <button type="button" class="btn btn-outline-warning btn-block mb-2" data-toggle="modal" data-target="#modalSolicitarEdicion">
                    <i class="fas fa-edit"></i> Solicitar Permiso de Edición
                </button>
                @elseif(in_array($nivelActual, [3, 5]) && $solicitudEdicionPendiente)
                <button class="btn btn-outline-secondary btn-block mb-2" disabled>
                    <i class="fas fa-clock"></i> Solicitud de Edición Pendiente
                </button>
                @endif

                {{-- Solicitar eliminación: Entrevistador propietario o Gestor --}}
                @if(($nivelActual == 3 && $esPropietario) || $nivelActual == 5)
                    @if(!$solicitudEliminacionPendiente)
                    <button type="button" class="btn btn-outline-danger btn-block mb-2" data-toggle="modal" data-target="#modalSolicitarEliminacion">
                        <i class="fas fa-trash"></i> Solicitar Eliminación
                    </button>
                    @else
                    <button class="btn btn-outline-secondary btn-block mb-2" disabled>
                        <i class="fas fa-clock"></i> Solicitud de Eliminación Pendiente
                    </button>
                    @endif
                @endif

                {{-- Eliminar directo: solo Admin --}}
                @if($nivelActual == 1)
                <form action="{{ route('entrevistas.destroy', $entrevista->id_e_ind_fvt) }}" method="POST" class="mt-2" onsubmit="return confirm('Esta seguro de eliminar esta entrevista?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-trash"></i> Eliminar Entrevista
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
{{-- Modal Solicitar Permiso de Edición --}}
@if(in_array(Auth::user()->id_nivel, [3, 5]))
<div class="modal fade" id="modalSolicitarEdicion" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Solicitar Permiso de Edición</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('permisos.solicitar') }}" method="POST">
                @csrf
                <input type="hidden" name="id_e_ind_fvt" value="{{ $entrevista->id_e_ind_fvt }}">
                <input type="hidden" name="tipo_solicitud" value="edicion">
                <div class="modal-body">
                    <p>Está solicitando permiso para <strong>editar</strong> la entrevista <strong>{{ $entrevista->entrevista_codigo }}</strong>.</p>
                    <div class="form-group">
                        <label for="justificacion_edicion">Justificación (opcional)</label>
                        <textarea class="form-control" id="justificacion_edicion" name="justificacion" rows="3" placeholder="Indique el motivo de la solicitud..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane mr-1"></i>Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- Modal Solicitar Eliminación --}}
@if(\App\Models\RolModuloPermiso::puedeCrear(Auth::user()->id_nivel, 'permisos') &&
    ($esPropietario || \App\Models\RolModuloPermiso::alcanceDependencia(Auth::user()->id_nivel, 'permisos')))
<div class="modal fade" id="modalSolicitarEliminacion" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash mr-2"></i>Solicitar Eliminación</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('permisos.solicitar') }}" method="POST">
                @csrf
                <input type="hidden" name="id_e_ind_fvt" value="{{ $entrevista->id_e_ind_fvt }}">
                <input type="hidden" name="tipo_solicitud" value="eliminacion">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        La solicitud será revisada por el administrador antes de proceder.
                    </div>
                    <div class="form-group">
                        <label for="justificacion_eliminacion">Justificación <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="justificacion_eliminacion" name="justificacion" rows="4" placeholder="Indique el motivo por el cual solicita la eliminación..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane mr-1"></i>Enviar Solicitud</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
