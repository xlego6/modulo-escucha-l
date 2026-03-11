<?php

namespace App\Services;

use App\Models\Entrevista;
use App\Models\Geo;
use App\Models\AsignacionTranscripcion;
use Carbon\Carbon;
use PhpOffice\PhpWord\TemplateProcessor;

class TranscripcionDocService
{
    /**
     * Reúne todos los metadatos para el encabezado FormTR
     */
    public function obtenerDatos(Entrevista $entrevista, string $tipo): array
    {
        // Lugar: municipio y departamento de toma del testimonio
        $municipio = $entrevista->rel_lugar_entrevista?->descripcion ?? '';
        $departamento = $entrevista->id_territorio
            ? Geo::find($entrevista->id_territorio)?->descripcion
            : null;
        $lugar = implode(', ', array_filter([$municipio, $departamento])) ?: 'Sin información';

        // Modalidad (presencial / virtual / mixta)
        $modalidades = $entrevista->rel_modalidades;
        if ($modalidades && $modalidades->isNotEmpty()) {
            $medio = $modalidades->pluck('descripcion')->join(' / ');
        } elseif ($entrevista->es_virtual !== null) {
            $medio = $entrevista->es_virtual ? 'Virtual' : 'Presencial';
        } else {
            $medio = 'Sin información';
        }

        // Fechas de realización
        $fi = $entrevista->fecha_toma_inicial
            ? Carbon::parse($entrevista->fecha_toma_inicial)->format('d/m/Y') : null;
        $ff = $entrevista->fecha_toma_final
            ? Carbon::parse($entrevista->fecha_toma_final)->format('d/m/Y') : null;
        if ($fi && $ff && $fi !== $ff) {
            $fechaRealizacion = "$fi al $ff";
        } else {
            $fechaRealizacion = $fi ?? $ff ?? 'Sin información';
        }

        // Nombre del entrevistador
        $entrevistador = $entrevista->rel_entrevistador?->rel_usuario?->name ?? 'Sin información';

        // Duración total de audios/videos
        $duracionSeg = $entrevista->rel_adjuntos
            ->filter(fn($a) => str_contains($a->tipo_mime ?? '', 'audio')
                             || str_contains($a->tipo_mime ?? '', 'video'))
            ->sum('duracion');
        $duracion = $duracionSeg ? $this->fmtDuracion((int)$duracionSeg) : 'Sin información';

        // Última asignación con fecha_inicio_edicion (fecha inicio transcripción)
        $asignacion = AsignacionTranscripcion::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->whereNotNull('fecha_inicio_edicion')
            ->with('rel_transcriptor.rel_usuario')
            ->orderBy('fecha_inicio_edicion', 'desc')
            ->first();

        $fechaInicioTranscripcion = $asignacion?->fecha_inicio_edicion
            ? $asignacion->fecha_inicio_edicion->format('d/m/Y')
            : 'Sin información';

        $fechaFinTranscripcion = $entrevista->transcripcion_completada_at
            ? Carbon::parse($entrevista->transcripcion_completada_at)->format('d/m/Y')
            : 'Sin información';

        $nombreTranscriptor = $asignacion
            ? ($asignacion->rel_transcriptor?->rel_usuario?->name ?? 'Sin información')
            : 'Automatizada';

        $dependencia   = $entrevista->rel_dependencia_origen?->descripcion ?? 'Sin información';
        $tipoTestimonio = $entrevista->rel_tipo_testimonio?->descripcion   ?? 'Sin información';

        // Texto de transcripción
        $tipoAdjunto = $tipo === 'final'
            ? Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_FINAL
            : Entrevista::TIPO_ADJUNTO_TRANSCRIPCION_AUTOMATIZADA;

        $adjunto = $entrevista->rel_adjuntos->firstWhere('id_tipo', $tipoAdjunto);
        $texto   = $adjunto?->texto_extraido ?? 'Sin transcripción disponible.';

        return compact(
            'lugar', 'medio', 'fechaRealizacion', 'entrevistador',
            'duracion', 'fechaInicioTranscripcion', 'fechaFinTranscripcion',
            'nombreTranscriptor', 'dependencia', 'tipoTestimonio', 'texto'
        );
    }

    /**
     * Genera el DOCX usando la plantilla FormTR.docx con TemplateProcessor.
     * Devuelve la ruta al archivo temporal.
     */
    public function generarDocx(Entrevista $entrevista, string $tipo): string
    {
        $datos = $this->obtenerDatos($entrevista, $tipo);

        $templatePath = storage_path('app/templates/FormTR.docx');
        $processor    = new TemplateProcessor($templatePath);

        $processor->setValue('CODIGO_ENTREVISTA',          htmlspecialchars($entrevista->entrevista_codigo));
        $processor->setValue('LUGAR_REALIZACION',          htmlspecialchars($datos['lugar']));
        $processor->setValue('MEDIO_TOMA',                 htmlspecialchars($datos['medio']));
        $processor->setValue('FECHA_REALIZACION',          htmlspecialchars($datos['fechaRealizacion']));
        $processor->setValue('NOMBRE_ENTREVISTADOR',       htmlspecialchars($datos['entrevistador']));
        $processor->setValue('DURACION_AUDIO',             htmlspecialchars($datos['duracion']));
        $processor->setValue('FECHA_INICIO_TRANSCRIPCION', htmlspecialchars($datos['fechaInicioTranscripcion']));
        $processor->setValue('FECHA_FIN_TRANSCRIPCION',    htmlspecialchars($datos['fechaFinTranscripcion']));
        $processor->setValue('NOMBRE_TRANSCRIPTOR',        htmlspecialchars($datos['nombreTranscriptor']));
        $processor->setValue('DEPENDENCIA',                htmlspecialchars($datos['dependencia']));
        $processor->setValue('TIPO_TESTIMONIO',            htmlspecialchars($datos['tipoTestimonio']));

        // Transcripción: los saltos de línea se convierten en <w:br/>
        $lineas = explode("\n", $datos['texto']);
        $textoDocx = implode(
            '</w:t><w:br/><w:t xml:space="preserve">',
            array_map('htmlspecialchars', $lineas)
        );
        $processor->setValue('TRANSCRIPCION', $textoDocx);

        $tmpPath = tempnam(sys_get_temp_dir(), 'FormTR_') . '.docx';
        $processor->saveAs($tmpPath);

        return $tmpPath;
    }

    /**
     * Genera el PDF convirtiendo el DOCX lleno con LibreOffice.
     * Devuelve la ruta al archivo PDF temporal.
     */
    public function generarPdf(Entrevista $entrevista, string $tipo): string
    {
        $docxPath = $this->generarDocx($entrevista, $tipo);
        $tmpDir   = dirname($docxPath);

        $cmd = sprintf(
            'HOME=/tmp soffice --headless --norestore --convert-to pdf --outdir %s %s 2>/dev/null',
            escapeshellarg($tmpDir),
            escapeshellarg($docxPath)
        );
        shell_exec($cmd);

        // LibreOffice nombra el PDF igual que el DOCX pero con extensión .pdf
        $pdfPath = substr($docxPath, 0, -5) . '.pdf';

        @unlink($docxPath);

        if (!file_exists($pdfPath)) {
            throw new \Exception('Error al convertir el documento a PDF con LibreOffice');
        }

        return $pdfPath;
    }

    private function fmtDuracion(int $seg): string
    {
        $h = floor($seg / 3600);
        $m = floor(($seg % 3600) / 60);
        $s = $seg % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
