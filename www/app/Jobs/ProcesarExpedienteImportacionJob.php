<?php

namespace App\Jobs;

use App\Http\Controllers\AdjuntoController;
use App\Models\Adjunto;
use App\Models\CatItem;
use App\Models\ConsentimientoInformado;
use App\Models\ContenidoTestimonio;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Geo;
use App\Models\ImportacionExpediente;
use App\Models\ImportacionMasiva;
use App\Models\Persona;
use App\Models\PersonaEntrevistada;
use App\Models\TrazaActividad;
use App\Services\ImportacionMasivaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcesarExpedienteImportacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 3600; // 1 hora (por la copia/conversión de archivos)

    public function __construct(private int $idImpExpediente)
    {
    }

    public function handle(ImportacionMasivaService $svc): void
    {
        /** @var ImportacionExpediente $ie */
        $ie          = ImportacionExpediente::findOrFail($this->idImpExpediente);
        $importacion = $ie->rel_importacion;

        $ie->estado = ImportacionExpediente::ESTADO_PROCESANDO;
        $ie->save();

        $config        = $importacion->configuracion ?? [];
        $mapeosCat     = $config['mapeos_catalogos']          ?? [];
        $mapeosGeo     = $config['mapeos_geo']                ?? [];
        $pathMappings  = $config['path_mappings']             ?? [];
        $idEntrevistador = (int) ($config['id_entrevistador'] ?? 0);
        $tratamientoTranscripcion = $config['tratamiento_transcripcion'] ?? 'automatizada';

        $cols     = $ie->datos_csv['cols']     ?? [];
        $personas = $ie->datos_csv['personas'] ?? [];
        $archivos = $ie->archivos              ?? [];

        try {
            DB::beginTransaction();

            // ------------------------------------------------------------------
            // 1. Resolver entrevistador
            // ------------------------------------------------------------------
            $entrevistador = Entrevistador::findOrFail($idEntrevistador);

            // ------------------------------------------------------------------
            // 2. Crear Entrevista (Paso 1)
            // ------------------------------------------------------------------
            $siguienteNumero = Entrevista::where('id_entrevistador', $entrevistador->id_entrevistador)
                ->where('id_activo', 1)
                ->max('entrevista_numero') + 1;

            $idDependencia = $this->mapear($svc, $mapeosCat, 'dependencia', $this->col($cols, 9));
            $idTipoTest    = $this->mapear($svc, $mapeosCat, 'tipo_testimonio', $this->col($cols, 12));

            $codigo     = $this->generarCodigo($entrevistador, $siguienteNumero, $idDependencia);
            $correlativo = Entrevista::max('entrevista_correlativo') + 1;

            $idTerritorio  = $this->resolverGeo($svc, $mapeosGeo, $this->col($cols, 15), null, 2);
            $idLugar       = $this->resolverGeo($svc, $mapeosGeo, $this->col($cols, 15), $this->col($cols, 16), 3);

            $fechaIni = $svc->parseFecha($this->col($cols, 18));
            $fechaFin = $svc->parseFecha($this->col($cols, 19));

            $tieneAnexos = $svc->parseBool($this->col($cols, 24));

            $entrevista = Entrevista::create([
                'entrevista_codigo'     => $codigo,
                'entrevista_numero'     => $siguienteNumero,
                'entrevista_correlativo' => $correlativo,
                'id_entrevistador'      => $entrevistador->id_entrevistador,
                'id_macroterritorio'    => $entrevistador->id_macroterritorio,
                'numero_entrevistador'  => $entrevistador->numero_entrevistador,
                'titulo'               => $this->col($cols, 8) ?: 'Sin título',
                'id_dependencia_origen' => $idDependencia,
                'id_equipo_estrategia'  => $this->mapear($svc, $mapeosCat, 'equipo_estrategia', $this->col($cols, 10)),
                'nombre_proyecto'       => $this->col($cols, 11),
                'id_tipo_testimonio'    => $idTipoTest,
                'num_testimoniantes'    => max(1, (int) ($this->col($cols, 14) ?: count($personas) ?: 1)),
                'id_territorio'         => $idTerritorio,
                'entrevista_lugar'      => $idLugar,
                'fecha_toma_inicial'    => $fechaIni,
                'fecha_toma_final'      => $fechaFin,
                'entrevista_fecha'      => $fechaIni,
                'tiene_anexos'          => $tieneAnexos,
                'descripcion_anexos'    => $this->col($cols, 25),
                'observaciones_toma'    => $this->col($cols, 23),
                'nombre_entrevistador'  => $entrevistador->rel_usuario->name ?? '',
                'id_activo'             => 1,
                'id_subserie'           => 1,
            ]);

            // Relaciones múltiples paso 1
            $this->syncPivot('esclarecimiento.entrevista_formato',
                $entrevista->id_e_ind_fvt, 'id_formato',
                $this->mapearLista($svc, $mapeosCat, 'formato', $this->col($cols, 13)));

            $this->syncPivot('esclarecimiento.entrevista_modalidad',
                $entrevista->id_e_ind_fvt, 'id_modalidad',
                $this->mapearLista($svc, $mapeosCat, 'modalidad', $this->col($cols, 17)));

            $this->syncPivot('esclarecimiento.entrevista_necesidad_reparacion',
                $entrevista->id_e_ind_fvt, 'id_necesidad',
                $this->mapearLista($svc, $mapeosCat, 'necesidad_reparacion', $this->col($cols, 21)));

            $this->syncPivot('esclarecimiento.entrevista_area_compatible',
                $entrevista->id_e_ind_fvt, 'id_area',
                $this->mapearLista($svc, $mapeosCat, 'areas_compatibles', $this->col($cols, 22)));

            $this->syncPivot('esclarecimiento.entrevista_idioma',
                $entrevista->id_e_ind_fvt, 'id_idioma',
                $this->mapearLista($svc, $mapeosCat, 'idioma', $this->col($cols, 20)));

            // ------------------------------------------------------------------
            // 3. Crear Personas + Consentimientos (Paso 2)
            // ------------------------------------------------------------------
            foreach ($personas as $pDatos) {
                $partes = $this->partirNombreApellido($pDatos['nombre']);

                $idSexo        = $this->mapear($svc, $mapeosCat, 'sexo', $pDatos['sexo']);
                $idIdentidad   = $this->mapear($svc, $mapeosCat, 'identidad_genero', $pDatos['identidad_genero']);
                $idOrientacion = $this->mapear($svc, $mapeosCat, 'orientacion_sexual', $pDatos['orientacion_sexual']);
                $idEtnia       = $this->mapear($svc, $mapeosCat, 'etnia', $pDatos['etnia']);
                $idRango       = $this->mapear($svc, $mapeosCat, 'rango_etario', $pDatos['rango_etario']);
                $idDiscap      = $this->mapear($svc, $mapeosCat, 'discapacidad', $pDatos['discapacidad']);

                $idOrigenDepto = $this->resolverGeo($svc, $mapeosGeo, $pDatos['lugar_origen_depto'], null, 2);
                $idOrigenMuni  = $this->resolverGeo($svc, $mapeosGeo, $pDatos['lugar_origen_depto'], $pDatos['lugar_origen_muni'], 3);

                $persona = Persona::create([
                    'nombre'                   => $partes['nombre'],
                    'apellido'                 => $partes['apellido'],
                    'nombre_identitario'       => $pDatos['nombre_identitario'] ?: null,
                    'id_lugar_nacimiento_depto' => $idOrigenDepto,
                    'id_lugar_nacimiento'      => $idOrigenMuni,
                    'id_sexo'                  => $idSexo,
                    'id_identidad'             => $idIdentidad,
                    'id_orientacion'           => $idOrientacion,
                    'id_etnia'                 => $idEtnia,
                    'id_rango_etario'          => $idRango,
                    'id_discapacidad'          => $idDiscap,
                ]);

                // Poblaciones y ocupaciones de la persona
                $idsPoblacion = $this->mapearLista($svc, $mapeosCat, 'poblacion', $pDatos['poblacion']);
                $idsOcupacion = $this->mapearLista($svc, $mapeosCat, 'ocupacion', $pDatos['ocupacion']);

                foreach ($idsPoblacion as $idPob) {
                    DB::table('fichas.persona_poblacion')->insert(['id_persona' => $persona->id_persona, 'id_poblacion' => $idPob, 'created_at' => now()]);
                }
                foreach ($idsOcupacion as $idOcup) {
                    DB::table('fichas.persona_ocupacion')->insert(['id_persona' => $persona->id_persona, 'id_ocupacion' => $idOcup, 'created_at' => now()]);
                }

                $pe = PersonaEntrevistada::create([
                    'id_persona'    => $persona->id_persona,
                    'id_e_ind_fvt'  => $entrevista->id_e_ind_fvt,
                    'edad'          => is_numeric($pDatos['edad']) ? (int) $pDatos['edad'] : null,
                ]);

                // Consentimiento
                $tieneDoc = $svc->parseBool($pDatos['consent_tiene'], 0);

                ConsentimientoInformado::create([
                    'id_persona_entrevistada'              => $pe->id_persona_entrevistada,
                    'tiene_documento_autorizacion'         => $tieneDoc,
                    'es_menor_edad'                        => $svc->parseBool($pDatos['consent_menor']),
                    'autoriza_ser_entrevistado'            => $svc->parseBool($pDatos['consent_autoriza'], 1),
                    'permite_grabacion'                    => $svc->parseBool($pDatos['consent_grabacion'], 1),
                    'permite_procesamiento_misional'       => $svc->parseBool($pDatos['consent_procesamiento'], 1),
                    'permite_uso_conservacion_consulta'    => $svc->parseBool($pDatos['consent_uso'], 1),
                    'considera_riesgo_seguridad'           => $svc->parseBool($pDatos['consent_riesgo']),
                    'autoriza_datos_personales_sin_anonimizar' => $svc->parseBool($pDatos['consent_datos_pers']),
                    'autoriza_datos_sensibles_sin_anonimizar'  => $svc->parseBool($pDatos['consent_datos_sens']),
                    'observaciones'                        => $pDatos['consent_obs'] ?: null,
                ]);
            }

            // ------------------------------------------------------------------
            // 4. Crear Contenido del testimonio (Paso 3)
            // ------------------------------------------------------------------
            $fechaHechosIni = $svc->parseFecha($this->col($cols, 60));
            $fechaHechosFin = $svc->parseFecha($this->col($cols, 61));

            if ($fechaHechosIni || $fechaHechosFin) {
                $entrevista->update([
                    'hechos_del' => $fechaHechosIni,
                    'hechos_al'  => $fechaHechosFin,
                ]);
            }

            $contenido = ContenidoTestimonio::create([
                'id_e_ind_fvt'             => $entrevista->id_e_ind_fvt,
                'fecha_hechos_inicial'     => $fechaHechosIni,
                'fecha_hechos_final'       => $fechaHechosFin,
                'responsables_individuales' => $this->col($cols, 59),
                'temas_abordados'          => $this->col($cols, 64),
            ]);

            $relacionesContenido = [
                'contenido_poblacion'       => ['id_poblacion',  $this->mapearLista($svc, $mapeosCat, 'contenido_poblacion',    $this->col($cols, 49))],
                'contenido_ocupacion'       => ['id_ocupacion',  $this->mapearLista($svc, $mapeosCat, 'contenido_ocupacion',    $this->col($cols, 50))],
                'contenido_sexo'            => ['id_sexo',       $this->mapearLista($svc, $mapeosCat, 'contenido_sexo',         $this->col($cols, 51))],
                'contenido_identidad_genero' => ['id_identidad', $this->mapearLista($svc, $mapeosCat, 'contenido_identidad',    $this->col($cols, 52))],
                'contenido_orientacion_sexual' => ['id_orientacion', $this->mapearLista($svc, $mapeosCat, 'contenido_orientacion', $this->col($cols, 53))],
                'contenido_etnia'           => ['id_etnia',      $this->mapearLista($svc, $mapeosCat, 'contenido_etnia',        $this->col($cols, 54))],
                'contenido_rango_etario'    => ['id_rango',      $this->mapearLista($svc, $mapeosCat, 'contenido_rango_etario', $this->col($cols, 55))],
                'contenido_discapacidad'    => ['id_discapacidad', $this->mapearLista($svc, $mapeosCat, 'contenido_discapacidad', $this->col($cols, 56))],
                'contenido_hecho_victimizante' => ['id_hecho',   $this->mapearLista($svc, $mapeosCat, 'contenido_hecho',        $this->col($cols, 57))],
                'contenido_responsable'     => ['id_responsable', $this->mapearLista($svc, $mapeosCat, 'contenido_responsable', $this->col($cols, 58))],
            ];

            foreach ($relacionesContenido as $tabla => [$campo, $ids]) {
                foreach ($ids as $idVal) {
                    DB::table("esclarecimiento.$tabla")->insert([
                        'id_e_ind_fvt' => $entrevista->id_e_ind_fvt,
                        $campo         => $idVal,
                    ]);
                }
            }

            // Lugares mencionados en el contenido
            $idContenidoDepto = $this->resolverGeo($svc, $mapeosGeo, $this->col($cols, 62), null, 2);
            $idContenidoMuni  = $this->resolverGeo($svc, $mapeosGeo, $this->col($cols, 62), $this->col($cols, 63), 3);
            if ($idContenidoDepto || $idContenidoMuni) {
                DB::table('esclarecimiento.contenido_lugar')->insert([
                    'id_e_ind_fvt'   => $entrevista->id_e_ind_fvt,
                    'id_departamento' => $idContenidoDepto,
                    'id_municipio'   => $idContenidoMuni,
                ]);
            }

            DB::commit();

            // ------------------------------------------------------------------
            // 5. Copiar / convertir archivos y crear Adjuntos (fuera de la tx)
            // ------------------------------------------------------------------
            $carpetaStorage = 'adjuntos/' . $entrevista->entrevista_codigo;
            Storage::disk('public')->makeDirectory($carpetaStorage);

            // Pasada 1: todos los archivos excepto transcripciones .txt
            // Se construye un mapa fila_idx → Adjunto de audio para usarlo en la pasada 2.
            $audioAdjuntos = [];
            foreach ($archivos as $arch) {
                if ($arch['id_tipo'] === 313) continue;
                $adj = $this->procesarArchivo($arch, $entrevista, $carpetaStorage, $svc, $tratamientoTranscripcion);
                if ($adj && $arch['id_tipo'] === 310) {
                    $audioAdjuntos[$arch['fila_idx'] ?? count($audioAdjuntos)] = $adj;
                }
            }

            // Pasada 2: transcripciones (.txt u otros tipos 313)
            $hayTranscripciones = false;
            foreach ($archivos as $arch) {
                if ($arch['id_tipo'] !== 313) continue;
                $this->procesarArchivo($arch, $entrevista, $carpetaStorage, $svc, $tratamientoTranscripcion, $audioAdjuntos);
                $hayTranscripciones = true;
            }

            // Regenerar transcripción consolidada (id_tipo 312) a partir de los texto_extraido individuales
            if ($hayTranscripciones && in_array($tratamientoTranscripcion, ['automatizada', 'ambos'])) {
                $this->regenerarTranscripcionCompleta($entrevista);
            }

            // ------------------------------------------------------------------
            // 6. Marcar expediente como completado
            // ------------------------------------------------------------------
            $ie->id_e_ind_fvt = $entrevista->id_e_ind_fvt;
            $ie->estado        = ImportacionExpediente::ESTADO_COMPLETADO;
            $ie->save();

            TrazaActividad::create([
                'fecha_hora'  => now(),
                'id_usuario'  => $importacion->id_usuario,
                'accion'      => 'importar',
                'objeto'      => 'entrevista',
                'id_registro' => $entrevista->id_e_ind_fvt,
                'codigo'      => $entrevista->entrevista_codigo,
                'referencia'  => 'Importación masiva ID=' . $importacion->id_importacion . ', CSV ID=' . $ie->id_csv,
                'ip'          => '127.0.0.1',
            ]);

            // ------------------------------------------------------------------
            // 7. Transcripción automática si hay GPU disponible
            // ------------------------------------------------------------------
            $this->despacharTranscripcionSiGpu($entrevista);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("ImportacionJob #{$this->idImpExpediente}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $ie->estado       = ImportacionExpediente::ESTADO_ERROR;
            $ie->error_mensaje = Str::limit($e->getMessage(), 500);
            $ie->save();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers de archivos
    // -------------------------------------------------------------------------

    /**
     * Procesa un archivo del expediente.
     *
     * Para archivos de audio (id_tipo 310) retorna el Adjunto creado, para que
     * la pasada de transcripciones pueda asociarlos por fila_idx.
     * Para el resto retorna null.
     *
     * $audioAdjuntos: mapa [fila_idx => Adjunto] construido en la pasada 1.
     */
    private function procesarArchivo(
        array $arch,
        Entrevista $entrevista,
        string $carpetaStorage,
        ImportacionMasivaService $svc,
        string $tratamientoTranscripcion = 'automatizada',
        array $audioAdjuntos = []
    ): ?Adjunto {
        if (!($arch['existe'] ?? false) || ($arch['es_directorio'] ?? false)) {
            Log::warning("ImportacionJob: archivo no encontrado o es directorio: " . ($arch['ruta_linux'] ?? $arch['ruta']));
            return null;
        }

        $rutaFuente = $arch['ruta_linux'];
        $tamano     = $arch['tamano'] ?? filesize($rutaFuente);
        $idTipo     = $arch['id_tipo'];
        $convertir  = $arch['convertir'] ?? ($tamano > 500 * 1024 * 1024);

        // Archivos de transcripción (col. 77): tratamiento configurable
        if ($idTipo === 313) {
            if ($tratamientoTranscripcion === 'automatizada' || $tratamientoTranscripcion === 'ambos') {
                // Buscar el Adjunto de audio del mismo renglón del CSV
                $filaIdx       = $arch['fila_idx'] ?? null;
                $audioAdjunto  = ($filaIdx !== null && isset($audioAdjuntos[$filaIdx]))
                    ? $audioAdjuntos[$filaIdx]
                    : null;
                $this->ingestarComoTranscripcionAutomatizada($rutaFuente, $entrevista, $audioAdjunto);
            }
            if ($tratamientoTranscripcion === 'adjunto' || $tratamientoTranscripcion === 'ambos') {
                $this->copiarDirecto($rutaFuente, $entrevista, $carpetaStorage, $idTipo, $tamano);
            }
            return null;
        }

        if ($convertir && $idTipo === 310) {
            $this->copiarConvertirM4a($rutaFuente, $entrevista, $carpetaStorage);
            // Devolver el adjunto recién creado para el mapa de pares
            return Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
                ->where('id_tipo', 310)
                ->latest('id_adjunto')
                ->first();
        }

        return $this->copiarDirecto($rutaFuente, $entrevista, $carpetaStorage, $idTipo, $tamano);
    }

    /**
     * Lee el .txt y guarda su contenido como transcripción automatizada.
     *
     * Si se proporciona $audioAdjunto, el texto se guarda en ese adjunto de audio
     * (texto_extraido), igual que hace Whisper. Así cada audio conserva su propia
     * transcripción y la vista las muestra individualmente.
     *
     * Si no hay adjunto de audio asociado (caso fallback), llama directamente a
     * guardarTranscripcionAutomatizada() para guardar el texto consolidado.
     */
    private function ingestarComoTranscripcionAutomatizada(
        string $rutaFuente,
        Entrevista $entrevista,
        ?Adjunto $audioAdjunto = null
    ): void {
        $contenido = file_get_contents($rutaFuente);
        if ($contenido === false) {
            throw new \RuntimeException("No se pudo leer el archivo de transcripción: $rutaFuente");
        }

        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        $contenido = trim($contenido);

        if ($audioAdjunto) {
            // Guardar en el adjunto del audio correspondiente, igual que Whisper
            $audioAdjunto->texto_extraido    = $contenido;
            $audioAdjunto->texto_extraido_at = now();
            $audioAdjunto->save();
        } else {
            // Fallback: sin audio asociado, guardar directo en el adjunto consolidado
            $entrevista->guardarTranscripcionAutomatizada($contenido, basename($rutaFuente));
        }
    }

    /**
     * Concatena el texto_extraido de todos los audios del expediente y actualiza
     * el adjunto de transcripción automatizada consolidada (id_tipo 312).
     * Replica la lógica de ProcesamientoController::regenerarTranscripcionCompleta().
     */
    private function regenerarTranscripcionCompleta(Entrevista $entrevista): void
    {
        $adjuntos = Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->where(function ($q) {
                $q->where('tipo_mime', 'like', '%audio%')
                  ->orWhere('tipo_mime', 'like', '%video%');
            })
            ->whereNotNull('texto_extraido')
            ->orderBy('id_adjunto')
            ->get();

        if ($adjuntos->count() > 1) {
            $textoCompleto = '';
            foreach ($adjuntos as $adj) {
                $textoCompleto .= "\n\n=== {$adj->nombre_original} ===\n\n" . $adj->texto_extraido;
            }
            $entrevista->guardarTranscripcionAutomatizada(trim($textoCompleto));
        } elseif ($adjuntos->count() === 1) {
            $entrevista->guardarTranscripcionAutomatizada($adjuntos->first()->texto_extraido);
        }
    }

    private function copiarDirecto(string $rutaFuente, Entrevista $entrevista, string $carpetaStorage, int $idTipo, int $tamano): ?Adjunto
    {
        $ext          = strtolower(pathinfo($rutaFuente, PATHINFO_EXTENSION));
        $nombreDest   = time() . '_' . Str::random(8) . '.' . $ext;
        $ubicacion    = $carpetaStorage . '/' . $nombreDest;
        $rutaDest     = Storage::disk('public')->path($ubicacion);

        if (!copy($rutaFuente, $rutaDest)) {
            throw new \RuntimeException("No se pudo copiar: $rutaFuente");
        }

        $mime    = mime_content_type($rutaDest) ?: 'application/octet-stream';
        $md5     = md5_file($rutaDest);

        // Verificar duplicado por hash en este expediente
        if (Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)->where('md5', $md5)->exists()) {
            @unlink($rutaDest);
            return null;
        }

        $adjunto = Adjunto::create([
            'id_e_ind_fvt'   => $entrevista->id_e_ind_fvt,
            'ubicacion'      => $ubicacion,
            'nombre_original' => basename($rutaFuente),
            'tipo_mime'      => $mime,
            'id_tipo'        => $idTipo,
            'tamano'         => filesize($rutaDest),
            'md5'            => $md5,
            'existe_archivo' => true,
        ]);

        // Extraer duración para audio/video
        if (str_contains($mime, 'audio') || str_contains($mime, 'video')) {
            $duracion = AdjuntoController::extraerDuracion($rutaDest);
            if ($duracion) {
                $adjunto->duracion = $duracion;
                $adjunto->save();
            }
        }

        return $adjunto;
    }

    private function copiarConvertirM4a(string $rutaFuente, Entrevista $entrevista, string $carpetaStorage): void
    {
        $ffmpeg       = trim(shell_exec('which ffmpeg 2>/dev/null') ?? '') ?: '/usr/bin/ffmpeg';
        $nombreM4a    = time() . '_' . Str::random(8) . '.m4a';
        $ubicacion    = $carpetaStorage . '/' . $nombreM4a;
        $rutaDest     = Storage::disk('public')->path($ubicacion);

        $cmd = sprintf(
            '%s -hide_banner -loglevel error -i %s -vn -c:a aac -b:a 64k -ar 22050 -ac 1 -y %s 2>&1',
            escapeshellcmd($ffmpeg),
            escapeshellarg($rutaFuente),
            escapeshellarg($rutaDest)
        );

        exec($cmd, $output, $rc);

        if ($rc !== 0 || !file_exists($rutaDest) || filesize($rutaDest) === 0) {
            @unlink($rutaDest);
            throw new \RuntimeException('Error ffmpeg al convertir a m4a: ' . implode(' ', array_slice($output, -3)));
        }

        $md5 = md5_file($rutaDest);

        if (Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)->where('md5', $md5)->exists()) {
            @unlink($rutaDest);
            return;
        }

        $adjunto = Adjunto::create([
            'id_e_ind_fvt'    => $entrevista->id_e_ind_fvt,
            'ubicacion'       => $ubicacion,
            'nombre_original' => pathinfo($rutaFuente, PATHINFO_FILENAME) . '.m4a',
            'tipo_mime'       => 'audio/mp4',
            'id_tipo'         => 310,
            'tamano'          => filesize($rutaDest),
            'tamano_bruto'    => filesize($rutaFuente),
            'md5'             => $md5,
            'existe_archivo'  => true,
        ]);

        $duracion = AdjuntoController::extraerDuracion($rutaDest);
        if ($duracion) {
            $adjunto->duracion = $duracion;
            $adjunto->save();
        }
    }

    // -------------------------------------------------------------------------
    // Helpers de catálogos y geografía
    // -------------------------------------------------------------------------

    /**
     * Aplica un mapeo de catálogo: dado el valor de texto del CSV,
     * devuelve el id_item correspondiente o null.
     */
    private function mapear(ImportacionMasivaService $svc, array $mapeos, string $campo, string $valor): ?int
    {
        $valor = trim($valor);
        if ($valor === '' || strtolower($valor) === 'n/a') return null;

        $idItem = $mapeos[$campo][$valor] ?? null;
        return $idItem ? (int) $idItem : null;
    }

    /**
     * Igual que mapear() pero para un campo con múltiples valores separados por , o ;
     * Retorna array de ids (sin nulls).
     */
    private function mapearLista(ImportacionMasivaService $svc, array $mapeos, string $campo, string $valor): array
    {
        $items = $svc->parseLista($valor);
        $ids   = [];
        foreach ($items as $item) {
            $id = $this->mapear($svc, $mapeos, $campo, $item);
            if ($id !== null) $ids[] = $id;
        }
        return $ids;
    }

    /**
     * Resuelve un par (departamento, municipio) a su id_geo.
     * $nivel: 2=departamento, 3=municipio
     */
    private function resolverGeo(ImportacionMasivaService $svc, array $mapeosGeo, string $depto, ?string $muni, int $nivel): ?int
    {
        if ($nivel === 2) {
            $id = $mapeosGeo['lugar_depto'][$depto] ?? null;
            return $id ? (int) $id : null;
        }

        if ($nivel === 3 && $muni !== null) {
            $key = "$depto||$muni";
            $id  = $mapeosGeo['lugar_muni'][$key] ?? null;
            return $id ? (int) $id : null;
        }

        return null;
    }

    private function syncPivot(string $tabla, int $idEntrevista, string $campo, array $ids): void
    {
        DB::table($tabla)->where('id_e_ind_fvt', $idEntrevista)->delete();
        foreach ($ids as $id) {
            DB::table($tabla)->insert(['id_e_ind_fvt' => $idEntrevista, $campo => $id, 'created_at' => now()]);
        }
    }

    // -------------------------------------------------------------------------
    // Helpers varios
    // -------------------------------------------------------------------------

    /** Devuelve una columna del array de columnas CSV (sin excepción si no existe). */
    private function col(array $cols, int $idx): string
    {
        return trim($cols[$idx] ?? '');
    }

    /**
     * Genera el código de entrevista igual que en EntrevistaWizardController.
     */
    private function generarCodigo(Entrevistador $ent, int $numero, ?int $idDependencia): string
    {
        $prefijo = 'TES';
        if ($idDependencia) {
            $dep = CatItem::find($idDependencia);
            if ($dep && $dep->abreviado) {
                $prefijo = $dep->abreviado;
            }
        }
        $numEnt  = str_pad($ent->numero_entrevistador ?? 0, 4, '0', STR_PAD_LEFT);
        $numEntr = str_pad($numero, 3, '0', STR_PAD_LEFT);
        return "$prefijo-$numEnt-$numEntr";
    }

    /**
     * Divide "Apellido Apellido, Nombre" o "Nombre Apellido" en partes.
     */
    private function partirNombreApellido(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        if (str_contains($nombreCompleto, ',')) {
            [$apellido, $nombre] = array_map('trim', explode(',', $nombreCompleto, 2));
            return ['nombre' => $nombre, 'apellido' => $apellido];
        }
        $palabras = preg_split('/\s+/', $nombreCompleto);
        $mitad    = (int) ceil(count($palabras) / 2);
        return [
            'nombre'   => implode(' ', array_slice($palabras, 0, $mitad)),
            'apellido' => implode(' ', array_slice($palabras, $mitad)),
        ];
    }

    /**
     * Despacha transcripción automática si nvidia-smi indica GPU disponible.
     */
    private function despacharTranscripcionSiGpu(Entrevista $entrevista): void
    {
        $gpuDisponible = false;
        exec('nvidia-smi --query-gpu=name --format=csv,noheader 2>/dev/null', $out, $rc);
        if ($rc === 0 && !empty($out)) {
            $gpuDisponible = true;
        }

        if (!$gpuDisponible) return;

        $audios = Adjunto::where('id_e_ind_fvt', $entrevista->id_e_ind_fvt)
            ->where('id_tipo', 310)
            ->get();

        foreach ($audios as $adjunto) {
            $ruta = Storage::disk('public')->path($adjunto->ubicacion);
            if (file_exists($ruta)) {
                ProcesarTranscripcion::dispatch(
                    $entrevista->id_e_ind_fvt,
                    $adjunto->id_adjunto,
                    true,
                    $entrevista->id_entrevistador
                );
            }
        }
    }
}
