@extends('layouts.app')

@section('title', 'Ayuda - Testimonios')

@section('content_header', 'Ayuda')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-question-circle mr-2"></i>
                    Preguntas Frecuentes (FAQ)
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    Encuentre respuestas a las preguntas mas comunes sobre el uso del sistema.
                </p>

                <div class="accordion" id="accordionFaq">
                    {{-- Pregunta 1 --}}
                    <div class="card">
                        <div class="card-header" id="heading1">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    Necesito ayuda, ¿con quien me puedo comunicar?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse1" class="collapse show" aria-labelledby="heading1" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>Puede comunicarse con el administrador del sistema o el personal de soporte tecnico de su organizacion.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 2 --}}
                    <div class="card">
                        <div class="card-header" id="heading2">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Como puedo crear una nueva entrevista?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse2" class="collapse" aria-labelledby="heading2" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>Para crear una nueva entrevista:</p>
                                <ol>
                                    <li>Vaya al menu lateral y haga clic en <strong>Entrevistas</strong></li>
                                    <li>Haga clic en el boton <strong>Nueva Entrevista</strong></li>
                                    <li>Complete los datos requeridos en cada paso del formulario</li>
                                    <li>Adjunte los archivos correspondientes (consentimiento, audio, video, etc.)</li>
                                    <li>Haga clic en <strong>Guardar</strong> para finalizar</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 3 --}}
                    <div class="card">
                        <div class="card-header" id="heading3">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Puedo agregar mas de un archivo adjunto por tipo?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse3" class="collapse" aria-labelledby="heading3" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>Si. Durante la creacion de la entrevista puede cargar un archivo por cada tipo de adjunto. Posteriormente, puede utilizar la opcion <strong>"Gestionar archivos adjuntos"</strong> (disponible en el listado de entrevistas) para agregar archivos adicionales.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 4 --}}
                    <div class="card">
                        <div class="card-header" id="heading4">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Puedo crear una entrevista sin todos los adjuntos?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse4" class="collapse" aria-labelledby="heading4" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>Si, aunque no es lo recomendable. El sistema emitira una alerta que debera confirmar. Posteriormente puede agregar archivos mediante la opcion <strong>"Gestionar archivos adjuntos"</strong>.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 5 --}}
                    <div class="card">
                        <div class="card-header" id="heading5">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse5" aria-expanded="false" aria-controls="collapse5">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Puedo eliminar una entrevista ya creada?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse5" class="collapse" aria-labelledby="heading5" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>No. Todas las entrevistas creadas se registran permanentemente para mantener la trazabilidad del sistema. Los usuarios con permisos de administrador pueden <strong>anular</strong> una entrevista, pero no eliminarla completamente.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 6 --}}
                    <div class="card">
                        <div class="card-header" id="heading6">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse6" aria-expanded="false" aria-controls="collapse6">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Como puedo eliminar un archivo adjunto subido por error?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse6" class="collapse" aria-labelledby="heading6" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>En el listado de entrevistas encontrara la opcion <strong>"Gestionar archivos adjuntos"</strong>, la cual permite agregar nuevos adjuntos o eliminar archivos existentes.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 7 --}}
                    <div class="card">
                        <div class="card-header" id="heading7">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse7" aria-expanded="false" aria-controls="collapse7">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Como funciona la busqueda de entrevistas?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse7" class="collapse" aria-labelledby="heading7" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>El sistema cuenta con una <strong>Buscadora</strong> que permite filtrar entrevistas por multiples criterios:</p>
                                <ul>
                                    <li>Codigo de entrevista</li>
                                    <li>Tipo de testimonio</li>
                                    <li>Departamento y municipio</li>
                                    <li>Fecha de realizacion</li>
                                    <li>Datos de la persona entrevistada</li>
                                </ul>
                                <p>Acceda desde el menu lateral haciendo clic en <strong>Buscadora</strong>.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 8 --}}
                    <div class="card">
                        <div class="card-header" id="heading8">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse8" aria-expanded="false" aria-controls="collapse8">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Como cambio mi contraseña?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse8" class="collapse" aria-labelledby="heading8" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>Para cambiar su contraseña:</p>
                                <ol>
                                    <li>Haga clic en su nombre de usuario en la esquina superior derecha</li>
                                    <li>Seleccione <strong>Mi Perfil</strong></li>
                                    <li>En la seccion "Cambiar Contraseña", ingrese su contraseña actual y la nueva contraseña</li>
                                    <li>Confirme la nueva contraseña y haga clic en <strong>Cambiar Contraseña</strong></li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 9 --}}
                    <div class="card">
                        <div class="card-header" id="heading9">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse9" aria-expanded="false" aria-controls="collapse9">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Que es el compromiso de reserva?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse9" class="collapse" aria-labelledby="heading9" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>El compromiso de reserva es un acuerdo de confidencialidad que debe aceptar antes de acceder a informacion sensible de las entrevistas. Este compromiso garantiza la proteccion de los datos personales de las personas entrevistadas.</p>
                                <p>Puede aceptar el compromiso desde su <strong>Perfil de usuario</strong>.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Pregunta 10 --}}
                    <div class="card">
                        <div class="card-header" id="heading10">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#collapse10" aria-expanded="false" aria-controls="collapse10">
                                    <i class="fas fa-chevron-right mr-2"></i>
                                    ¿Que tipos de archivos puedo adjuntar?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse10" class="collapse" aria-labelledby="heading10" data-parent="#accordionFaq">
                            <div class="card-body">
                                <p>El sistema permite adjuntar los siguientes tipos de archivos:</p>
                                <ul>
                                    <li><strong>Consentimiento informado:</strong> PDF, imagen</li>
                                    <li><strong>Audio:</strong> MP3, WAV, M4A, OGG</li>
                                    <li><strong>Video:</strong> MP4, AVI, MOV</li>
                                    <li><strong>Documentos:</strong> PDF, Word, Excel</li>
                                    <li><strong>Transcripcion:</strong> PDF, Word, texto plano</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Sección de niveles de acceso --}}
