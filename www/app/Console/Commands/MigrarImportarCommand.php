<?php

namespace App\Console\Commands;

use App\Models\Adjunto;
use App\Models\AsignacionAnonimizacion;
use App\Models\AsignacionTranscripcion;
use App\Models\CatItem;
use App\Models\ConsentimientoInformado;
use App\Models\Consentimiento;
use App\Models\ContenidoTestimonio;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use App\Models\Persona;
use App\Models\PersonaEntrevistada;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class MigrarImportarCommand extends Command
{
    protected $signature = 'migrar:importar
                            {archivo : Ruta al ZIP generado por migrar:exportar}
                            {--dry-run : Validar sin insertar nada en la base de datos}';

    protected $description = 'Importa entrevistas desde un ZIP de migración, generando nuevos códigos en este servidor';

    private bool $dryRun;
    private array $entrevistadorCache = [];
    private array $usuarioCache       = [];

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');
        $zipPath      = $this->argument('archivo');

        if (!file_exists($zipPath)) {
            $this->error("Archivo no encontrado: $zipPath");
            return 1;
        }

        // Extraer ZIP a directorio temporal
        $tmpDir = sys_get_temp_dir() . '/migrar_import_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error("No se pudo abrir el ZIP.");
            return 1;
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $jsonPath = $tmpDir . '/data.json';
        if (!file_exists($jsonPath)) {
            $this->error("El ZIP no contiene data.json.");
            return 1;
        }

        $data        = json_decode(file_get_contents($jsonPath), true);
        $expedientes = $data['expedientes'] ?? [];

        if (empty($expedientes)) {
            $this->error("No hay expedientes en el archivo.");
            return 1;
        }

        $this->info(sprintf(
            'Importando %d expediente(s) — versión %s exportada el %s%s',
            count($expedientes),
            $data['version'] ?? '?',
            $data['exported_at'] ?? '?',
            $this->dryRun ? ' [DRY RUN - sin cambios]' : ''
        ));

        $ok     = 0;
        $errores = [];

        foreach ($expedientes as $idx => $exp) {
            $sourceId     = $exp['source_id'];
            $sourceCodigo = $exp['source_codigo'];
            $this->line("  [{$idx}] {$sourceCodigo} (ID origen: {$sourceId})...");

            try {
                $this->importarExpediente($exp, $tmpDir);
                $ok++;
                $this->info("    ✓ Importado");
            } catch (\Throwable $e) {
                $errores[] = "{$sourceCodigo}: " . $e->getMessage();
                $this->error("    ✗ " . $e->getMessage());
            }
        }

        // Limpiar temporales
        $this->limpiarDir($tmpDir);

        $this->newLine();
        $this->info("Resultado: {$ok}/" . count($expedientes) . " importados.");
        if (!empty($errores)) {
            $this->warn('Errores:');
            foreach ($errores as $err) {
                $this->warn("  - $err");
            }
        }

        return empty($errores) ? 0 : 1;
    }

    private function importarExpediente(array $exp, string $tmpDir): void
    {
        // --- Resolver entrevistador en este servidor por email ---
        $entrevistador = $this->resolverEntrevistador($exp['entrevistador_email']);
        if (!$entrevistador) {
            throw new \RuntimeException(
                "Entrevistador con email '{$exp['entrevistador_email']}' no encontrado en este servidor."
            );
        }

        if ($this->dryRun) {
            $this->line("    [dry] Entrevistador mapeado: {$entrevistador->rel_usuario->email}");
            return;
        }

        DB::transaction(function () use ($exp, $entrevistador, $tmpDir) {

            // --- Generar nuevo código ---
            $siguienteNumero = $this->calcularSiguienteNumero($entrevistador->id_entrevistador);
            $correlativo     = $this->calcularCorrelativo();
            $nuevoCodigo     = $this->generarCodigo($entrevistador, $siguienteNumero, $exp['e_ind_fvt']['id_dependencia_origen'] ?? null);

            // --- Insertar e_ind_fvt ---
            $eData = array_merge($exp['e_ind_fvt'], [
                'id_entrevistador'       => $entrevistador->id_entrevistador,
                'numero_entrevistador'   => $entrevistador->numero_entrevistador,
                'entrevista_codigo'      => $nuevoCodigo,
                'entrevista_numero'      => $siguienteNumero,
                'entrevista_correlativo' => $correlativo,
            ]);
            $entrevista = Entrevista::create($eData);
            $newId      = $entrevista->id_e_ind_fvt;

            $this->line("    Nuevo código: {$nuevoCodigo} (ID: {$newId})");

            // --- Consentimiento (fichas.entrevista) ---
            if (!empty($exp['consentimiento'])) {
                Consentimiento::create(array_merge($exp['consentimiento'], ['id_e_ind_fvt' => $newId]));
            }

            // --- Contenido testimonio + pivots ---
            if (!empty($exp['contenido'])) {
                $ct = $exp['contenido'];
                $contenido = ContenidoTestimonio::create(array_merge($ct['datos'], ['id_e_ind_fvt' => $newId]));

                $pivotContenido = [
                    'esclarecimiento.contenido_poblacion'         => ['id_poblacion',   $ct['poblaciones']   ?? []],
                    'esclarecimiento.contenido_ocupacion'         => ['id_ocupacion',   $ct['ocupaciones']   ?? []],
                    'esclarecimiento.contenido_sexo'              => ['id_sexo',        $ct['sexos']         ?? []],
                    'esclarecimiento.contenido_identidad_genero'  => ['id_identidad',   $ct['identidades']   ?? []],
                    'esclarecimiento.contenido_orientacion_sexual'=> ['id_orientacion', $ct['orientaciones'] ?? []],
                    'esclarecimiento.contenido_etnia'             => ['id_etnia',       $ct['etnias']        ?? []],
                    'esclarecimiento.contenido_rango_etario'      => ['id_rango',       $ct['rangos_etarios']?? []],
                    'esclarecimiento.contenido_discapacidad'      => ['id_discapacidad',$ct['discapacidades']?? []],
                    'esclarecimiento.contenido_hecho_victimizante'=> ['id_hecho',       $ct['hechos']        ?? []],
                    'esclarecimiento.contenido_responsable'       => ['id_responsable', $ct['responsables']  ?? []],
                    'esclarecimiento.contenido_practica_resistencia'=>['id_practica',   $ct['practicas']     ?? []],
                ];
                foreach ($pivotContenido as $tabla => [$col, $ids]) {
                    foreach ($ids as $idItem) {
                        DB::table($tabla)->insert(['id_e_ind_fvt' => $newId, $col => $idItem]);
                    }
                }

                foreach ($ct['lugares'] ?? [] as $lugar) {
                    if (!empty($lugar['id_departamento']) || !empty($lugar['id_municipio'])) {
                        DB::table('esclarecimiento.contenido_lugar')->insert([
                            'id_e_ind_fvt'   => $newId,
                            'id_departamento' => $lugar['id_departamento'] ?? null,
                            'id_municipio'    => $lugar['id_municipio'] ?? null,
                        ]);
                    }
                }
            }

            // --- Personas ---
            foreach ($exp['personas'] ?? [] as $pData) {
                $persona = Persona::create($pData['persona']);

                foreach ($pData['poblaciones'] ?? [] as $idItem) {
                    DB::table('fichas.persona_poblacion')->insert(['id_persona' => $persona->id_persona, 'id_poblacion' => $idItem]);
                }
                foreach ($pData['ocupaciones'] ?? [] as $idItem) {
                    DB::table('fichas.persona_ocupacion')->insert(['id_persona' => $persona->id_persona, 'id_ocupacion' => $idItem]);
                }

                $pe = PersonaEntrevistada::create(array_merge(
                    $pData['persona_entrevistada'],
                    ['id_persona' => $persona->id_persona, 'id_e_ind_fvt' => $newId]
                ));

                if (!empty($pData['consentimiento_inform'])) {
                    ConsentimientoInformado::create(array_merge(
                        $pData['consentimiento_inform'],
                        ['id_persona_entrevistada' => $pe->id_persona_entrevistada]
                    ));
                }
            }

            // --- Adjuntos + archivos físicos ---
            $nuevoCarpeta = 'adjuntos/' . Str::slug($nuevoCodigo);

            foreach ($exp['adjuntos'] ?? [] as $adjData) {
                $datos     = $adjData['datos'];
                $fileInZip = $adjData['file_in_zip'];
                $livianoInZip = $adjData['liviano_in_zip'] ?? null;

                // Copiar archivo principal
                $nuevaUbicacion = '';
                if ($fileInZip && file_exists($tmpDir . '/' . $fileInZip)) {
                    $ext            = pathinfo($fileInZip, PATHINFO_EXTENSION);
                    $nombreArchivo  = time() . '_' . Str::random(8) . '.' . $ext;
                    $dirDisco       = storage_path('app/public/' . $nuevaCarpeta);
                    if (!is_dir($dirDisco)) {
                        mkdir($dirDisco, 0775, true);
                    }
                    copy($tmpDir . '/' . $fileInZip, $dirDisco . '/' . $nombreArchivo);
                    $nuevaUbicacion = $nuevaCarpeta . '/' . $nombreArchivo;
                }

                // Copiar archivo liviano
                $nuevaUbicacionLiviana = null;
                if ($livianoInZip && file_exists($tmpDir . '/' . $livianoInZip)) {
                    $ext            = pathinfo($livianoInZip, PATHINFO_EXTENSION);
                    $nombreLiviano  = 'lv_' . time() . '_' . Str::random(8) . '.' . $ext;
                    $dirDisco       = storage_path('app/public/' . $nuevaCarpeta);
                    if (!is_dir($dirDisco)) {
                        mkdir($dirDisco, 0775, true);
                    }
                    copy($tmpDir . '/' . $livianoInZip, $dirDisco . '/' . $nombreLiviano);
                    $nuevaUbicacionLiviana = $nuevaCarpeta . '/' . $nombreLiviano;
                }

                $adj = Adjunto::create(array_merge($datos, [
                    'id_e_ind_fvt'     => $newId,
                    'ubicacion'        => $nuevaUbicacion,
                    'liviano_ubicacion'=> $nuevaUbicacionLiviana,
                    'existe_archivo'   => $nuevaUbicacion ? 1 : 0,
                ]));

                // Asignación de transcripción
                if (!empty($adjData['asignacion_transcripcion'])) {
                    $at = $adjData['asignacion_transcripcion'];
                    AsignacionTranscripcion::create(array_merge(
                        $this->sinColumnas($at, ['transcriptor_email', 'asignado_por_email', 'revisor_email']),
                        [
                            'id_e_ind_fvt'   => $newId,
                            'id_adjunto'     => $adj->id_adjunto,
                            'id_transcriptor'=> $this->resolverEntrevistador($at['transcriptor_email'] ?? null)?->id_entrevistador,
                            'id_asignado_por'=> $this->resolverUsuario($at['asignado_por_email'] ?? null)?->id,
                            'id_revisor'     => $this->resolverUsuario($at['revisor_email'] ?? null)?->id,
                        ]
                    ));
                }
            }

            // --- Asignación de anonimización ---
            if (!empty($exp['asignacion_anonimizacion'])) {
                $aa = $exp['asignacion_anonimizacion'];
                AsignacionAnonimizacion::create(array_merge(
                    $this->sinColumnas($aa, ['anonimizador_email', 'asignado_por_email', 'revisor_email']),
                    [
                        'id_e_ind_fvt'    => $newId,
                        'id_anonimizador' => $this->resolverEntrevistador($aa['anonimizador_email'] ?? null)?->id_entrevistador,
                        'id_asignado_por' => $this->resolverUsuario($aa['asignado_por_email'] ?? null)?->id,
                        'id_revisor'      => $this->resolverUsuario($aa['revisor_email'] ?? null)?->id,
                    ]
                ));
            }

            // --- Entidades detectadas ---
            foreach ($exp['entidades_detectadas'] ?? [] as $entidad) {
                DB::table('esclarecimiento.entidad_detectada')->insert(
                    array_merge($entidad, ['id_e_ind_fvt' => $newId])
                );
            }

            // --- Pivots de la entrevista ---
            $pivotEntrevista = [
                'esclarecimiento.entrevista_formato'             => ['id_formato',   $exp['pivot_formatos']  ?? []],
                'esclarecimiento.entrevista_modalidad'           => ['id_modalidad', $exp['pivot_modalidades']?? []],
                'esclarecimiento.entrevista_necesidad_reparacion'=> ['id_necesidad', $exp['pivot_necesidades']?? []],
                'esclarecimiento.entrevista_idioma'              => ['id_idioma',    $exp['pivot_idiomas']   ?? []],
                'esclarecimiento.entrevista_area_compatible'     => ['id_area',      $exp['pivot_areas']     ?? []],
            ];
            foreach ($pivotEntrevista as $tabla => [$col, $ids]) {
                foreach ($ids as $idItem) {
                    DB::table($tabla)->insert(['id_e_ind_fvt' => $newId, $col => $idItem]);
                }
            }
        });
    }

    // -------------------------------------------------------------------------
    // Helpers de mapeo de IDs
    // -------------------------------------------------------------------------

    private function resolverEntrevistador(?string $email): ?Entrevistador
    {
        if (!$email) return $this->entrevistadorAdmin();
        if (array_key_exists($email, $this->entrevistadorCache)) {
            return $this->entrevistadorCache[$email];
        }
        $user   = User::where('email', $email)->first();
        $result = $user ? Entrevistador::where('id_usuario', $user->id)->first() : null;

        if (!$result) {
            $this->warn("    ⚠ Entrevistador '{$email}' no encontrado — asignado al entrevistador 0000 (admin)");
            $result = $this->entrevistadorAdmin();
        }

        $this->entrevistadorCache[$email] = $result;
        return $result;
    }

    private function entrevistadorAdmin(): ?Entrevistador
    {
        if (array_key_exists('__admin__', $this->entrevistadorCache)) {
            return $this->entrevistadorCache['__admin__'];
        }
        $admin = Entrevistador::where('numero_entrevistador', 0)->first();
        if (!$admin) {
            throw new \RuntimeException('No existe el entrevistador 0000 (admin) en este servidor.');
        }
        $this->entrevistadorCache['__admin__'] = $admin;
        return $admin;
    }

    private function resolverUsuario(?string $email): ?User
    {
        if (!$email) return null;
        if (array_key_exists($email, $this->usuarioCache)) {
            return $this->usuarioCache[$email];
        }
        $result = User::where('email', $email)->first();
        $this->usuarioCache[$email] = $result;
        return $result;
    }

    // -------------------------------------------------------------------------
    // Generación de código (misma lógica que EntrevistaWizardController)
    // -------------------------------------------------------------------------

    private function calcularSiguienteNumero(int $idEntrevistador): int
    {
        $ultimo = Entrevista::where('id_entrevistador', $idEntrevistador)
            ->where('id_activo', 1)
            ->max('entrevista_numero');
        return ($ultimo ?? 0) + 1;
    }

    private function calcularCorrelativo(): int
    {
        return (Entrevista::max('entrevista_correlativo') ?? 0) + 1;
    }

    private function generarCodigo(Entrevistador $entrevistador, int $numero, ?int $idDependencia): string
    {
        $prefijo  = 'TES';
        if ($idDependencia) {
            $dep = CatItem::find($idDependencia);
            if ($dep?->abreviado) {
                $prefijo = $dep->abreviado;
            }
        }
        $numEnt  = str_pad($entrevistador->numero_entrevistador ?? 0, 4, '0', STR_PAD_LEFT);
        $numEntr = str_pad($numero, 3, '0', STR_PAD_LEFT);
        return "{$prefijo}-{$numEnt}-{$numEntr}";
    }

    // -------------------------------------------------------------------------
    // Utilidades
    // -------------------------------------------------------------------------

    private function sinColumnas(array $attrs, array $excluir): array
    {
        return array_diff_key($attrs, array_flip($excluir));
    }

    private function limpiarDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->limpiarDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
