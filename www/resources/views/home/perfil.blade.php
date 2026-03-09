@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('content_header', 'Mi Perfil')

@section('content')
<div class="row">
    <!-- Informacion del Usuario -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <div class="profile-user-img img-fluid img-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px;">
                        <i class="fas fa-user fa-3x text-white"></i>
                    </div>
                </div>
                <h3 class="profile-username text-center">{{ $user->name }}</h3>
                <p class="text-center mb-1">
                    <span class="badge badge-primary px-3 py-2" style="font-size: 0.95rem;">{{ $user->fmt_privilegios }}</span>
                </p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Email</b> <a class="float-right">{{ $user->email }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>ID Entrevistador</b> <a class="float-right">{{ $user->id_entrevistador ?: 'N/A' }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Registro</b> <a class="float-right">{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}</a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Compromiso de Reserva: solo para Líder (2) y Transcriptor (4) -->
        @if(Auth::user()->id_nivel == 2 || Auth::user()->id_nivel == 4)
        <div class="card card-{{ $entrevistador && $entrevistador->compromiso_reserva ? 'success' : 'warning' }}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Compromiso de Confidencialidad y Reserva</h3>
            </div>
            <div class="card-body">
                @if($entrevistador && $entrevistador->compromiso_reserva)
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="mb-0"><strong>Compromiso aceptado</strong></p>
                        <small class="text-muted">{{ $entrevistador->compromiso_reserva->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <p class="text-muted">
                        Para acceder a la informacion de testimonios, debe aceptar el compromiso de confidencialidad, reserva y no divulgacion.
                    </p>
                    <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalCompromiso">
                        <i class="fas fa-file-signature mr-2"></i>Aceptar Compromiso
                    </button>
                @endif
            </div>
        </div>
        @endif

        <!-- Compromiso de Acceso Interno: para todos los roles -->
        @if($entrevistador)
        <div class="card card-{{ $entrevistador->compromiso_acceso ? 'success' : 'warning' }}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-id-badge mr-2"></i>Compromiso de Acceso Interno</h3>
            </div>
            <div class="card-body">
                @if($entrevistador->compromiso_acceso)
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="mb-0"><strong>Compromiso aceptado</strong></p>
                        <small class="text-muted">{{ $entrevistador->compromiso_acceso->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <p class="text-muted">
                        Para acceder a los módulos del sistema, debe aceptar las condiciones de uso y acceso interno.
                    </p>
                    <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalCompromisoAcceso">
                        <i class="fas fa-id-badge mr-2"></i>Aceptar Compromiso de Acceso
                    </button>
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-8">
        <!-- Editar Datos -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i>Editar Datos</h3>
            </div>
            <form action="{{ route('perfil.actualizar') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="name">Nombre Completo</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Correo Electronico</label>
                        @if(Auth::user()->id_nivel <= 2)
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        @else
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                            <input type="hidden" name="email" value="{{ $user->email }}">
                            <small class="form-text text-muted">El correo electronico no puede ser modificado. Contacte al administrador si requiere cambiarlo.</small>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        <!-- Cambiar Contraseña -->
        @if(Auth::user()->id_nivel == 1)
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key mr-2"></i>Cambiar Contraseña</h3>
            </div>
            <form action="{{ route('perfil.password') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="password_actual">Contraseña Actual</label>
                        <input type="password" class="form-control @error('password_actual') is-invalid @enderror" id="password_actual" name="password_actual" required>
                        @error('password_actual')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Nueva Contraseña</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Minimo 8 caracteres</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password_confirmation">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-lock mr-2"></i>Cambiar Contraseña
                    </button>
                </div>
            </form>
        </div>
        @else
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-key mr-2"></i>Contraseña</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle mr-2"></i>
                    La gestión de contraseña para su perfil se realiza a través del directorio activo institucional.
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal Compromiso de Confidencialidad, Reserva y No Divulgacion -->
@if(Auth::user()->id_nivel == 2 || Auth::user()->id_nivel == 4)
<div class="modal fade" id="modalCompromiso" tabindex="-1" role="dialog" aria-labelledby="modalCompromisoLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalCompromisoLabel">
                    <i class="fas fa-file-signature mr-2"></i>Compromiso de Confidencialidad, Reserva y No Divulgacion
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('perfil.compromiso') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Importante:</strong> Lea detenidamente el siguiente compromiso antes de aceptar. Su aceptacion queda registrada con fecha y hora.
                    </div>

                    <div class="card card-body bg-light" style="max-height: 400px; overflow-y: auto; font-size: 0.92rem;">

                        <p>El <strong>Centro Nacional de Memoria Historica &ndash; CNMH</strong>, como institucion encargada de contribuir al esclarecimiento de lo ocurrido, de promover y contribuir al reconocimiento de las victimas, y promover la convivencia en los territorios, requiere del tratamiento adecuado de la informacion para garantizar la seguridad y proteccion de las personas, asi como proteger y asegurar la satisfaccion de su derecho a la verdad, la justicia, la reparacion integral y las garantias de no repeticion.</p>

                        <p>Yo, <strong>{{ $user->name }}</strong>, en mi condicion de {{ Auth::user()->id_nivel == 2 ? 'lider(a)' : 'transcriptor(a)' }} vinculado(a) con la entidad, entiendo y acepto las siguientes condiciones, compromisos, derechos y deberes:</p>

                        <ul class="pl-3">
                            <li class="mb-2">Mantener la informacion confidencial en condiciones de seguridad, usandola <strong>EXCLUSIVAMENTE</strong> para realizar la labor asignada. Una vez finalizada la labor o terminado el vinculo con la entidad, devolver <strong>TODA</strong> la informacion y <strong>NO</strong> conservar copia alguna en ningun formato o dispositivo.</li>
                            <li class="mb-2">Proteger la informacion confidencial, sea verbal, escrita, visual, en audio, video o cualquier otro formato recibido sobre los archivos, bases de datos e informacion suministrada, restringiendo su uso exclusivamente para el desarrollo de la labor asignada, sin compartirla con ninguna otra persona, incluidos familiares, amigos o conocidos, independientemente de su vinculo con la entidad.</li>
                            <li class="mb-2"><strong>NO</strong> reproducir en forma mecanica o virtual la informacion entregada bajo ninguna circunstancia, salvo aquellas directamente necesarias para completar la labor asignada.</li>
                            <li class="mb-2"><strong>NO</strong> divulgar, alterar, entregar, facilitar, filtrar, compartir, publicar, revelar, dar a conocer, enviar, ofrecer, intercambiar, comercializar, utilizar o permitir que alguien emplee la informacion con cualquier fin distinto al de la labor asignada.</li>
                            <li class="mb-2"><strong>NO</strong> almacenar la informacion en dispositivos personales o medios no autorizados por la entidad. Realizar la labor <strong>UNICAMENTE</strong> en los equipos y/o sistemas designados oficialmente, cumpliendo con todos los protocolos de seguridad informatica establecidos.</li>
                            <li class="mb-2"><strong>ELIMINAR</strong> de manera inmediata y definitiva cualquier archivo temporal, copia de trabajo o fragmento de informacion que haya sido necesario crear durante el proceso, una vez finalizado cada trabajo y entregado el producto final al supervisor.</li>
                            <li class="mb-2"><strong>INFORMAR</strong> inmediatamente al jefe inmediato sobre cualquier incidente, sustraccion, perdida, filtracion o acceso no autorizado a la informacion bajo custodia.</li>
                            <li class="mb-2"><strong>RECONOCER</strong> que la informacion a la que se tiene acceso contiene relatos y datos de victimas del conflicto y personas en situacion de vulnerabilidad, con el compromiso de manejarla con el maximo respeto y sensibilidad etica.</li>
                            <li class="mb-2"><strong>ABSTENERSE</strong> de realizar busquedas adicionales sobre las personas o hechos mencionados en las entrevistas o informacion a la que accedo, limitandose exclusivamente a la labor tecnica asignada.</li>
                            <li class="mb-2"><strong>FACILITAR</strong> cualquier informacion necesaria para el seguimiento y verificacion de las actividades cuando sea requerido por la entidad, incluyendo el acceso a los equipos y sistemas que utilizo para el desarrollo de la labor.</li>
                            <li class="mb-2"><strong>MANTENER</strong> la confidencialidad de la informacion incluso despues de finalizada la vinculacion con la entidad, reconociendo que este compromiso es extensible incluso despues a la cesacion de servicios y/o actividades contractuales.</li>
                        </ul>

                        <p>La informacion a la que se tiene acceso en el desarrollo de estas actividades debe tener una vocacion restringida de circulacion, buscando garantizar la seguridad y proteccion de las personas victimas, testigos y de la entidad.</p>

                        <p>Entiendo plenamente que el incumplimiento del presente <strong>COMPROMISO DE CONFIDENCIALIDAD, RESERVA Y NO DIVULGACION</strong>, por accion u omision, puede acarrear la terminacion inmediata de mi vinculo contractual con la entidad, sanciones disciplinarias conforme al regimen aplicable, responsabilidades civiles por los danos y perjuicios causados, y responsabilidades penales bajo los delitos tipificados en la Ley 599 de 2000 (Codigo Penal), segun sea el caso.</p>

                        <p>Este acuerdo se rige por las leyes colombianas, incluyendo, pero no limitadas a: Ley 1621 de 2013 (Ley de Inteligencia), Ley 1712 de 2014 (Ley de Transparencia), Ley 1581 de 2012 (Proteccion de Datos Personales), Ley 1448 de 2011 (Ley de Victimas), Ley 599 de 2000 (Codigo Penal), Ley 600 de 2000 (Codigo de Procedimiento Penal), y demas normas aplicables.</p>

                        <p>Dada la naturaleza juridica del Centro Nacional de Memoria Historica y su compromiso con las victimas establecidas en el articulo 3&deg; de la Ley 1448 de 2011, el presente instrumento, su interpretacion y aplicacion se hara en virtud del principio <em>pro victima</em>, de manera tal que contribuya a garantizar la mayor proteccion a sus derechos.</p>

                        <p class="mb-0"><strong>Declaro que he leido y comprendido completamente este documento y que entiendo la naturaleza sensible de la informacion a la que tendre acceso. Reconozco la responsabilidad asumida y las consecuencias que podria enfrentar en caso de incumplimiento.</strong></p>
                    </div>

                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="acepto_compromiso" name="acepto_compromiso" value="1" required>
                            <label class="custom-control-label" for="acepto_compromiso">
                                <strong>He leido, entendido y acepto el Compromiso de Confidencialidad, Reserva y No Divulgacion</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check mr-2"></i>Aceptar Compromiso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Modal Compromiso de Acceso Interno -->
@if($entrevistador && !$entrevistador->compromiso_acceso)
<div class="modal fade" id="modalCompromisoAcceso" tabindex="-1" role="dialog" aria-labelledby="modalCompromisoAccesoLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalCompromisoAccesoLabel">
                    <i class="fas fa-id-badge mr-2"></i>Compromiso de Acceso Interno
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('perfil.compromiso_acceso') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Importante:</strong> Lea detenidamente las condiciones de acceso antes de aceptar.
                    </div>

                    <div class="card card-body bg-light" style="max-height: 350px; overflow-y: auto; font-size: 0.92rem;">
                        <p>Yo, <strong>{{ $user->name }}</strong>, funcionario(a) del <strong>Centro Nacional de Memoria Histórica &ndash; CNMH</strong>
                        @if($entrevistador->fmt_dependencia_origen !== 'Sin asignar')
                            de la <strong>{{ $entrevistador->fmt_dependencia_origen }}</strong>
                        @endif
                        , acepto las condiciones para la consulta interna del sistema de gestión de entrevistas y testimonios.</p>

                        <ul class="pl-3">
                            <li class="mb-2">Acceder al sistema <strong>exclusivamente</strong> para el desarrollo de mis funciones institucionales asignadas.</li>
                            <li class="mb-2">No compartir mis credenciales de acceso con ninguna otra persona.</li>
                            <li class="mb-2">Mantener la confidencialidad de toda la información a la que acceda en el ejercicio de mis funciones.</li>
                            <li class="mb-2">Reportar de inmediato cualquier acceso no autorizado o situación que comprometa la seguridad de la información.</li>
                            <li class="mb-2">Cumplir con todas las políticas de seguridad de la información establecidas por la entidad.</li>
                            <li class="mb-2">Reconocer que el incumplimiento puede dar lugar a consecuencias disciplinarias y/o legales.</li>
                        </ul>

                        <p class="mb-0"><strong>Declaro que he leído y acepto las condiciones de acceso al sistema.</strong></p>
                    </div>

                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="acepto_compromiso_acceso" name="acepto_compromiso_acceso" value="1" required>
                            <label class="custom-control-label" for="acepto_compromiso_acceso">
                                <strong>He leído y acepto las condiciones de acceso interno</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check mr-2"></i>Aceptar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
