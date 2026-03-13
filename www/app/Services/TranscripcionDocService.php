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

        // Transcripción: markdown inline → runs OOXML con formato
        $processor->setValue('TRANSCRIPCION', $this->markdownToDocxXml($datos['texto']));

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

    /**
     * Convierte texto con markdown en XML de párrafos OOXML para TemplateProcessor.
     *
     * Estrategia: cierra el párrafo-plantilla vacío al inicio, emite cada línea
     * como un <w:p> completo y reabre un párrafo vacío al final para que la
     * plantilla pueda cerrarlo con </w:t></w:r></w:p> sin romper el XML.
     *
     * Soporta:
     *   # / ## / ###  → encabezado en negrita con tamaño de fuente ajustado
     *   - item         → párrafo con sangría y viñeta •
     *   inline: **bold**, *italic*, ***bold+italic***, equivalentes con _
     */
    private function markdownToDocxXml(string $texto): string
    {
        // Normalizar saltos de línea (Windows \r\n → \n)
        $lineas = explode("\n", str_replace(["\r\n", "\r"], "\n", $texto));

        // Cerrar el párrafo-plantilla (que contenía ${TRANSCRIPCION} vacío)
        $resultado = '</w:t></w:r></w:p>';

        foreach ($lineas as $linea) {
            // Encabezados: #, ##, ###
            if (preg_match('/^(#{1,3}) (.*)$/', $linea, $m)) {
                $nivel  = strlen($m[1]);
                $sz     = ['1' => '36', '2' => '30', '3' => '26'][(string)$nivel];
                $titulo = htmlspecialchars($m[2], ENT_XML1);
                $resultado .= '<w:p>'
                    . "<w:r><w:rPr><w:b/><w:sz w:val=\"{$sz}\"/><w:szCs w:val=\"{$sz}\"/></w:rPr>"
                    . "<w:t xml:space=\"preserve\">{$titulo}</w:t></w:r>"
                    . '</w:p>';
                continue;
            }

            // Listas: - item  o  * item
            if (preg_match('/^[-*] (.*)$/', $linea, $m)) {
                $inner = $this->lineaMarkdownAXml($m[1]);
                $resultado .= '<w:p><w:pPr><w:ind w:left="360"/></w:pPr>'
                    . '<w:r><w:t xml:space="preserve">&#x2022; </w:t></w:r>'
                    . "<w:r><w:t xml:space=\"preserve\">{$inner}</w:t></w:r>"
                    . '</w:p>';
                continue;
            }

            // Párrafo normal (con markdown inline)
            $inner = $this->lineaMarkdownAXml($linea);
            $resultado .= "<w:p><w:r><w:t xml:space=\"preserve\">{$inner}</w:t></w:r></w:p>";
        }

        // Párrafo vacío final: la plantilla lo cierra con </w:t></w:r></w:p>
        $resultado .= '<w:p><w:r><w:t>';

        return $resultado;
    }

    /**
     * Parsea una línea con markdown inline y devuelve fragmento OOXML.
     * Soporta: ***bold+italic***, **bold**, *italic*, ___bold+italic___, __bold__, _italic_
     */
    private function lineaMarkdownAXml(string $linea): string
    {
        $pattern = '/\*\*\*(.+?)\*\*\*|\*\*(.+?)\*\*|\*(.+?)\*|___(.+?)___|__(.+?)__|_(.+?)_/s';

        $resultado = '';
        $ultimo    = 0;

        preg_match_all($pattern, $linea, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            $matchFull  = $match[0][0];
            $matchStart = $match[0][1];

            // Texto plano antes del token
            if ($matchStart > $ultimo) {
                $resultado .= htmlspecialchars(
                    substr($linea, $ultimo, $matchStart - $ultimo), ENT_XML1
                );
            }

            // Detectar formato e inner text
            $isBold = $isItalic = false;
            $inner  = '';

            if (isset($match[1]) && $match[1][1] !== -1) {       // ***...***
                $isBold = $isItalic = true;
                $inner  = $match[1][0];
            } elseif (isset($match[2]) && $match[2][1] !== -1) {  // **...**
                $isBold = true;
                $inner  = $match[2][0];
            } elseif (isset($match[3]) && $match[3][1] !== -1) {  // *...*
                $isItalic = true;
                $inner    = $match[3][0];
            } elseif (isset($match[4]) && $match[4][1] !== -1) {  // ___...___
                $isBold = $isItalic = true;
                $inner  = $match[4][0];
            } elseif (isset($match[5]) && $match[5][1] !== -1) {  // __...__
                $isBold = true;
                $inner  = $match[5][0];
            } elseif (isset($match[6]) && $match[6][1] !== -1) {  // _..._
                $isItalic = true;
                $inner    = $match[6][0];
            }

            $rPr          = ($isBold ? '<w:b/>' : '') . ($isItalic ? '<w:i/>' : '');
            $innerEscaped = htmlspecialchars($inner, ENT_XML1);

            // Cerrar run actual → run con formato → reabrir run plano
            $resultado .= '</w:t></w:r>'
                . "<w:r><w:rPr>{$rPr}</w:rPr><w:t xml:space=\"preserve\">{$innerEscaped}</w:t></w:r>"
                . '<w:r><w:t xml:space="preserve">';

            $ultimo = $matchStart + strlen($matchFull);
        }

        // Texto plano restante al final de la línea
        if ($ultimo < strlen($linea)) {
            $resultado .= htmlspecialchars(substr($linea, $ultimo), ENT_XML1);
        }

        return $resultado;
    }

    private function fmtDuracion(int $seg): string
    {
        $h = floor($seg / 3600);
        $m = floor(($seg % 3600) / 60);
        $s = $seg % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}
