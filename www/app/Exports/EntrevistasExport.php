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
            'Idioma',
            'Fecha Toma Inicial',
            'Fecha Toma Final',
            'Necesidades Reparacion',
            'Areas Compatibles',
            'Tiene Anexos',
            'Descripcion Anexos',
            'Observaciones Toma',
            'Entrevistador',

            // PASO 2: TESTIMONIANTES
            'Testimoniante(s)',
            'Tipo(s) Testimoniante',
            'Consentimiento Completo',

            // PASO 3: CONTENIDO
            'Fecha Hechos Inicial',
            'Fecha Hechos Final',
            'Poblaciones Mencionadas',
            'Ocupaciones Mencionadas',
            'Hechos Victimizantes',
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

        // Testimoniantes
        $testimoniantes = [];
        $tiposTestimoniante = [];
        $consentimientoCompleto = true;

        foreach ($entrevista->rel_personas_entrevistadas as $pe) {
            if ($pe->rel_persona) {
                $testimoniantes[] = trim($pe->rel_persona->primer_nombre . ' ' . $pe->rel_persona->segundo_nombre . ' ' . $pe->rel_persona->primer_apellido . ' ' . $pe->rel_persona->segundo_apellido);
            }
            $tiposTestimoniante[] = $pe->fmt_tipo;

            // Verificar consentimiento
            if ($pe->rel_consentimiento) {
                $cons = $pe->rel_consentimiento;
                if (!$cons->tiene_documento_autorizacion || !$cons->autoriza_ser_entrevistado ||
                    !$cons->permite_grabacion || !$cons->permite_procesamiento_misional) {
                    $consentimientoCompleto = false;
                }
            } else {
                $consentimientoCompleto = false;
            }
        }

        // Contenido del testimonio
        $contenido = $entrevista->rel_contenido;
        $poblaciones = $contenido ? $contenido->rel_poblaciones->pluck('descripcion')->implode(', ') : '';
        $ocupaciones = $contenido ? $contenido->rel_ocupaciones->pluck('descripcion')->implode(', ') : '';
        $hechos = $contenido ? $contenido->rel_hechos_victimizantes->pluck('descripcion')->implode(', ') : '';
        $responsables = $contenido ? $contenido->rel_responsables->pluck('descripcion')->implode(', ') : '';

        // Adjuntos
        $adjuntos = $entrevista->rel_adjuntos;
        $tieneAdjuntos = $adjuntos->count() > 0 ? 'Si' : 'No';
        $cantidadAdjuntos = $adjuntos->count();
        $tiposAdjuntos = $adjuntos->map(function ($adj) {
            return $adj->rel_tipo ? $adj->rel_tipo->descripcion : 'Sin tipo';
        })->unique()->implode(', ');

        // Contar por tipo
        $adjuntosAudio = $adjuntos->filter(function ($adj) {
            return $adj->es_audio;
        })->count();

        $adjuntosVideo = $adjuntos->filter(function ($adj) {
            return $adj->es_video;
        })->count();

        $adjuntosDocumento = $adjuntos->filter(function ($adj) {
            return $adj->es_documento;
        })->count();

        // Duracion total en minutos
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
            $entrevista->rel_idioma ? $entrevista->rel_idioma->descripcion : '',
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

            // PASO 2: TESTIMONIANTES
            implode(' | ', array_filter($testimoniantes)),
            implode(' | ', array_unique(array_filter($tiposTestimoniante))),
            $consentimientoCompleto ? 'Si' : 'No',

            // PASO 3: CONTENIDO
            $contenido ? $contenido->fecha_hechos_inicial : '',
            $contenido ? $contenido->fecha_hechos_final : '',
            $poblaciones,
            $ocupaciones,
            $hechos,
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

    public function styles(Worksheet $sheet)
    {
        // Estilo para encabezado
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EBC01A'] // Color principal del proyecto
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
