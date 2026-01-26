<?php

namespace App\Services;

use App\Models\Adjunto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TextExtractorService
{
    /**
     * Extraer texto de un adjunto
     */
    public function extraerTexto(Adjunto $adjunto): ?string
    {
        if (!$adjunto->existe_archivo || !$adjunto->ubicacion) {
            return null;
        }

        $path = Storage::disk('public')->path($adjunto->ubicacion);

        if (!file_exists($path)) {
            return null;
        }

        $mime = $adjunto->tipo_mime ?? '';
        $extension = strtolower(pathinfo($adjunto->nombre_original, PATHINFO_EXTENSION));

        try {
            // Archivos de texto plano
            if ($this->esTextoPlano($mime, $extension)) {
                return $this->extraerDeTextoPlano($path);
            }

            // Archivos PDF
            if ($this->esPdf($mime, $extension)) {
                return $this->extraerDePdf($path);
            }

            // Archivos Word (docx)
            if ($this->esWord($mime, $extension)) {
                return $this->extraerDeWord($path);
            }

            // Archivos RTF
            if ($extension === 'rtf') {
                return $this->extraerDeRtf($path);
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Error extrayendo texto de adjunto ' . $adjunto->id_adjunto . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si es texto plano
     */
    private function esTextoPlano($mime, $extension): bool
    {
        $extensiones = ['txt', 'csv', 'log', 'md', 'json', 'xml', 'srt', 'vtt'];
        $mimes = ['text/plain', 'text/csv', 'application/json', 'text/xml'];

        return in_array($extension, $extensiones) || in_array($mime, $mimes);
    }

    /**
     * Verificar si es PDF
     */
    private function esPdf($mime, $extension): bool
    {
        return $extension === 'pdf' || $mime === 'application/pdf';
    }

    /**
     * Verificar si es Word
     */
    private function esWord($mime, $extension): bool
    {
        $extensiones = ['docx', 'doc'];
        $mimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword'
        ];

        return in_array($extension, $extensiones) || in_array($mime, $mimes);
    }

    /**
     * Extraer texto de archivo plano
     */
    private function extraerDeTextoPlano($path): string
    {
        $contenido = file_get_contents($path);

        // Detectar y convertir encoding
        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        // Limpiar caracteres especiales y normalizar espacios
        $contenido = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $contenido);
        $contenido = preg_replace('/\s+/', ' ', $contenido);

        return trim($contenido);
    }

    /**
     * Extraer texto de PDF usando pdftotext (poppler-utils)
     */
    private function extraerDePdf($path): ?string
    {
        // Intentar con pdftotext (necesita poppler-utils instalado)
        $output = [];
        $returnCode = 0;

        // Verificar si pdftotext esta disponible
        exec('which pdftotext 2>/dev/null || where pdftotext 2>nul', $output, $returnCode);

        if ($returnCode === 0 || !empty($output)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
            $comando = sprintf('pdftotext -enc UTF-8 %s %s 2>/dev/null', escapeshellarg($path), escapeshellarg($tempFile));

            exec($comando, $output, $returnCode);

            if ($returnCode === 0 && file_exists($tempFile)) {
                $texto = file_get_contents($tempFile);
                @unlink($tempFile);

                // Limpiar y normalizar
                $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
                $texto = preg_replace('/\s+/', ' ', $texto);

                return trim($texto);
            }

            @unlink($tempFile);
        }

        // Metodo alternativo: intentar extraer texto directamente del PDF
        return $this->extraerDePdfBasico($path);
    }

    /**
     * Extraer texto de PDF de forma basica (sin dependencias externas)
     */
    private function extraerDePdfBasico($path): ?string
    {
        $contenido = file_get_contents($path);

        // Buscar streams de texto en el PDF
        $texto = '';

        // Buscar bloques de texto entre BT y ET
        if (preg_match_all('/BT\s*(.*?)\s*ET/s', $contenido, $matches)) {
            foreach ($matches[1] as $bloque) {
                // Extraer texto entre parentesis o corchetes
                if (preg_match_all('/\((.*?)\)|\[(.*?)\]/s', $bloque, $textos)) {
                    foreach ($textos[1] as $t) {
                        if ($t) $texto .= $t . ' ';
                    }
                    foreach ($textos[2] as $t) {
                        if ($t) $texto .= preg_replace('/\([^)]*\)/', '', $t) . ' ';
                    }
                }
            }
        }

        // Limpiar caracteres de escape PDF
        $texto = preg_replace('/\\\\[0-9]{3}/', '', $texto);
        $texto = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $texto);
        $texto = preg_replace('/[^\x20-\x7E\xA0-\xFF\s]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        $texto = trim($texto);

        return strlen($texto) > 50 ? $texto : null;
    }

    /**
     * Extraer texto de documento Word (DOCX)
     */
    private function extraerDeWord($path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'docx') {
            return $this->extraerDeDocx($path);
        }

        // Para DOC antiguo, intentar extraer texto basico
        return $this->extraerDeDocBasico($path);
    }

    /**
     * Extraer texto de DOCX (ZIP con XML)
     */
    private function extraerDeDocx($path): ?string
    {
        $zip = new \ZipArchive();

        if ($zip->open($path) !== true) {
            return null;
        }

        $texto = '';

        // El contenido principal esta en word/document.xml
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            return null;
        }

        // Parsear XML y extraer texto
        $dom = new \DOMDocument();
        @$dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        // Buscar todos los nodos de texto
        $nodos = $xpath->query('//w:t');
        foreach ($nodos as $nodo) {
            $texto .= $nodo->textContent . ' ';
        }

        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Extraer texto de DOC antiguo (metodo basico)
     */
    private function extraerDeDocBasico($path): ?string
    {
        $contenido = file_get_contents($path);

        // Eliminar caracteres binarios pero mantener texto
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', ' ', $contenido);

        // Intentar extraer secuencias de texto legible
        preg_match_all('/[\x20-\x7E\xA0-\xFF]{10,}/', $texto, $matches);

        if (!empty($matches[0])) {
            $texto = implode(' ', $matches[0]);
            $texto = preg_replace('/\s+/', ' ', $texto);
            return trim($texto);
        }

        return null;
    }

    /**
     * Extraer texto de RTF
     */
    private function extraerDeRtf($path): ?string
    {
        $contenido = file_get_contents($path);

        // Eliminar comandos RTF
        $texto = preg_replace('/\{\\\\[^{}]+\}/', '', $contenido);
        $texto = preg_replace('/\\\\[a-z]+[0-9]*\s?/', '', $texto);
        $texto = preg_replace('/[{}]/', '', $texto);

        // Limpiar
        $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    /**
     * Procesar todos los adjuntos pendientes
     */
    public function procesarPendientes($limite = 50): array
    {
        $adjuntos = Adjunto::where('existe_archivo', 1)
            ->whereNull('texto_extraido_at')
            ->where(function($q) {
                $q->where('tipo_mime', 'LIKE', '%pdf%')
                  ->orWhere('tipo_mime', 'LIKE', '%text%')
                  ->orWhere('tipo_mime', 'LIKE', '%word%')
                  ->orWhere('tipo_mime', 'LIKE', '%document%')
                  ->orWhere('nombre_original', 'LIKE', '%.txt')
                  ->orWhere('nombre_original', 'LIKE', '%.pdf')
                  ->orWhere('nombre_original', 'LIKE', '%.docx')
                  ->orWhere('nombre_original', 'LIKE', '%.doc')
                  ->orWhere('nombre_original', 'LIKE', '%.rtf');
            })
            ->limit($limite)
            ->get();

        $resultados = [
            'procesados' => 0,
            'exitosos' => 0,
            'fallidos' => 0,
        ];

        foreach ($adjuntos as $adjunto) {
            $resultados['procesados']++;

            $texto = $this->extraerTexto($adjunto);

            $adjunto->texto_extraido = $texto;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();

            if ($texto && strlen($texto) > 0) {
                $resultados['exitosos']++;
            } else {
                $resultados['fallidos']++;
            }
        }

        return $resultados;
    }
}
