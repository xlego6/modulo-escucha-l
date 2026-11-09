<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdjuntoController;
use App\Models\Adjunto;
use App\Services\AudioCompatibilidadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertirAudiosNoReproducibles extends Command
{
    protected $signature = 'adjuntos:audios-no-reproducibles
                            {--convertir : Convertir a .m4a los encontrados (por defecto solo los lista)}
                            {--borrar-original : Con --convertir, borrar el archivo original tras convertirlo}
                            {--id= : ID especifico de adjunto}';

    protected $description = 'Busca adjuntos de audio que el navegador no puede reproducir (p. ej. WAV ADPCM) y opcionalmente los convierte a .m4a';

    public function handle()
    {
        $query = Adjunto::where('tipo_mime', 'like', '%audio%')
            ->where('existe_archivo', 1)
            ->orderBy('id_adjunto');

        if ($this->option('id')) {
            $query->where('id_adjunto', $this->option('id'));
        }

        $convertir = (bool)$this->option('convertir');
        $encontrados = 0;
        $convertidos = 0;
        $errores = 0;

        foreach ($query->cursor() as $adjunto) {
            $ruta = Storage::disk('public')->path($adjunto->ubicacion);
            $info = AudioCompatibilidadService::analizar($ruta);
            if ($info === null || AudioCompatibilidadService::esReproducible($ruta) !== false) {
                continue;
            }

            $encontrados++;
            $this->line("[{$adjunto->id_adjunto}] {$adjunto->nombre_original} ({$info['formato']}/{$info['codec']}) — {$adjunto->ubicacion}");

            if (!$convertir) {
                continue;
            }

            $ubicacionM4a = preg_replace('/\.[^.\/]+$/', '', $adjunto->ubicacion) . '.m4a';
            $rutaM4a = Storage::disk('public')->path($ubicacionM4a);
            if (file_exists($rutaM4a)) {
                $this->error("    Ya existe {$ubicacionM4a}; se omite.");
                $errores++;
                continue;
            }

            try {
                AudioCompatibilidadService::convertirAM4a($ruta, $rutaM4a);

                $adjunto->ubicacion = $ubicacionM4a;
                $adjunto->nombre_original = pathinfo($adjunto->nombre_original, PATHINFO_FILENAME) . '.m4a';
                $adjunto->tipo_mime = 'audio/mp4';
                $adjunto->tamano = filesize($rutaM4a);
                $adjunto->md5 = md5_file($rutaM4a);
                $duracion = AdjuntoController::extraerDuracion($rutaM4a);
                if ($duracion) {
                    $adjunto->duracion = $duracion;
                }
                $adjunto->save();

                if ($this->option('borrar-original')) {
                    @unlink($ruta);
                    $this->info("    Convertido; original borrado.");
                } else {
                    $this->info("    Convertido; original conservado en {$ruta}");
                }
                $convertidos++;
            } catch (\Throwable $e) {
                $this->error('    ' . $e->getMessage());
                $errores++;
            }
        }

        $this->newLine();
        $this->info("No reproducibles: {$encontrados}" . ($convertir ? " — convertidos: {$convertidos}, errores: {$errores}" : ''));
        if (!$convertir && $encontrados > 0) {
            $this->comment('Ejecute con --convertir para convertirlos a .m4a.');
        }

        return $errores > 0 ? 1 : 0;
    }
}
