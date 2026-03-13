<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use setasign\Fpdi\Fpdi;

class Adjunto extends Model
{
    protected $table = 'esclarecimiento.adjunto';
    protected $primaryKey = 'id_adjunto';

    protected $fillable = [
        'id_e_ind_fvt',
        'ubicacion',
        'nombre_original',
        'tipo_mime',
        'id_tipo',
        'id_calificacion',
        'tamano',
        'tamano_bruto',
        'md5',
        'duracion',
        'existe_archivo',
        'texto_extraido',
        'texto_extraido_at',
    ];

    public function rel_entrevista() {
        return $this->belongsTo(Entrevista::class, 'id_e_ind_fvt', 'id_e_ind_fvt');
    }

    public function rel_tipo() {
        return $this->belongsTo(CatItem::class, 'id_tipo', 'id_item');
    }

    public function getFmtTamanoAttribute() {
        $bytes = $this->tamano;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getFmtDuracionAttribute() {
        if (empty($this->duracion)) {
            return 'N/A';
        }
        $horas = floor($this->duracion / 3600);
        $minutos = floor(($this->duracion % 3600) / 60);
        $segundos = $this->duracion % 60;

        if ($horas > 0) {
            return sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
        }
        return sprintf('%02d:%02d', $minutos, $segundos);
    }

    public function getEsAudioAttribute() {
        return strpos($this->tipo_mime ?? '', 'audio') !== false;
    }

    public function getEsVideoAttribute() {
        $mime = $this->tipo_mime ?? '';
        return strpos($mime, 'video') !== false || strpos($mime, 'flv') !== false;
    }

    public function getEsDocumentoAttribute() {
        $tipos_doc = ['pdf', 'word', 'document', 'text'];
        $mime = $this->tipo_mime ?? '';
        foreach ($tipos_doc as $tipo) {
            if (strpos($mime, $tipo) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generar imagen PNG con marca de agua para el visor (patrón diagonal repetible)
     * Incluye: nombre de usuario, fecha y hora de consulta
     */
    public static function generarMarcaAgua($texto = null): ?string
    {
        $user = \Auth::user();
        $texto = $texto ?? ($user->name ?? 'Usuario');

        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $fechaHora = date('d/m/Y H:i');
        $im = @imagecreatetruecolor(400, 400);
        if (!$im) {
            return null;
        }

        imagesavealpha($im, true);
        imagealphablending($im, false);
        $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $transparent);

        $color = imagecolorallocatealpha($im, 130, 130, 130, 80);
        imagealphablending($im, true);

        $ttf = public_path('/fonts/source-sans-pro-v11-latin-900.ttf');

        if (function_exists('imagettftext') && file_exists($ttf)) {
            $font = 18;
            $angle = 45;
            // Línea de nombre (diagonal)
            @imagettftext($im, $font, $angle, 20, 220, $color, $ttf, $texto);
            @imagettftext($im, $font, $angle, 220, 420, $color, $ttf, $texto);
            // Línea de fecha/hora (diagonal, más pequeña)
            $colorFecha = imagecolorallocatealpha($im, 130, 130, 130, 90);
            @imagettftext($im, 13, $angle, 120, 300, $colorFecha, $ttf, $fechaHora);
            @imagettftext($im, 13, $angle, 320, 500, $colorFecha, $ttf, $fechaHora);
        } else {
            // Fallback sin TTF
            imagestring($im, 5, 20, 150, $texto, $color);
            imagestring($im, 4, 20, 170, $fechaHora, $color);
            imagestring($im, 5, 200, 300, $texto, $color);
            imagestring($im, 4, 200, 320, $fechaHora, $color);
        }

        $nombreArchivo = 'marca_' . ($user->id ?? 0) . '_' . time() . '.png';
        $ruta = storage_path('app/public/marcas/' . $nombreArchivo);
        $directorio = dirname($ruta);
        if (!file_exists($directorio)) {
            @mkdir($directorio, 0755, true);
        }

        @imagepng($im, $ruta);
        @imagedestroy($im);

        return file_exists($ruta) ? 'storage/marcas/' . $nombreArchivo : null;
    }

    /**
     * Aplicar marca de agua a un PDF para descarga.
     * Usa FPDI para insertar la imagen de marca sobre cada página.
     * Retorna la ruta del archivo temporal con el PDF marcado.
     */
    public static function aplicarMarcaAguaDescarga(string $ubicacion, string $textoUsuario): ?string
    {
        $rutaOriginal = \Storage::disk('public')->path($ubicacion);

        if (!file_exists($rutaOriginal)) {
            return null;
        }

        // Generar PNG de marca de agua para descarga
        $pngPath = self::generarPngMarcaDescarga($textoUsuario);
        if (!$pngPath || !file_exists($pngPath)) {
            return null;
        }

        try {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($rutaOriginal);

            for ($i = 1; $i <= $pageCount; $i++) {
                $template = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($template);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($template, 0, 0, $size['width'], $size['height']);
                // Superponer marca de agua encima del contenido
                $pdf->Image($pngPath, 0, 0, $size['width'], $size['height'], 'PNG');
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_wm_') . '.pdf';
            $pdf->Output('F', $tempFile);
        } catch (\Exception $e) {
            \Log::warning('Error aplicando marca de agua al PDF: ' . $e->getMessage());
            return null;
        } finally {
            @unlink($pngPath);
        }

        return file_exists($tempFile) ? $tempFile : null;
    }

    /**
     * Generar PNG de marca de agua a tamaño A4 para aplicar en descarga de PDF.
     */
    private static function generarPngMarcaDescarga(string $texto): ?string
    {
        if (!function_exists('imagecreatetruecolor')) {
            return null;
        }

        $fechaHora = date('d/m/Y H:i:s');
        // Dimensiones A4 a 96dpi (~794x1123px)
        $width = 794;
        $height = 1123;

        $im = @imagecreatetruecolor($width, $height);
        if (!$im) {
            return null;
        }

        imagesavealpha($im, true);
        imagealphablending($im, false);
        $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $transparent);

        $color = imagecolorallocatealpha($im, 150, 150, 150, 80);
        imagealphablending($im, true);

        $ttf = public_path('/fonts/source-sans-pro-v11-latin-900.ttf');

        if (function_exists('imagettftext') && file_exists($ttf)) {
            $font = 28;
            $angle = 45;
            // Cubrir la página con el patrón diagonal
            for ($y = 0; $y < $height + 300; $y += 200) {
                for ($x = -200; $x < $width + 200; $x += 350) {
                    @imagettftext($im, $font, $angle, $x, $y, $color, $ttf, $texto);
                    @imagettftext($im, 18, $angle, $x + 60, $y + 80, $color, $ttf, $fechaHora);
                }
            }
        } else {
            for ($y = 80; $y < $height; $y += 160) {
                @imagestring($im, 5, 40, $y, $texto, $color);
                @imagestring($im, 4, 40, $y + 20, $fechaHora, $color);
            }
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'wmpng_') . '.png';
        @imagepng($im, $tempFile);
        @imagedestroy($im);

        return file_exists($tempFile) ? $tempFile : null;
    }

    /**
     * Limpiar marcas de agua antiguas (más de 1 hora)
     */
    public static function limpiarMarcasAntiguas()
    {
        $directorio = storage_path('app/public/marcas');
        if (!file_exists($directorio)) {
            return;
        }

        $archivos = glob($directorio . '/marca_*.png');
        $limite = time() - 3600; // 1 hora

        foreach ($archivos as $archivo) {
            if (filemtime($archivo) < $limite) {
                @unlink($archivo);
            }
        }
    }
}
