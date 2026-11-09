<?php

namespace App\Services;

/**
 * Detecta audios que el navegador no puede reproducir (p. ej. WAV con códec
 * Microsoft ADPCM, WMA, AMR) y los convierte a .m4a (AAC) con ffmpeg.
 */
class AudioCompatibilidadService
{
    /**
     * Combinaciones contenedor → códecs que reproducen Chrome y Firefox con <audio>.
     * La clave se compara con el primer elemento de format_name de ffprobe.
     */
    private const COMPATIBLES = [
        'wav'  => ['pcm_u8', 'pcm_s16le', 'pcm_s24le', 'pcm_s32le', 'pcm_f32le'],
        'mp3'  => ['mp3'],
        'mov'  => ['aac', 'mp3', 'flac', 'opus'],   // mov,mp4,m4a,3gp,...
        'ogg'  => ['vorbis', 'opus', 'flac'],
        'matroska' => ['vorbis', 'opus'],           // matroska,webm
        'flac' => ['flac'],
        'aac'  => ['aac'],
    ];

    /**
     * Indica si un archivo de solo audio se puede reproducir en el navegador.
     * Devuelve null cuando no aplica o no se puede determinar (sin ffprobe,
     * archivo ilegible o con pista de video): en esos casos no se toca el archivo.
     */
    public static function esReproducible(string $rutaAbsoluta): ?bool
    {
        $info = self::analizar($rutaAbsoluta);
        if ($info === null) {
            return null;
        }

        $codecs = self::COMPATIBLES[$info['formato']] ?? [];
        return in_array($info['codec'], $codecs, true);
    }

    /**
     * Devuelve ['formato' => ..., 'codec' => ...] del primer stream de audio,
     * o null si no hay audio, hay video o ffprobe falla.
     */
    public static function analizar(string $rutaAbsoluta): ?array
    {
        if (!is_file($rutaAbsoluta)) {
            return null;
        }

        $ffprobe = trim(shell_exec('which ffprobe 2>/dev/null') ?? '') ?: 'ffprobe';
        $cmd = sprintf(
            '%s -v quiet -print_format json -show_format -show_streams %s 2>/dev/null',
            escapeshellcmd($ffprobe),
            escapeshellarg($rutaAbsoluta)
        );

        $data = json_decode(shell_exec($cmd) ?? '', true);
        if (!is_array($data) || empty($data['streams'])) {
            return null;
        }

        $audio = null;
        foreach ($data['streams'] as $stream) {
            $tipo = $stream['codec_type'] ?? '';
            // Una carátula (attached_pic) en un mp3/m4a no cuenta como video
            if ($tipo === 'video' && empty($stream['disposition']['attached_pic'])) {
                return null;
            }
            if ($tipo === 'audio' && $audio === null) {
                $audio = $stream;
            }
        }

        if ($audio === null) {
            return null;
        }

        $formato = explode(',', $data['format']['format_name'] ?? '')[0];

        return [
            'formato' => $formato,
            'codec'   => $audio['codec_name'] ?? '',
        ];
    }

    /**
     * Convierte a .m4a (AAC mono 64 kbps, calidad suficiente para voz).
     * Lanza RuntimeException si ffmpeg falla.
     */
    public static function convertirAM4a(string $origen, string $destino): void
    {
        $ffmpeg = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '') ?: '/usr/bin/ffmpeg';
        $cmd = sprintf(
            '%s -hide_banner -loglevel error -i %s -vn -c:a aac -b:a 64k -ar 22050 -ac 1 -movflags +faststart -y %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($origen),
            escapeshellarg($destino)
        );

        exec($cmd, $output, $rc);

        if ($rc !== 0 || !is_file($destino) || filesize($destino) === 0) {
            @unlink($destino);
            throw new \RuntimeException('Error de ffmpeg al convertir a m4a: ' . implode(' ', array_slice($output, -3)));
        }
    }
}