<div class="row">
    <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield mr-2"></i>
                    Niveles de Acceso
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">El sistema utiliza diferentes niveles de acceso para proteger la informacion:</p>
                <table class="table table-bordered table-striped">
                    <thead class="thead-dark">
                        <tr>
                            <th>Nivel</th>
                            <th>Descripcion</th>
                            <th>Permisos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-danger">Administrador</span></td>
                            <td>Acceso completo al sistema</td>
                            <td>
                                <ul class="mb-0">
                                    <li>Gestion de usuarios</li>
                                    <li>Configuracion del sistema</li>
                                    <li>Acceso a todas las entrevistas</li>
                                    <li>Desclasificacion</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Lider</span></td>
                            <td>Supervision y estadisticas</td>
                            <td>
                                <ul class="mb-0">
                                    <li>Ver estadisticas generales</li>
                                    <li>Exportar datos</li>
                                    <li>Gestionar permisos</li>
                                    <li>Ver mapa de entrevistas</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-primary">Entrevistador</span></td>
                            <td>Recoleccion de testimonios</td>
                            <td>
                                <ul class="mb-0">
                                    <li>Crear y editar entrevistas propias</li>
                                    <li>Gestionar adjuntos</li>
                                    <li>Buscar entrevistas</li>
                                </ul>
                            </td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-secondary">Transcriptor</span></td>
                            <td>Edicion de transcripciones</td>
                            <td>
                                <ul class="mb-0">
                                    <li>Editar transcripciones asignadas</li>
                                    <li>Revisar y corregir texto</li>
                                    <li>Anonimizacion</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Sección de contacto --}}
<div class="row">
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-headset mr-2"></i>
                    Soporte Tecnico
                </h3>
            </div>
            <div class="card-body">
                <p>Si tiene problemas tecnicos o necesita asistencia adicional, contacte al equipo de soporte:</p>
                <ul class="list-unstyled">
                    <li><i class="fas fa-envelope mr-2 text-muted"></i> soporte@ejemplo.com</li>
                    <li><i class="fas fa-clock mr-2 text-muted"></i> Lunes a Viernes, 8:00 AM - 6:00 PM</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb mr-2"></i>
                    Consejos Rapidos
                </h3>
            </div>
            <div class="card-body">
                <ul>
                    <li>Guarde su trabajo frecuentemente</li>
                    <li>Use navegadores actualizados (Chrome, Firefox, Edge)</li>
                    <li>Verifique su conexion a internet antes de subir archivos grandes</li>
                    <li>Cierre sesion al terminar de usar el sistema</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection

@section('css')
<style>
    .accordion .card {
        margin-bottom: 0.5rem;
        border-radius: 0.25rem !important;
    }
    .accordion .card-header {
        padding: 0;
        background-color: #f8f9fa;
    }
    .accordion .btn-link {
        color: #333;
        text-decoration: none;
        font-weight: 500;
    }
    .accordion .btn-link:hover {
        color: #007bff;
        text-decoration: none;
    }
    .accordion .btn-link[aria-expanded="true"] .fa-chevron-right {
        transform: rotate(90deg);
        transition: transform 0.2s;
    }
    .accordion .btn-link[aria-expanded="false"] .fa-chevron-right {
        transform: rotate(0deg);
        transition: transform 0.2s;
    }
</style>
@endsection
