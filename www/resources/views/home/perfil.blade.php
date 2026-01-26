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
                <p class="text-muted text-center">{{ $user->fmt_privilegios }}</p>

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

        <!-- Compromiso de Reserva -->
        <div class="card card-{{ $entrevistador && $entrevistador->compromiso_reserva ? 'success' : 'warning' }}">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Compromiso de Reserva</h3>
            </div>
            <div class="card-body">
                @if($entrevistador && $entrevistador->compromiso_reserva)
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="mb-0"><strong>Compromiso aceptado</strong></p>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($entrevistador->compromiso_reserva)->format('d/m/Y H:i') }}</small>
                    </div>
                @else
                    <p class="text-muted">
                        Para acceder a la informacion de testimonios, debe aceptar el compromiso de reserva y confidencialidad.
                    </p>
                    <button type="button" class="btn btn-warning btn-block" data-toggle="modal" data-target="#modalCompromiso">
                        <i class="fas fa-file-signature mr-2"></i>Aceptar Compromiso
                    </button>
                @endif
            </div>
        </div>
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
    </div>
</div>

<!-- Modal Compromiso de Reserva -->
<div class="modal fade" id="modalCompromiso" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-file-signature mr-2"></i>Compromiso de Reserva y Confidencialidad</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('perfil.compromiso') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Lea detenidamente el siguiente compromiso antes de aceptar.
                    </div>

                    <div class="card card-body bg-light" style="max-height: 300px; overflow-y: auto;">
                        <h6>COMPROMISO DE RESERVA Y CONFIDENCIALIDAD</h6>
                        <p>Yo, <strong>{{ $user->name }}</strong>, identificado(a) con el usuario <strong>{{ $user->email }}</strong>, en mi calidad de usuario del Sistema de Gestion de Testimonios, me comprometo a:</p>

                        <ol>
                            <li class="mb-2"><strong>Confidencialidad:</strong> Mantener en estricta reserva toda la informacion a la que tenga acceso a traves de este sistema, incluyendo pero no limitado a: datos personales de testimoniantes, contenido de testimonios, ubicaciones, hechos narrados y cualquier otra informacion sensible.</li>

                            <li class="mb-2"><strong>Uso apropiado:</strong> Utilizar la informacion unicamente para los fines institucionales autorizados, absteniendome de copiar, reproducir, distribuir o divulgar por cualquier medio la informacion contenida en el sistema.</li>

                            <li class="mb-2"><strong>Proteccion de datos:</strong> Cumplir con las normas de proteccion de datos personales vigentes y las politicas institucionales de seguridad de la informacion.</li>

                            <li class="mb-2"><strong>No divulgacion:</strong> No revelar a terceros no autorizados ninguna informacion obtenida a traves del sistema, incluso despues de haber cesado en mis funciones.</li>

                            <li class="mb-2"><strong>Responsabilidad:</strong> Asumir la responsabilidad por cualquier uso indebido de la informacion que realice, entendiendo que el incumplimiento de este compromiso puede dar lugar a acciones disciplinarias y legales.</li>
                        </ol>

                        <p class="mb-0">Declaro que he leido, entiendo y acepto los terminos de este compromiso de reserva y confidencialidad.</p>
                    </div>

                    <div class="form-group mt-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="acepto_compromiso" name="acepto_compromiso" value="1" required>
                            <label class="custom-control-label" for="acepto_compromiso">
                                <strong>Acepto el compromiso de reserva y confidencialidad</strong>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check mr-2"></i>Aceptar Compromiso
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
