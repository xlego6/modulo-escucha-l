<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        return strpos($this->tipo_mime ?? '', 'video') !== false;
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
     * Generar imagen PNG con marca de agua
     * Incluye: usuario, fecha y hora de consulta
     */
    public static function generarMarcaAgua($texto = null)
    {
        $user = \Auth::user();
        if (!$texto) {
            $texto = $user->name ?? 'Usuario';
        }

        // Verificar si GD está disponible
        if (!function_exists('imagecreatetruecolor')) {
            // Retornar null si GD no está disponible, usaremos CSS
            return null;
        }

        $fechaHora = date('Y-m-d H:i:s');

        $font = 20;
        $angle = 45;

        // Calcular dimensiones
        $width = 400;
        $height = 200;

        // Crear imagen con fondo transparente
        $im = @imagecreatetruecolor($width, $height);
        if (!$im) {
            return null;
        }

        imagesavealpha($im, true);
        imagealphablending($im, false);

        // Fondo transparente
        $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $transparent);

        // Color del texto (gris semi-transparente)
        $textColor = imagecolorallocatealpha($im, 128, 128, 128, 80);

        imagealphablending($im, true);

        // Usar fuente básica (siempre disponible)
        imagestring($im, 5, 20, $height / 2 - 30, $texto, $textColor);
        imagestring($im, 4, 20, $height / 2, $fechaHora, $textColor);

        // Guardar PNG
        $nombreArchivo = 'marca_' . ($user->id ?? 0) . '_' . time() . '.png';
        $ruta = storage_path('app/public/marcas/' . $nombreArchivo);

        // Crear directorio si no existe
        $directorio = dirname($ruta);
        if (!file_exists($directorio)) {
            @mkdir($directorio, 0755, true);
        }

        @imagepng($im, $ruta);
        @imagedestroy($im);

        if (file_exists($ruta)) {
            return 'storage/marcas/' . $nombreArchivo;
        }

        return null;
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
