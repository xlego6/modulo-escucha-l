<?php

namespace App\Helpers;

/**
 * Textos canónicos de los compromisos.
 *
 * Para cambiar el texto de un compromiso:
 *   1. Actualizar la constante de versión (ej. ACCESO_VERSION)
 *   2. Actualizar el método correspondiente (textoAcceso / textoReserva)
 *   3. Actualizar el modal en resources/views/home/perfil.blade.php
 *
 * El sistema almacena el texto exacto que cada usuario firmó,
 * por lo que cambios futuros no afectan firmas anteriores.
 */
class CompromisoTextos
{
    const ACCESO_VERSION  = '2026-05-v1';
    const RESERVA_VERSION = '2026-04-v1';

    /**
     * Texto del compromiso de acceso interno (con datos del usuario sustituidos).
     */
    public static function textoAcceso(string $nombreUsuario, string $dependencia): string
    {
        $parteDependencia = (trim($dependencia) && $dependencia !== 'Sin asignar')
            ? trim($dependencia)
            : '[Dependencia]';

        return "El/La funcionario/a del CNMH {$nombreUsuario}, de la {$parteDependencia}, suscribe el presente compromiso con el fin de formalizar la entrega y consulta de copias de información de expedientes testimoniales, entrevistas u otros archivos orales, producto de las actividades misional del CNMH o los que le sean allegados por organizaciones sociales de derechos humanos, en función del numeral 2 del artículo 12. del Decreto 4803 de 2011, en el Módulo de Escucha, administrado por la Dirección de Archivos de los Derechos Humanos. Esta aceptación se realiza de manera voluntaria, libre de costos y autoriza el resguardo, búsqueda, consulta, visualización y generación de informes en el Módulo de Escucha del CNMH de dichos documentos, dependiendo del nivel de reserva y sujeto a las medidas de protección u otras que dictamine la ley colombiana. El usuario que realiza la entrega, a través de la carga, se hace responsable de la integridad, producción y gestión de los documentos que hacen parte de sus expedientes y de su inclusión en instrumentos de gestión documental de la entidad, y otorga autorización para su uso por el período de vigencia del Centro Nacional de Memoria Histórica y/o quien haga sus veces.";
    }

    /**
     * Texto del compromiso de confidencialidad, reserva y no divulgación.
     * No incluye formato HTML — texto plano para almacenamiento.
     */
    public static function textoReserva(string $nombreUsuario, string $rolLabel): string
    {
        return "Yo, {$nombreUsuario}, en mi condición de {$rolLabel} vinculado(a) con el Centro Nacional de Memoria Histórica — CNMH, entiendo y acepto las siguientes condiciones, compromisos, derechos y deberes:

1. Mantener la información confidencial en condiciones de seguridad, usándola EXCLUSIVAMENTE para realizar la labor asignada. Una vez finalizada la labor o terminado el vínculo con la entidad, devolver TODA la información y NO conservar copia alguna en ningún formato o dispositivo.

2. Proteger la información confidencial, sea verbal, escrita, visual, en audio, video o cualquier otro formato recibido sobre los archivos, bases de datos e información suministrada, restringiendo su uso exclusivamente para el desarrollo de la labor asignada, sin compartirla con ninguna otra persona.

3. NO reproducir en forma mecánica o virtual la información entregada bajo ninguna circunstancia, salvo aquellas directamente necesarias para completar la labor asignada.

4. NO divulgar, alterar, entregar, facilitar, filtrar, compartir, publicar, revelar, dar a conocer, enviar, ofrecer, intercambiar, comercializar, utilizar o permitir que alguien emplee la información con cualquier fin distinto al de la labor asignada.

5. NO almacenar la información en dispositivos personales o medios no autorizados por la entidad. Realizar la labor ÚNICAMENTE en los equipos y/o sistemas designados oficialmente, cumpliendo con todos los protocolos de seguridad informática establecidos.

6. ELIMINAR de manera inmediata y definitiva cualquier archivo temporal, copia de trabajo o fragmento de información que haya sido necesario crear durante el proceso, una vez finalizado cada trabajo y entregado el producto final al supervisor.

7. INFORMAR inmediatamente al jefe inmediato sobre cualquier incidente, sustracción, pérdida, filtración o acceso no autorizado a la información bajo custodia.

8. RECONOCER que la información a la que se tiene acceso contiene relatos y datos de víctimas del conflicto y personas en situación de vulnerabilidad, con el compromiso de manejarla con el máximo respeto y sensibilidad ética.

9. ABSTENERSE de realizar búsquedas adicionales sobre las personas o hechos mencionados en las entrevistas o información a la que accedo, limitándose exclusivamente a la labor técnica asignada.

10. FACILITAR cualquier información necesaria para el seguimiento y verificación de las actividades cuando sea requerido por la entidad.

11. MANTENER la confidencialidad de la información incluso después de finalizada la vinculación con la entidad.

Entiendo plenamente que el incumplimiento del presente COMPROMISO DE CONFIDENCIALIDAD, RESERVA Y NO DIVULGACIÓN puede acarrear la terminación inmediata de mi vínculo contractual con la entidad, sanciones disciplinarias, responsabilidades civiles y responsabilidades penales bajo los delitos tipificados en la Ley 599 de 2000 (Código Penal).

Este acuerdo se rige por las leyes colombianas, incluyendo: Ley 1621 de 2013, Ley 1712 de 2014, Ley 1581 de 2012, Ley 1448 de 2011, Ley 599 de 2000, Ley 600 de 2000.

Declaro que he leído y comprendido completamente este documento y que entiendo la naturaleza sensible de la información a la que tendré acceso. Reconozco la responsabilidad asumida y las consecuencias que podría enfrentar en caso de incumplimiento.";
    }
}
