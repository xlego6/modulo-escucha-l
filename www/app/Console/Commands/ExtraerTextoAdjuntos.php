<?php

namespace App\Console\Commands;

use App\Services\TextExtractorService;
use Illuminate\Console\Command;

class ExtraerTextoAdjuntos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'adjuntos:extraer-texto
                            {--limite=50 : Numero maximo de adjuntos a procesar}
                            {--id= : ID especifico de adjunto a procesar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extrae el texto de documentos adjuntos (PDF, DOCX, TXT) para indexacion';

    /**
     * Execute the console command.
     */
    public function handle(TextExtractorService $extractor)
    {
        $this->info('Iniciando extraccion de texto de adjuntos...');

        if ($id = $this->option('id')) {
            // Procesar un adjunto especifico
            $adjunto = \App\Models\Adjunto::find($id);

            if (!$adjunto) {
                $this->error('No se encontro el adjunto con ID: ' . $id);
                return 1;
            }

            $this->info('Procesando: ' . $adjunto->nombre_original);

            $texto = $extractor->extraerTexto($adjunto);
            $adjunto->texto_extraido = $texto;
            $adjunto->texto_extraido_at = now();
            $adjunto->save();

            if ($texto) {
                $this->info('Texto extraido: ' . strlen($texto) . ' caracteres');
                $this->line('Primeros 500 caracteres:');
                $this->line(substr($texto, 0, 500) . '...');
            } else {
                $this->warn('No se pudo extraer texto del documento');
            }

            return 0;
        }

        // Procesar lote
        $limite = (int) $this->option('limite');
        $resultados = $extractor->procesarPendientes($limite);

        $this->info('Procesamiento completado:');
        $this->table(
            ['Metrica', 'Valor'],
            [
                ['Procesados', $resultados['procesados']],
                ['Exitosos', $resultados['exitosos']],
                ['Fallidos', $resultados['fallidos']],
            ]
        );

        if ($resultados['procesados'] === 0) {
            $this->info('No hay adjuntos pendientes de procesar.');
        }

        return 0;
    }
}
