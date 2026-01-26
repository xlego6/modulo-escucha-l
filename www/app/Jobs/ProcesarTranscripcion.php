<?php

namespace App\Jobs;

use App\Models\Entrevista;
use App\Models\Adjunto;
use App\Services\ProcesamientoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcesarTranscripcion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $entrevistaId;
    public $adjuntoId;
    public $conDiarizacion;
    public $usuarioId;

    /**
     * Número de intentos
     */
    public $tries = 3;

    /**
     * Timeout en segundos (30 minutos)
     */
    public $timeout = 1800;

    /**
     * Create a new job instance.
     */
    public function __construct(int $entrevistaId, int $adjuntoId, bool $conDiarizacion = true, ?int $usuarioId = null)
    {
        $this->entrevistaId = $entrevistaId;
        $this->adjuntoId = $adjuntoId;
        $this->conDiarizacion = $conDiarizacion;
        $this->usuarioId = $usuarioId;
    }

    /**
     * Execute the job.
     */
    public function handle(ProcesamientoService $procesamientoService)
    {
        $entrevista = Entrevista::findOrFail($this->entrevistaId);
        $adjunto = Adjunto::findOrFail($this->adjuntoId);

        // Registrar inicio
        $trabajo = $this->registrarTrabajo('procesando');

        try {
            // Obtener ruta del archivo
            $ruta = Storage::disk('public')->path($adjunto->ubicacion);

            if (!file_exists($ruta)) {
                throw new \Exception("Archivo no encontrado: {$adjunto->ubicacion}");
            }

            Log::info("Iniciando transcripción de entrevista {$this->entrevistaId}", [
                'archivo' => $adjunto->nombre_original,
                'diarizacion' => $this->conDiarizacion,
            ]);

            // Llamar al servicio de transcripción
            $resultado = $procesamientoService->transcribe($ruta, $this->conDiarizacion);

            if (!$resultado['success']) {
                throw new \Exception($resultado['error'] ?? 'Error desconocido en transcripción');
            }

            // Guardar transcripción
            $texto = $resultado['text'] ?? '';

            // Formatear con timestamps si están disponibles
            if (!empty($resultado['segments'])) {
                $texto = $this->formatearConTimestamps($resultado['segments']);

                // Guardar segmentos en tabla separada
                $this->guardarSegmentos($resultado['segments']);
            }

            // Guardar como adjunto de tipo "Transcripción Automatizada"
            $entrevista->guardarTranscripcionAutomatizada($texto, $adjunto->nombre_original);

            // Marcar trabajo como completado
            $this->actualizarTrabajo($trabajo, 'completado', 100, 'Transcripción completada exitosamente', [
                'caracteres' => strlen($texto),
                'segmentos' => count($resultado['segments'] ?? []),
            ]);

            Log::info("Transcripción completada para entrevista {$this->entrevistaId}", [
                'caracteres' => strlen($texto),
            ]);

        } catch (\Exception $e) {
            Log::error("Error en transcripción de entrevista {$this->entrevistaId}: " . $e->getMessage());

            $this->actualizarTrabajo($trabajo, 'fallido', 0, $e->getMessage());

            throw $e; // Re-lanzar para que Laravel maneje los reintentos
        }
    }

    /**
     * Formatear transcripción con timestamps
     */
    private function formatearConTimestamps(array $segmentos): string
    {
        $texto = '';

        foreach ($segmentos as $seg) {
            $inicio = $this->formatearTiempo($seg['start'] ?? 0);
            $hablante = $seg['speaker'] ?? '';
            $contenido = trim($seg['text'] ?? '');

            if ($hablante) {
                $texto .= "[{$inicio}] {$hablante}: {$contenido}\n\n";
            } else {
                $texto .= "[{$inicio}] {$contenido}\n\n";
            }
        }

        return trim($texto);
    }

    /**
     * Formatear tiempo en MM:SS
     */
    private function formatearTiempo(float $segundos): string
    {
        $minutos = floor($segundos / 60);
        $segs = floor($segundos % 60);
        return sprintf('%02d:%02d', $minutos, $segs);
    }

    /**
     * Guardar segmentos en la base de datos
     */
    private function guardarSegmentos(array $segmentos): void
    {
        // Eliminar segmentos anteriores
        DB::table('esclarecimiento.transcripcion_segmento')
            ->where('id_e_ind_fvt', $this->entrevistaId)
            ->delete();

        foreach ($segmentos as $i => $seg) {
            DB::table('esclarecimiento.transcripcion_segmento')->insert([
                'id_e_ind_fvt' => $this->entrevistaId,
                'tiempo_inicio' => $seg['start'] ?? null,
                'tiempo_fin' => $seg['end'] ?? null,
                'texto' => trim($seg['text'] ?? ''),
                'hablante' => $seg['speaker'] ?? null,
                'orden' => $i,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Registrar trabajo en la tabla de procesamiento
     */
    private function registrarTrabajo(string $estado): int
    {
        return DB::table('esclarecimiento.trabajo_procesamiento')->insertGetId([
            'id_e_ind_fvt' => $this->entrevistaId,
            'tipo' => 'transcripcion',
            'estado' => $estado,
            'progreso' => 0,
            'parametros' => json_encode([
                'adjunto_id' => $this->adjuntoId,
                'diarizacion' => $this->conDiarizacion,
            ]),
            'id_usuario' => $this->usuarioId,
            'iniciado_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Actualizar estado del trabajo
     */
    private function actualizarTrabajo(int $id, string $estado, int $progreso, ?string $mensaje = null, ?array $resultado = null): void
    {
        $datos = [
            'estado' => $estado,
            'progreso' => $progreso,
            'mensaje' => $mensaje,
            'updated_at' => now(),
        ];

        if ($estado === 'completado' || $estado === 'fallido') {
            $datos['completado_at'] = now();
        }

        if ($resultado) {
            $datos['resultado'] = json_encode($resultado);
        }

        DB::table('esclarecimiento.trabajo_procesamiento')
            ->where('id_trabajo', $id)
            ->update($datos);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de transcripción falló definitivamente para entrevista {$this->entrevistaId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
