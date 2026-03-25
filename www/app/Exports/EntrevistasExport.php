<?php

namespace App\Exports;

use App\Models\Entrevista;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EntrevistasExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filtros;

    public function __construct(array $filtros = [])
    {
        $this->filtros = $filtros;
    }

    public function query()
    {
        $query = Entrevista::where('id_activo', 1)
            ->with([
                'rel_entrevistador',
                'rel_entrevistador.rel_usuario',
                'rel_lugar_entrevista',
                'rel_lugar_hechos',
                'rel_dependencia_origen',
                'rel_equipo_estrategia',
                'rel_tipo_testimonio',
                'rel_idioma',
                'rel_idiomas',
                'rel_area_compatible',
                'rel_formatos',
                'rel_modalidades',
                'rel_necesidades_reparacion',
                'rel_adjuntos',
                'rel_adjuntos.rel_tipo',
                'rel_personas_entrevistadas',
                'rel_personas_entrevistadas.rel_persona',
                'rel_personas_entrevistadas.rel_consentimiento',
                'rel_contenido',
                'rel_contenido.rel_poblaciones',
                'rel_contenido.rel_ocupaciones',
                'rel_contenido.rel_hechos_victimizantes',
                'rel_contenido.rel_responsables',
                'rel_contenido.rel_practicas_resistencia',
            ]);

        if (!empty($this->filtros['fecha_desde'])) {
            $query->where('fecha_toma_inicial', '>=', $this->filtros['fecha_desde']);
        }

        if (!empty($this->filtros['fecha_hasta'])) {
            $query->where('fecha_toma_final', '<=', $this->filtros['fecha_hasta']);
        }

        if (!empty($this->filtros['id_territorio'])) {
            $query->where('id_territorio', $this->filtros['id_territorio']);
        }

        if (!empty($this->filtros['id_entrevistador'])) {
            $query->where('id_entrevistador', $this->filtros['id_entrevistador']);
        }

        if (!empty($this->filtros['id_dependencia_origen'])) {
            $query->where('id_dependencia_origen', $this->filtros['id_dependencia_origen']);
        }

        if (!empty($this->filtros['id_tipo_testimonio'])) {
            $query->where('id_tipo_testimonio', $this->filtros['id_tipo_testimonio']);
        }

        // Filtro por presencia de adjuntos
        if (isset($this->filtros['tiene_adjuntos']) && $this->filtros['tiene_adjuntos'] !== '') {
            if ($this->filtros['tiene_adjuntos'] == '1') {
                $query->whereHas('rel_adjuntos');
            } elseif ($this->filtros['tiene_adjuntos'] == '0') {
                $query->whereDoesntHave('rel_adjuntos');
            }
        }

        // Filtro por tipo de adjunto
        if (!empty($this->filtros['id_tipo_adjunto'])) {
            $query->whereHas('rel_adjuntos', function ($q) {
                $q->where('id_tipo', $this->filtros['id_tipo_adjunto']);
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            // DATOS TECNICOS
            'ID',
            'Codigo',
            'Fecha Creacion',

            // PASO 1: DATOS TESTIMONIALES
            'Titulo',
            'Dependencia de Origen',
            'Equipo/Estrategia',
            'Proyecto/Investigacion',
            'Tipo de Testimonio',
            'Formato(s) del Testimonio',
            'Num. Testimoniantes',
            'Departamento Toma',
            'Municipio Toma',
            'Modalidad(es)',
            'Idioma(s)',
            'Detalle Idiomas',
            'Fecha Toma Inicial',
            'Fecha Toma Final',
            'Necesidades Reparacion',
            'Areas Compatibles',
            'Tiene Anexos',
            'Descripcion Anexos',
            'Observaciones Toma',
            'Entrevistador (usuario)',
            'Nombre Entrevistador',

            // PASO 2: TESTIMONIANTES
            'Testimoniante(s)',
            'Tipo(s) Testimoniante',

            // CONSENTIMIENTO - una columna por pregunta
            'Cons: Tipo Documento',
            'Cons: Es Menor de Edad',
            'Cons: Autoriza Entrevista',
            'Cons: Permite Grabacion',
            'Cons: Permite Proc. Misional',
            'Cons: Permite Uso/Conserv./Consulta',
            'Cons: Riesgo de Seguridad',
            'Cons: Autoriza Datos Personales',
            'Cons: Autoriza Datos Sensibles',
            'Cons: Observaciones',

            // PRUEBA DE DAÑO
            'P. Dano: Afecta Derechos Privados',
            'P. Dano: Afecta Intereses Publicos',
            'P. Dano: Inteligencia/Contraintelig.',
            'P. Dano: NNA',
            'Clasificacion Prueba de Dano',

            // PASO 3: CONTENIDO
            'Fecha Hechos Inicial',
            'Fecha Hechos Final',
            'Poblaciones Mencionadas',
            'Otras Poblaciones Mencionadas',
            'Ocupaciones Mencionadas',
            'Otras Ocupaciones Mencionadas',
            'Hechos Victimizantes',
            'Otros Hechos Victimizantes',
            'Practicas de Resistencia',
            'Detalle Resistencias',
            'Detalle Grupos Etnicos',
            'Responsables Colectivos',
            'Responsables Individuales',
            'Temas Abordados',

            // ADJUNTOS
            'Tiene Adjuntos',
            'Cantidad Adjuntos',
            'Tipos de Adjuntos',
            'Adjuntos Audio',
            'Adjuntos Video',
            'Adjuntos Documento',
            'Duracion Total (min)',
        ];
    }

    public function map($entrevista): array
    {
        // Formatos del testimonio
        $formatos = $entrevista->rel_formatos->pluck('descripcion')->implode(', ');

        // Modalidades
        $modalidades = $entrevista->rel_modalidades->pluck('descripcion')->implode(', ');

        // Necesidades de reparacion
        $necesidades = $entrevista->rel_necesidades_reparacion->pluck('descripcion')->implode(', ');

        // Testimoniantes y consentimientos
        $testimoniantes = [];
        $tiposTestimoniante = [];

        $consTipoDoc    = [];
        $consMenorEdad  = [];
        $consAutoriza   = [];
        $consGrabacion  = [];
        $consProc       = [];
        $consUso        = [];
        $consRiesgo     = [];
        $consDatosP     = [];
        $consDatosS     = [];
        $consObs        = [];

        $danoPriva      = [];
        $danoPubli      = [];
        $danoIntelig    = [];
        $danoNna        = [];

        foreach ($entrevista->rel_personas_entrevistadas as $i => $pe) {
            $num = 'P' . ($i + 1);

            if ($pe->rel_persona) {
                $testimoniantes[] = trim(
                    $pe->rel_persona->primer_nombre . ' ' .
                    $pe->rel_persona->segundo_nombre . ' ' .
                    $pe->rel_persona->primer_apellido . ' ' .
                    $pe->rel_persona->segundo_apellido
                );
            }
            $tiposTestimoniante[] = $pe->fmt_tipo;

            $cons = $pe->rel_consentimiento;

            if ($cons) {
                $obs = $cons->observaciones ?? '';
                $esOtro = str_contains($obs, '[CONSENTIMIENTO_OTRO]');

                // Tipo de documento
                if ($esOtro) {
                    $consTipoDoc[] = "{$num}: Otro";
                } elseif ($cons->tiene_documento_autorizacion) {
                    $consTipoDoc[] = "{$num}: Si";
                } else {
                    $consTipoDoc[] = "{$num}: No";
                }

                // Campos booleanos (solo aplican si tiene documento)
                $tieneDoc = $cons->tiene_documento_autorizacion;
                $consMenorEdad[] = $num . ': ' . $this->formatBool($tieneDoc ? $cons->es_menor_edad : null);
                $consAutoriza[]  = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_ser_entrevistado : null);
                $consGrabacion[] = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_grabacion : null);
                $consProc[]      = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_procesamiento_misional : null);
                $consUso[]       = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_uso_conservacion_consulta : null);
                $consRiesgo[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->considera_riesgo_seguridad : null);
                $consDatosP[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_datos_personales_sin_anonimizar : null);
                $consDatosS[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_datos_sensibles_sin_anonimizar : null);

                // Observaciones (limpiar el marcador OTRO para el excel)
                $obsLimpia = $esOtro ? trim(str_replace('[CONSENTIMIENTO_OTRO]', '', $obs)) : $obs;
                if ($obsLimpia) $consObs[] = "{$num}: {$obsLimpia}";

                // Prueba de daño
                $danoPriva[]   = $num . ': ' . $this->formatDano($cons->prueba_dano_derechos_privados);
                $danoPubli[]   = $num . ': ' . $this->formatDano($cons->prueba_dano_intereses_publicos);
                $danoIntelig[] = $num . ': ' . $this->formatDano($cons->prueba_dano_inteligencia);
                $danoNna[]     = $num . ': ' . $this->formatDano($cons->prueba_dano_nna);
            }
        }

        // Clasificacion prueba de daño: la más restrictiva entre todos los consentimientos
        $clasificacion = $this->clasificacionEntrevista($entrevista->rel_personas_entrevistadas);

        // Contenido del testimonio
        $contenido = $entrevista->rel_contenido;
        $poblaciones = $contenido ? $contenido->rel_poblaciones->pluck('descripcion')->implode(', ') : '';
        $ocupaciones = $contenido ? $contenido->rel_ocupaciones->pluck('descripcion')->implode(', ') : '';
        $hechos = $contenido ? $contenido->rel_hechos_victimizantes->pluck('descripcion')->implode(', ') : '';
        $responsables = $contenido ? $contenido->rel_responsables->pluck('descripcion')->implode(', ') : '';
        $practicas = $contenido ? $contenido->rel_practicas_resistencia->pluck('descripcion')->implode(', ') : '';

        // Adjuntos
        $adjuntos = $entrevista->rel_adjuntos;
        $tieneAdjuntos = $adjuntos->count() > 0 ? 'Si' : 'No';
        $cantidadAdjuntos = $adjuntos->count();
        $tiposAdjuntos = $adjuntos->map(function ($adj) {
            return $adj->rel_tipo ? $adj->rel_tipo->descripcion : 'Sin tipo';
        })->unique()->implode(', ');

        $adjuntosAudio = $adjuntos->filter(fn($adj) => $adj->es_audio)->count();
        $adjuntosVideo = $adjuntos->filter(fn($adj) => $adj->es_video)->count();
        $adjuntosDocumento = $adjuntos->filter(fn($adj) => $adj->es_documento)->count();

        $duracionTotal = $adjuntos->sum('duracion');
        $duracionMinutos = $duracionTotal > 0 ? round($duracionTotal / 60, 2) : '';

        return [
            // DATOS TECNICOS
            $entrevista->id_e_ind_fvt,
            $entrevista->entrevista_codigo,
            $entrevista->created_at ? $entrevista->created_at->format('Y-m-d H:i:s') : '',

            // PASO 1: DATOS TESTIMONIALES
            $entrevista->titulo,
            $entrevista->rel_dependencia_origen ? $entrevista->rel_dependencia_origen->descripcion : '',
            $entrevista->rel_equipo_estrategia ? $entrevista->rel_equipo_estrategia->descripcion : '',
            $entrevista->nombre_proyecto ?? '',
            $entrevista->rel_tipo_testimonio ? $entrevista->rel_tipo_testimonio->descripcion : '',
            $formatos,
            $entrevista->num_testimoniantes,
            $entrevista->rel_entrevistador ? $entrevista->rel_entrevistador->id_territorio : '',
            $entrevista->rel_lugar_entrevista ? $entrevista->rel_lugar_entrevista->descripcion : '',
            $modalidades,
            $entrevista->rel_idiomas->count() > 0
                ? $entrevista->rel_idiomas->pluck('descripcion')->implode(', ')
                : ($entrevista->rel_idioma ? $entrevista->rel_idioma->descripcion : ''),
            $entrevista->detalle_idiomas ?? '',
            $entrevista->fecha_toma_inicial,
            $entrevista->fecha_toma_final,
            $necesidades,
            $entrevista->rel_area_compatible ? $entrevista->rel_area_compatible->descripcion : '',
            $entrevista->tiene_anexos ? 'Si' : 'No',
            $entrevista->descripcion_anexos,
            $entrevista->observaciones_toma,
            $entrevista->rel_entrevistador && $entrevista->rel_entrevistador->rel_usuario
                ? $entrevista->rel_entrevistador->rel_usuario->name
                : '',
            $entrevista->nombre_entrevistador
                ?: ($entrevista->rel_entrevistador?->rel_usuario?->name ?? ''),

            // PASO 2: TESTIMONIANTES
            implode(' | ', array_filter($testimoniantes)),
            implode(' | ', array_unique(array_filter($tiposTestimoniante))),

            // CONSENTIMIENTO
            implode(' | ', $consTipoDoc),
            implode(' | ', $consMenorEdad),
            implode(' | ', $consAutoriza),
            implode(' | ', $consGrabacion),
            implode(' | ', $consProc),
            implode(' | ', $consUso),
            implode(' | ', $consRiesgo),
            implode(' | ', $consDatosP),
            implode(' | ', $consDatosS),
            implode(' | ', $consObs),

            // PRUEBA DE DAÑO
            implode(' | ', $danoPriva),
            implode(' | ', $danoPubli),
            implode(' | ', $danoIntelig),
            implode(' | ', $danoNna),
            $clasificacion,

            // PASO 3: CONTENIDO
            $contenido ? $contenido->fecha_hechos_inicial : '',
            $contenido ? $contenido->fecha_hechos_final : '',
            $poblaciones,
            $contenido ? $contenido->otras_poblaciones_mencionadas : '',
            $ocupaciones,
            $contenido ? $contenido->otras_ocupaciones_mencionadas : '',
            $hechos,
            $contenido ? $contenido->otros_hechos_victimizantes : '',
            $practicas,
            $contenido ? $contenido->detalle_resistencias : '',
            $contenido ? $contenido->detalle_grupos_etnicos : '',
            $responsables,
            $contenido ? $contenido->responsables_individuales : '',
            $contenido ? $contenido->temas_abordados : '',

            // ADJUNTOS
            $tieneAdjuntos,
            $cantidadAdjuntos,
            $tiposAdjuntos,
            $adjuntosAudio,
            $adjuntosVideo,
            $adjuntosDocumento,
            $duracionMinutos,
        ];
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    /** Convierte boolean DB a Si/No/- */
    private function formatBool($value): string
    {
        if ($value === null) return '-';
        return $value ? 'Si' : 'No';
    }

    /** Convierte campo prueba de daño (1/0/2/null) a Si/No/No sabe/- */
    private function formatDano($value): string
    {
        if ($value === null) return '-';
        if ($value == 1) return 'Si';
        if ($value == 0) return 'No';
        return 'No sabe'; // 2
    }

    /**
     * Calcula la clasificacion de prueba de daño para UN consentimiento.
     * Retorna par [string clasificacion, int prioridad].
     * Prioridad: 5=Inteligencia > 4=Clasificada+Reservada > 3=Reservada > 2=Clasificada > 1=Publica > 0=Pendiente
     */
    private function calcularClasificacion($privados, $publicos, $inteligencia, $nna): array
    {
        // Pregunta 3 SI → categoría superior
        if ($inteligencia == 1) {
            return ['Inteligencia y Contrainteligencia', 5];
        }

        // Todos "no sabe" o sin respuesta
        $esNS = fn($v) => ($v === null || $v == 2);
        if ($esNS($privados) && $esNS($publicos) && $esNS($inteligencia) && $esNS($nna)) {
            return ['Pendiente de Calificacion', 0];
        }

        $si1 = ($privados == 1);
        $si2o4 = ($publicos == 1 || $nna == 1);

        if ($si1 && $si2o4) return ['Publica-Clasificada y Publica-Reservada', 4];
        if ($si1)           return ['Publica-Clasificada', 2];
        if ($si2o4)         return ['Publica-Reservada', 3];

        return ['Publica-Publica', 1];
    }

    /**
     * Agrega la clasificación de prueba de daño de todas las personas de la entrevista.
     * Devuelve la clasificación de mayor prioridad.
     */
    private function clasificacionEntrevista($personas): string
    {
        $maxPrioridad = -1;
        $maxClasif = '';

        foreach ($personas as $pe) {
            $cons = $pe->rel_consentimiento;
            if (!$cons) continue;

            [$clasif, $prioridad] = $this->calcularClasificacion(
                $cons->prueba_dano_derechos_privados,
                $cons->prueba_dano_intereses_publicos,
                $cons->prueba_dano_inteligencia,
                $cons->prueba_dano_nna
            );

            if ($prioridad > $maxPrioridad) {
                $maxPrioridad = $prioridad;
                $maxClasif = $clasif;
            }
        }

        return $maxClasif;
    }

    public function styles(Worksheet $sheet)
    {
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EBC01A']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        return [
            1 => $headerStyle,
        ];
    }
}
