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
                'rel_territorio',
                'rel_dependencia_origen',
                'rel_equipo_estrategia',
                'rel_tipo_testimonio',
                'rel_idioma',
                'rel_idiomas',
                'rel_areas_compatibles',
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
                'rel_contenido.rel_sexos',
                'rel_contenido.rel_identidades_genero',
                'rel_contenido.rel_orientaciones_sexuales',
                'rel_contenido.rel_etnias',
                'rel_contenido.rel_rangos_etarios',
                'rel_contenido.rel_discapacidades',
                'rel_contenido.rel_hechos_victimizantes',
                'rel_contenido.rel_responsables',
                'rel_contenido.rel_practicas_resistencia',
            ]);

        // Códigos explícitos: si se dan, ignoran todos los demás filtros
        $codigos = [];
        if (!empty($this->filtros['codigos'])) {
            $codigos = array_values(array_filter(
                array_map('trim', preg_split('/[\s,;]+/', $this->filtros['codigos']))
            ));
        }
        if (!empty($codigos)) {
            $query->whereIn('entrevista_codigo', $codigos);
        } else {
            if (!empty($this->filtros['fecha_desde'])) {
                $query->where('fecha_toma_inicial', '>=', $this->filtros['fecha_desde']);
            }
            if (!empty($this->filtros['fecha_hasta'])) {
                $query->where('fecha_toma_final', '<=', $this->filtros['fecha_hasta']);
            }
            if (!empty($this->filtros['carga_desde'])) {
                $query->whereDate('created_at', '>=', $this->filtros['carga_desde']);
            }
            if (!empty($this->filtros['carga_hasta'])) {
                $query->whereDate('created_at', '<=', $this->filtros['carga_hasta']);
            }
            if (!empty($this->filtros['id_territorio'])) {
                $query->whereIn('id_territorio', (array) $this->filtros['id_territorio']);
            }
            if (!empty($this->filtros['id_entrevistador'])) {
                $query->whereIn('id_entrevistador', (array) $this->filtros['id_entrevistador']);
            }
            if (!empty($this->filtros['id_dependencia_origen'])) {
                $query->whereIn('id_dependencia_origen', (array) $this->filtros['id_dependencia_origen']);
            }
            if (!empty($this->filtros['id_tipo_testimonio'])) {
                $query->whereIn('id_tipo_testimonio', (array) $this->filtros['id_tipo_testimonio']);
            }
            if (isset($this->filtros['tiene_adjuntos']) && $this->filtros['tiene_adjuntos'] !== '') {
                if ($this->filtros['tiene_adjuntos'] == '1') {
                    $query->whereHas('rel_adjuntos');
                } elseif ($this->filtros['tiene_adjuntos'] == '0') {
                    $query->whereDoesntHave('rel_adjuntos');
                }
            }
            if (!empty($this->filtros['id_tipo_adjunto'])) {
                $query->whereHas('rel_adjuntos', function ($q) {
                    $q->whereIn('id_tipo', (array) $this->filtros['id_tipo_adjunto']);
                });
            }
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

            // CONSENTIMIENTO
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
            'Sexos Mencionados',
            'Identidades de Genero',
            'Orientaciones Sexuales',
            'Etnias Mencionadas',
            'Detalle Grupos Etnicos',
            'Rangos Etarios',
            'Discapacidades Mencionadas',
            'Hechos Victimizantes',
            'Otros Hechos Victimizantes',
            'Practicas de Resistencia',
            'Detalle Resistencias',
            'Responsables Colectivos',
            'Responsables Individuales',
            'Temas Abordados',

            // ADJUNTOS
            'Tiene Adjuntos',
            'Cantidad Adjuntos',
            'Tipos de Adjuntos',
            'Soporte',
            'Peso Total',
            'Adjuntos Audio',
            'Adjuntos Video',
            'Adjuntos Documento',
            'Duracion Total',
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

        // Areas compatibles (many-to-many)
        $areasCompatibles = $entrevista->rel_areas_compatibles->pluck('descripcion')->implode(', ');

        // Departamento de toma: id_territorio almacenado directamente en la entrevista
        $departamentoToma = $entrevista->rel_territorio ? $entrevista->rel_territorio->descripcion : '';

        // Testimoniantes y consentimientos
        $testimoniantes = [];
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

            $cons = $pe->rel_consentimiento;
            if ($cons) {
                $obs = $cons->observaciones ?? '';
                $esOtro = str_contains($obs, '[CONSENTIMIENTO_OTRO]');

                if ($esOtro) {
                    $consTipoDoc[] = "{$num}: Otro";
                } elseif ($cons->tiene_documento_autorizacion) {
                    $consTipoDoc[] = "{$num}: Si";
                } else {
                    $consTipoDoc[] = "{$num}: No";
                }

                $tieneDoc = $cons->tiene_documento_autorizacion;
                $consMenorEdad[] = $num . ': ' . $this->formatBool($tieneDoc ? $cons->es_menor_edad : null);
                $consAutoriza[]  = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_ser_entrevistado : null);
                $consGrabacion[] = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_grabacion : null);
                $consProc[]      = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_procesamiento_misional : null);
                $consUso[]       = $num . ': ' . $this->formatBool($tieneDoc ? $cons->permite_uso_conservacion_consulta : null);
                $consRiesgo[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->considera_riesgo_seguridad : null);
                $consDatosP[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_datos_personales_sin_anonimizar : null);
                $consDatosS[]    = $num . ': ' . $this->formatBool($tieneDoc ? $cons->autoriza_datos_sensibles_sin_anonimizar : null);

                $obsLimpia = $esOtro ? trim(str_replace('[CONSENTIMIENTO_OTRO]', '', $obs)) : $obs;
                if ($obsLimpia) $consObs[] = "{$num}: {$obsLimpia}";

                $danoPriva[]   = $num . ': ' . $this->formatDano($cons->prueba_dano_derechos_privados);
                $danoPubli[]   = $num . ': ' . $this->formatDano($cons->prueba_dano_intereses_publicos);
                $danoIntelig[] = $num . ': ' . $this->formatDano($cons->prueba_dano_inteligencia);
                $danoNna[]     = $num . ': ' . $this->formatDano($cons->prueba_dano_nna);
            }
        }

        $clasificacion = $this->clasificacionEntrevista($entrevista->rel_personas_entrevistadas);

        // Contenido del testimonio
        $contenido      = $entrevista->rel_contenido;
        $poblaciones    = $contenido ? $contenido->rel_poblaciones->pluck('descripcion')->implode(', ') : '';
        $ocupaciones    = $contenido ? $contenido->rel_ocupaciones->pluck('descripcion')->implode(', ') : '';
        $sexos          = $contenido ? $contenido->rel_sexos->pluck('descripcion')->implode(', ') : '';
        $identidades    = $contenido ? $contenido->rel_identidades_genero->pluck('descripcion')->implode(', ') : '';
        $orientaciones  = $contenido ? $contenido->rel_orientaciones_sexuales->pluck('descripcion')->implode(', ') : '';
        $etnias         = $contenido ? $contenido->rel_etnias->pluck('descripcion')->implode(', ') : '';
        $rangos         = $contenido ? $contenido->rel_rangos_etarios->pluck('descripcion')->implode(', ') : '';
        $discapacidades = $contenido ? $contenido->rel_discapacidades->pluck('descripcion')->implode(', ') : '';
        $hechos         = $contenido ? $contenido->rel_hechos_victimizantes->pluck('descripcion')->implode(', ') : '';
        $responsables   = $contenido ? $contenido->rel_responsables->pluck('descripcion')->implode(', ') : '';
        $practicas      = $contenido ? $contenido->rel_practicas_resistencia->pluck('descripcion')->implode(', ') : '';

        // Temas abordados: JSON del tesauro → texto plano separado por |
        $temas = '';
        if ($contenido && $contenido->temas_abordados) {
            $decoded = json_decode($contenido->temas_abordados, true);
            if (is_array($decoded)) {
                $temas = implode(' | ', array_column($decoded, 'text'));
            } else {
                $temas = trim(preg_replace('/[\r\n]+/', ' | ', $contenido->temas_abordados), ' | ');
            }
        }

        // Adjuntos
        $adjuntos         = $entrevista->rel_adjuntos;
        $tieneAdjuntos    = $adjuntos->count() > 0 ? 'Si' : 'No';
        $cantidadAdjuntos = $adjuntos->count();
        $tiposAdjuntos    = $adjuntos->map(fn($adj) => $adj->rel_tipo ? $adj->rel_tipo->descripcion : 'Sin tipo')->unique()->implode(', ');

        // Soporte: extensiones únicas
        $soportes = $adjuntos->map(function ($adj) {
            $nombre = $adj->nombre_original ?? '';
            return $nombre ? strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) : null;
        })->filter()->unique()->sort()->implode(', ');

        // Peso total formateado
        $pesoTotal = $adjuntos->sum('tamano');
        $pesoFormato = '';
        if ($pesoTotal >= 1073741824) {
            $pesoFormato = number_format($pesoTotal / 1073741824, 2) . ' GB';
        } elseif ($pesoTotal >= 1048576) {
            $pesoFormato = number_format($pesoTotal / 1048576, 2) . ' MB';
        } elseif ($pesoTotal > 0) {
            $pesoFormato = number_format($pesoTotal / 1024, 2) . ' KB';
        }

        $adjuntosAudio    = $adjuntos->filter(fn($adj) => $adj->es_audio)->count();
        $adjuntosVideo    = $adjuntos->filter(fn($adj) => $adj->es_video)->count();
        $adjuntosDocumento = $adjuntos->filter(fn($adj) => $adj->es_documento)->count();

        // Duración total en hh:mm:ss
        $duracionTotal = $adjuntos->sum('duracion');
        $duracionFormato = '';
        if ($duracionTotal > 0) {
            $h = floor($duracionTotal / 3600);
            $m = floor(($duracionTotal % 3600) / 60);
            $s = $duracionTotal % 60;
            $duracionFormato = sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

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
            $departamentoToma,
            $entrevista->rel_lugar_entrevista ? $entrevista->rel_lugar_entrevista->descripcion : '',
            $modalidades,
            $entrevista->rel_idiomas->count() > 0
                ? $entrevista->rel_idiomas->pluck('descripcion')->implode(', ')
                : ($entrevista->rel_idioma ? $entrevista->rel_idioma->descripcion : ''),
            $entrevista->detalle_idiomas ?? '',
            $entrevista->fecha_toma_inicial,
            $entrevista->fecha_toma_final,
            $necesidades,
            $areasCompatibles,
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
            $sexos,
            $identidades,
            $orientaciones,
            $etnias,
            $contenido ? $contenido->detalle_grupos_etnicos : '',
            $rangos,
            $discapacidades,
            $hechos,
            $contenido ? $contenido->otros_hechos_victimizantes : '',
            $practicas,
            $contenido ? $contenido->detalle_resistencias : '',
            $responsables,
            $contenido ? $contenido->responsables_individuales : '',
            $temas,

            // ADJUNTOS
            $tieneAdjuntos,
            $cantidadAdjuntos,
            $tiposAdjuntos,
            $soportes,
            $pesoFormato,
            $adjuntosAudio,
            $adjuntosVideo,
            $adjuntosDocumento,
            $duracionFormato,
        ];
    }

    // -------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------

    private function formatBool($value): string
    {
        if ($value === null) return '-';
        return $value ? 'Si' : 'No';
    }

    private function formatDano($value): string
    {
        if ($value === null) return '-';
        if ($value == 1) return 'Si';
        if ($value == 0) return 'No';
        return 'No sabe';
    }

    private function calcularClasificacion($privados, $publicos, $inteligencia, $nna): array
    {
        if ($inteligencia == 1) {
            return ['Inteligencia y Contrainteligencia', 5];
        }
        $esNS = fn($v) => ($v === null || $v == 2);
        if ($esNS($privados) && $esNS($publicos) && $esNS($inteligencia) && $esNS($nna)) {
            return ['Pendiente de Calificacion', 0];
        }
        $si1   = ($privados == 1);
        $si2o4 = ($publicos == 1 || $nna == 1);
        if ($si1 && $si2o4) return ['Publica-Clasificada y Publica-Reservada', 4];
        if ($si1)           return ['Publica-Clasificada', 2];
        if ($si2o4)         return ['Publica-Reservada', 3];
        return ['Publica-Publica', 1];
    }

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
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'EBC01A'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
