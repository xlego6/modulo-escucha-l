<?php

namespace App\Console\Commands;

use App\Http\Controllers\AdjuntoController;
use App\Models\Adjunto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ActualizarDuracionAdjuntos extends Command
{
    protected $signature = 'adjuntos:actualizar-duracion
                            {--limite=200 : Numero maximo de adjuntos a procesar}
                            {--id= : ID especifico de adjunto a procesar}
                            {--todos : Reprocesar incluso los que ya tienen duracion}';

    protected $description = 'Extrae y actualiza la duración (segundos) de adjuntos de audio/video usando ffprobe';

    public function handle()
    {
        $query = Adjunto::where(function($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->where('existe_archivo', 1);

        if ($this->option('id')) {
            $query->where('id_adjunto', $this->option('id'));
        } elseif (!$this->option('todos')) {
            $query->where(function($q) {
                $q->whereNull('duracion')->orWhere('duracion', 0);
            });
        }

        $total = $query->count();
        $limite = (int)$this->option('limite');
        $adjuntos = $query->limit($limite)->get();

        $this->info("Adjuntos sin duración encontrados: {$total}. Procesando hasta {$limite}.");

        if ($adjuntos->isEmpty()) {
            $this->info('No hay adjuntos pendientes.');
            return 0;
        }

        $bar = $this->output->createProgressBar($adjuntos->count());
        $bar->start();

        $actualizados = 0;
        $errores = 0;

        foreach ($adjuntos as $adjunto) {
            $ruta = Storage::disk('public')->path($adjunto->ubicacion);

            $duracion = AdjuntoController::extraerDuracion($ruta);

            if ($duracion) {
                $adjunto->duracion = $duracion;
                $adjunto->save();
                $actualizados++;
            } else {
                $this->newLine();
                $this->warn("  Sin duración: [{$adjunto->id_adjunto}] {$adjunto->nombre_original}");
                $errores++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Completado: {$actualizados} actualizados, {$errores} sin duración detectada.");

        if ($total > $limite) {
            $this->warn("Quedan " . ($total - $limite) . " adjuntos pendientes. Ejecute el comando nuevamente.");
        }

        return 0;
    }
}
