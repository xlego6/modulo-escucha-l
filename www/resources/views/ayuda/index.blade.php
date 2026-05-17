@extends('layouts.app')

@section('title', 'Ayuda - Módulo de Escucha CNMH')

@section('content_header', 'Ayuda')

@section('css')
<style>
    details.faq-item {
        margin-bottom: 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        background: #fff;
    }
    details.faq-item summary {
        padding: 0.85rem 1rem;
        font-weight: 500;
        color: #333;
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background-color: #f8f9fa;
        border-radius: 0.25rem;
        user-select: none;
    }
    details.faq-item summary::-webkit-details-marker { display: none; }
    details.faq-item summary .faq-chevron {
        margin-left: auto;
        transition: transform 0.2s;
        color: #6c757d;
        flex-shrink: 0;
    }
    details.faq-item[open] summary {
        border-bottom: 1px solid #dee2e6;
        border-radius: 0.25rem 0.25rem 0 0;
        color: #007bff;
    }
    details.faq-item[open] summary .faq-chevron {
        transform: rotate(90deg);
    }
    details.faq-item summary:hover { background-color: #e9ecef; }
    details.faq-item .faq-body {
        padding: 1rem 1.25rem;
    }
    details.faq-item .faq-body p:last-child,
    details.faq-item .faq-body ul:last-child,
    details.faq-item .faq-body ol:last-child { margin-bottom: 0; }
</style>
@endsection

@section('content')

{{-- Manual de usuario --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-book mr-2"></i>Manual de Usuario
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Descargue el manual completo del Módulo de Escucha CNMH con instrucciones detalladas para todos los perfiles de usuario.</p>
                <a href="{{ asset('documentos/manual_modulo_escucha.pdf') }}" target="_blank" class="btn btn-primary btn-sm">
                    <i class="fas fa-file-pdf mr-2"></i>Descargar Manual de Usuario (PDF)
                </a>
            </div>
        </div>
    </div>
</div>

{{-- FAQ --}}
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-question-circle mr-2"></i>Preguntas Frecuentes
                </h3>
            </div>
            <div class="card-body">

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-headset text-muted"></i>
                        Necesito ayuda. ¿Con quién me puedo comunicar?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Puede comunicarse con el administrador del sistema al correo <strong>leonardo.sarmiento@cnmh.gov.co</strong>, de lunes a viernes de 8:00 AM a 6:00 PM.</p>
                        <p class="mb-0">Para solicitar cambio de rol (Gestor de conocimiento, Líder de procesamiento o Transcriptor), también use ese mismo correo, indicando la función que necesita desempeñar y la Dirección Técnica que lo autoriza.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-sign-in-alt text-muted"></i>
                        ¿Cómo ingreso al sistema?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Hay dos formas de acceder:</p>
                        <ul>
                            <li><strong>Dentro de la red del CNMH:</strong> Ingrese directamente desde su navegador a <code>http://192.168.0.88:8001/login</code>.</li>
                            <li><strong>De forma remota:</strong> Primero conéctese a la VPN (Sophos) de la entidad y luego acceda a la misma dirección.</li>
                        </ul>
                        <p class="mb-0">En ambos casos, autentíquese con sus credenciales del directorio activo del CNMH. Al ingresar por primera vez debe aceptar el <strong>Compromiso de Acceso Interno</strong> para habilitar las funcionalidades del módulo.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-users text-muted"></i>
                        ¿Cuáles son los perfiles de usuario y qué puede hacer cada uno?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>El sistema tiene cinco perfiles. Al ingresar por primera vez se le asigna automáticamente el perfil de <strong>Entrevistador</strong>:</p>
                        <ul>
                            <li><strong>Administrador:</strong> Acceso completo a todas las funcionalidades del sistema.</li>
                            <li><strong>Líder de procesamiento:</strong> Asignación, edición, revisión y aprobación de procesamientos (transcripción, detección de entidades, anonimización). Puede ver el listado completo de entrevistas de cualquier dependencia.</li>
                            <li><strong>Gestor de conocimiento:</strong> Gestión de permisos y adjuntos de entrevistas de su propia dependencia. Puede otorgar permisos a Entrevistadores propios y externos.</li>
                            <li><strong>Entrevistador:</strong> Crea, describe y carga archivos de sus propias entrevistas. Puede consultar estadísticas y solicitar permisos de acceso a entrevistas de otras dependencias.</li>
                            <li><strong>Transcriptor:</strong> Edita transcripciones automáticas y versiones anonimizadas asignadas, para revisión y aprobación del Líder de procesamiento.</li>
                        </ul>
                        <p class="mb-0">Para cambiar su perfil, contacte al administrador indicando el rol requerido y la autorización de su Dirección Técnica.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-file-signature text-muted"></i>
                        ¿Qué compromisos debo aceptar para usar el sistema?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Todos los usuarios deben aceptar el <strong>Compromiso de Acceso Interno</strong> antes de acceder a las funcionalidades del módulo. Puede hacerlo desde su Perfil de usuario.</p>
                        <p class="mb-0">Adicionalmente, los perfiles de <strong>Líder de procesamiento</strong> y <strong>Transcriptor</strong> deben aceptar un segundo compromiso: el <strong>Compromiso de Confidencialidad y Reserva</strong>, requerido para procesar información de las entrevistas. Desde su perfil puede descargar el certificado de los compromisos aceptados.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-plus-circle text-muted"></i>
                        ¿Cómo creo una nueva entrevista?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Esta función está disponible para los perfiles de <strong>Entrevistador, Gestor de conocimiento, Líder de procesamiento y Administrador</strong>.</p>
                        <ol>
                            <li>Ingrese al menú lateral en <strong>Entrevistas</strong> y haga clic en <strong>Nueva entrevista</strong>.</li>
                            <li><strong>Sección 1 — Testimoniales:</strong> Datos generales de la toma del testimonio. Los campos con asterisco rojo son obligatorios.</li>
                            <li><strong>Sección 2 — Testimoniantes:</strong> Datos de la(s) persona(s) entrevistada(s) y consentimientos informados.</li>
                            <li><strong>Sección 3 — Contenido:</strong> Metadatos del contenido del testimonio. Al finalizar haga clic en <strong>Finalizar</strong>.</li>
                            <li>Una vez creada, acceda a <strong>Gestionar Archivos Adjuntos</strong> para cargar los archivos del expediente.</li>
                        </ol>
                        <p class="mb-0">En caso de dudas sobre el diligenciamiento de los campos, consulte el documento <em>PCA-GU-011 "Guía de Descripción de Fondos Documentales y Expedientes Testimoniales"</em> en la intranet del CNMH.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-paperclip text-muted"></i>
                        ¿Qué tipos de archivos puedo adjuntar y cuál es el tamaño máximo?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Desde <strong>Gestionar Archivos Adjuntos</strong> puede cargar los siguientes tipos:</p>
                        <ul>
                            <li><strong>Audio/Video de la entrevista</strong></li>
                            <li><strong>Consentimiento informado</strong></li>
                            <li><strong>Transcripción automatizada</strong></li>
                            <li><strong>Transcripción final</strong></li>
                            <li><strong>Versión pública</strong></li>
                            <li><strong>Otros documentos</strong></li>
                        </ul>
                        <p>El tamaño máximo por archivo es <strong>500 MB</strong>. Si un audio o video supera ese peso, puede usar la opción de conversión a formato M4A disponible en el gestor de adjuntos para reducir su tamaño.</p>
                        <p class="mb-0">Puede cargar archivos adicionales en cualquier momento volviendo a <strong>Gestionar Archivos Adjuntos</strong> desde el listado de entrevistas.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-search text-muted"></i>
                        ¿Cómo funciona la búsqueda de entrevistas?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>La <strong>Buscadora</strong> está disponible para los perfiles de <strong>Administrador, Gestor de conocimiento y Entrevistador</strong>. Acceda desde el menú lateral.</p>
                        <ul>
                            <li>La búsqueda no es sensible a mayúsculas ni tildes.</li>
                            <li>Use <strong>comillas</strong> para buscar una frase exacta: <code>"desplazamiento forzado"</code>.</li>
                            <li>Use conectores booleanos en mayúsculas: <code>desplazamiento AND Cauca</code>, <code>Cauca OR Nariño</code>, <code>conflicto NOT urbano</code>.</li>
                        </ul>
                        <p>Puede complementar la búsqueda con filtros de <strong>departamento, municipio, hechos victimizantes, práctica de resistencia y dependencia de origen</strong>. Los resultados se muestran en páginas de 25 entrevistas.</p>
                        <p class="mb-0">Si una entrevista aparece en los resultados pero no puede acceder a ella, puede hacer clic en <strong>Solicitar acceso</strong> para gestionar el permiso correspondiente.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-key text-muted"></i>
                        ¿Cómo solicito permiso para acceder a una entrevista de otra dependencia?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>Los <strong>Entrevistadores</strong> solo pueden acceder a sus propios testimonios; los <strong>Gestores de conocimiento</strong>, solo a los de su dependencia. Para acceder a otros expedientes:</p>
                        <ol>
                            <li>Localice la entrevista en la <strong>Buscadora</strong> y haga clic en <strong>Solicitar acceso</strong>.</li>
                            <li>Complete la justificación de la solicitud y haga clic en <strong>Enviar Solicitud</strong>.</li>
                            <li>Haga seguimiento desde <strong>Administración → Permisos → Mis Solicitudes</strong>.</li>
                            <li>Cuando el Gestor de conocimiento apruebe la solicitud, podrá acceder a la entrevista directamente desde el código en esa misma pantalla. La columna <strong>Vence</strong> indica hasta cuándo tiene acceso.</li>
                        </ol>
                        <p class="mb-0">Si la solicitud es rechazada o revocada, verá el motivo en la columna <strong>Motivo</strong> de la lista de solicitudes.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-microphone-alt text-muted"></i>
                        ¿Cómo funciona el procesamiento de transcripciones?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <p>El módulo cuenta con transcripción automática basada en reconocimiento de voz (ASR). El flujo es:</p>
                        <ol>
                            <li><strong>Líder de procesamiento</strong> inicia la transcripción automática desde <strong>Procesamientos → Transcripción</strong>.</li>
                            <li>El mismo Líder asigna la edición del texto a un <strong>Transcriptor</strong> desde <strong>Procesamientos → Edición</strong>.</li>
                            <li>El <strong>Transcriptor</strong> edita el texto verificando con el audio. Puede guardar borradores (<kbd>Ctrl+S</kbd>) y navegar el audio con <kbd>Ctrl+Espacio</kbd>, <kbd>Ctrl+←</kbd>, <kbd>Ctrl+→</kbd>. Al terminar, hace clic en <strong>Enviar a Revisión</strong>.</li>
                            <li>El <strong>Líder de procesamiento</strong> revisa y decide <strong>Aprobar</strong> (el documento queda guardado como Transcripción Final en el expediente) o <strong>Rechazar y Devolver</strong> con comentarios al Transcriptor.</li>
                        </ol>
                        <p class="mb-0">Para criterios de edición y validación, consulte el documento <em>PCA-GU-010 "Guía de edición y validación de transcripciones CNMH"</em> en la intranet del CNMH.</p>
                    </div>
                </details>

                <details class="faq-item">
                    <summary>
                        <i class="fas fa-lock text-muted"></i>
                        ¿Cómo cambio mi contraseña?
                        <i class="fas fa-chevron-right faq-chevron"></i>
                    </summary>
                    <div class="faq-body">
                        <ol class="mb-0">
                            <li>Haga clic en su nombre de usuario en la esquina superior derecha de la pantalla.</li>
                            <li>Seleccione <strong>Mi Perfil</strong>.</li>
                            <li>En la sección <strong>Cambiar Contraseña</strong>, ingrese su contraseña actual y la nueva contraseña.</li>
                            <li>Confirme la nueva contraseña y haga clic en <strong>Cambiar Contraseña</strong>.</li>
                        </ol>
                    </div>
                </details>

            </div>
        </div>
    </div>
</div>

{{-- Niveles de acceso --}}
<div class="row">
    <div class="col-12">
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-shield mr-2"></i>Perfiles de Acceso
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">El sistema utiliza cinco perfiles para controlar el acceso y las funciones disponibles:</p>
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>Perfil</th>
                            <th>Función principal</th>
                            <th>Acceso a entrevistas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-danger">Administrador</span></td>
                            <td>Gestión completa del sistema</td>
                            <td>Todas las entrevistas de cualquier dependencia</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Líder de procesamiento</span></td>
                            <td>Asignación, revisión y aprobación de transcripciones y anonimizaciones</td>
                            <td>Puede ver el listado completo; acceso al contenido requiere permiso</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-info">Gestor de conocimiento</span></td>
                            <td>Gestión de permisos y adjuntos de su dependencia</td>
                            <td>Entrevistas de su dependencia; puede otorgar permisos a otros</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-primary">Entrevistador</span></td>
                            <td>Carga y descripción de testimonios propios</td>
                            <td>Sus propias entrevistas; acceso a otras mediante solicitud de permiso</td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-secondary">Transcriptor</span></td>
                            <td>Edición de transcripciones y anonimizaciones asignadas</td>
                            <td>Solo las entrevistas asignadas para procesamiento</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Contacto y consejos --}}
<div class="row">
    <div class="col-md-6">
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-headset mr-2"></i>Soporte Técnico
                </h3>
            </div>
            <div class="card-body">
                <p>Si tiene problemas técnicos o necesita asistencia adicional, contacte al administrador del sistema:</p>
                <ul class="list-unstyled mb-0">
                    <li><i class="fas fa-envelope mr-2 text-muted"></i> leonardo.sarmiento@cnmh.gov.co</li>
                    <li><i class="fas fa-clock mr-2 text-muted"></i> Lunes a Viernes, 8:00 AM – 6:00 PM</li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-warning card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb mr-2"></i>Consejos Rápidos
                </h3>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Guarde el avance con frecuencia al editar transcripciones (<kbd>Ctrl+S</kbd>).</li>
                    <li>Use navegadores actualizados: Chrome, Firefox o Edge.</li>
                    <li>Verifique su conexión a internet antes de subir archivos de más de 100 MB.</li>
                    <li>Si trabaja de forma remota, asegúrese de estar conectado a la VPN antes de acceder al sistema.</li>
                    <li>Cierre sesión al terminar de usar el sistema.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

@endsection
