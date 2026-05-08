<?php

namespace App\Console\Commands;

use App\Models\AsignacionTranscripcion;
use App\Models\Entrevista;
use App\Models\Entrevistador;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ZipArchive;

class MigrarExportarCommand extends Command
{
    protected $signature = 'migrar:exportar
                            {--ids= : IDs de e_ind_fvt separados por coma}
                            {--all  : Exportar todas las entrevistas activas}
                            {--listar : Muestra los expedientes disponibles con su ID y código, sin exportar}';

    protected $description = 'Exporta entrevistas a un ZIP con datos JSON + archivos físicos para migración entre servidores';

    public function handle(): int
    {
        // --- Modo listar ---
        if ($this->option('listar')) {
            $rows = Entrevista::where('id_activo', 1)
                ->orderBy('id_e_ind_fvt')
                ->get(['id_e_ind_fvt', 'entrevista_codigo', 'titulo', 'entrevista_fecha'])
                ->map(fn($e) => [
                    $e->id_e_ind_fvt,
                    $e->entrevista_codigo ?? '—',
                    Str::limit($e->titulo ?? '—', 60),
                    $e->entrevista_fecha ?? '—',
                ])->toArray();

            $this->table(['ID', 'Código', 'Título', 'Fecha'], $rows);
            $this->line('');
            $this->line('Use: php artisan migrar:exportar --ids=<ID1,ID2,...>');
            return 0;
        }

        // --- Resolver IDs ---
        if ($this->option('all')) {
            $ids = Entrevista::where('id_activo', 1)->pluck('id_e_ind_fvt')->toArray();
        } elseif ($this->option('ids')) {
            $ids = array_map('intval', explode(',', $this->option('ids')));
        } else {
            $this->error('Debe especificar --ids=1,2,3 o --all  (use --listar para ver los disponibles)');
            return 1;
        }

        if (empty($ids)) {
            $this->error('No se encontraron entrevistas con los IDs indicados.');
            return 1;
        }

        $this->info('Exportando ' . count($ids) . ' entrevista(s)...');

        $outputPath = '/tmp/migracion_export_' . date('Ymd_His') . '.zip';

        // Crear ZIP
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("No se pudo crear el archivo ZIP en: $outputPath");
            return 1;
        }

        $expedientes = [];
        $bar = $this->output->createProgressBar(count($ids));
        $bar->start();

        foreach ($ids as $id) {
            $entrevista = Entrevista::find($id);
            if (!$entrevista) {
                $this->newLine();
                $this->warn("ID $id no encontrado, omitiendo.");
                $bar->advance();
                continue;
            }

            $expedientes[] = $this->exportarExpediente($entrevista, $zip);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Guardar JSON en el ZIP
        $json = json_encode([
            'version'     => '1.0',
            'exported_at' => now()->toIso8601String(),
            'total'       => count($expedientes),
            'expedientes' => $expedientes,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $zip->addFromString('data.json', $json);
        $zip->close();

        $mb          = round(filesize($outputPath) / 1048576, 2);
        $containerName = gethostname(); // nombre del container
        $this->info("ZIP generado: $outputPath ({$mb} MB)");
        $this->newLine();
        $this->line('Para descargarlo a tu máquina local, corre este comando <comment>fuera del container</comment>:');
        $this->line("  docker cp modulo-escucha-l-php-1:{$outputPath} ~/migracion_export.zip");
        return 0;
    }

    private function exportarExpediente(Entrevista $e, ZipArchive $zip): array
    {
        // Entrevistador — email para mapeo cross-server
        $entrevistador = $e->rel_entrevistador()->with('rel_usuario')->first();
        $entrevistadorEmail  = $entrevistador?->rel_usuario?->email;
        $entrevistadorNumero = $entrevistador?->numero_entrevistador;

        // Datos principales (excluir PK e id_entrevistador que se remapea)
        $eData = $this->sinColumnas($e->getAttributes(), ['id_e_ind_fvt', 'id_entrevistador']);

        // Consentimiento (fichas.entrevista)
        $consent = $e->rel_consentimiento;
        $consentData = $consent
            ? $this->sinColumnas($consent->getAttributes(), ['id_entrevista', 'id_e_ind_fvt'])
            : null;

        // Contenido testimonio + pivots
        $contenido = $e->rel_contenido;
        $contenidoData = null;
        if ($contenido) {
            $contenidoData = [
                'datos'        => $this->sinColumnas($contenido->getAttributes(), ['id_contenido', 'id_e_ind_fvt']),
                'poblaciones'  => $contenido->rel_poblaciones()->pluck('id_item')->toArray(),
                'ocupaciones'  => $contenido->rel_ocupaciones()->pluck('id_item')->toArray(),
                'sexos'        => $contenido->rel_sexos()->pluck('id_item')->toArray(),
                'identidades'  => $contenido->rel_identidades_genero()->pluck('id_item')->toArray(),
                'orientaciones'=> $contenido->rel_orientaciones_sexuales()->pluck('id_item')->toArray(),
                'etnias'       => $contenido->rel_etnias()->pluck('id_item')->toArray(),
                'rangos_etarios' => $contenido->rel_rangos_etarios()->pluck('id_item')->toArray(),
                'discapacidades' => $contenido->rel_discapacidades()->pluck('id_item')->toArray(),
                'hechos'         => $contenido->rel_hechos_victimizantes()->pluck('id_item')->toArray(),
                'responsables'   => $contenido->rel_responsables()->pluck('id_item')->toArray(),
                'practicas'      => $contenido->rel_practicas_resistencia()->pluck('id_item')->toArray(),
                'lugares'        => DB::table('esclarecimiento.contenido_lugar')
                    ->where('id_e_ind_fvt', $e->id_e_ind_fvt)
                    ->get(['id_departamento', 'id_municipio'])
                    ->toArray(),
            ];
        }

        // Personas
        $personas = [];
        foreach ($e->rel_personas_entrevistadas()->with(['rel_persona', 'rel_consentimiento'])->get() as $pe) {
            $persona = $pe->rel_persona;
            $ci      = $pe->rel_consentimiento;
            $personas[] = [
                'persona'               => $persona ? $this->sinColumnas($persona->getAttributes(), ['id_persona']) : null,
                'persona_entrevistada'  => $this->sinColumnas($pe->getAttributes(), ['id_persona_entrevistada', 'id_persona', 'id_e_ind_fvt']),
                'consentimiento_inform'  => $ci ? $this->sinColumnas($ci->getAttributes(), ['id_consentimiento', 'id_persona_entrevistada']) : null,
                'poblaciones'           => $persona ? $persona->rel_poblaciones()->pluck('id_item')->toArray() : [],
                'ocupaciones'           => $persona ? $persona->rel_ocupaciones()->pluck('id_item')->toArray() : [],
            ];
        }

        // Adjuntos + archivos físicos
        $adjuntos = [];
        $adjIdx   = 0;
        foreach ($e->rel_adjuntos()->get() as $adj) {
            $fileInZip = null;
            if ($adj->existe_archivo && $adj->ubicacion) {
                $diskPath = storage_path('app/public/' . $adj->ubicacion);
                if (file_exists($diskPath)) {
                    $ext       = pathinfo($adj->nombre_original, PATHINFO_EXTENSION);
                    $fileInZip = 'files/' . $e->id_e_ind_fvt . '_' . $adjIdx . '.' . $ext;
                    $zip->addFile($diskPath, $fileInZip);
                }
            }
            // Liviano (versión comprimida del audio/video)
            $livianoInZip = null;
            if (!empty($adj->liviano_ubicacion)) {
                $livianoPath = storage_path('app/public/' . $adj->liviano_ubicacion);
                if (file_exists($livianoPath)) {
                    $ext          = pathinfo($adj->liviano_ubicacion, PATHINFO_EXTENSION);
                    $livianoInZip = 'files/liviano_' . $e->id_e_ind_fvt . '_' . $adjIdx . '.' . $ext;
                    $zip->addFile($livianoPath, $livianoInZip);
                }
            }

            // Asignación de transcripción (FK: id_adjunto en AsignacionTranscripcion)
            $asTrans     = AsignacionTranscripcion::where('id_adjunto', $adj->id_adjunto)->first();
            $asTransData = null;
            if ($asTrans) {
                $asTransData = array_merge(
                    $this->sinColumnas($asTrans->getAttributes(), ['id_asignacion', 'id_e_ind_fvt', 'id_adjunto', 'id_transcriptor', 'id_asignado_por', 'id_revisor']),
                    [
                        'transcriptor_email' => $asTrans->rel_transcriptor?->rel_usuario?->email,
                        'asignado_por_email' => $asTrans->rel_asignado_por?->email,
                        'revisor_email'      => $asTrans->rel_revisor?->email,
                    ]
                );
            }

            $adjuntos[] = [
                'datos'                    => $this->sinColumnas($adj->getAttributes(), ['id_adjunto', 'id_e_ind_fvt']),
                'file_in_zip'              => $fileInZip,
                'liviano_in_zip'           => $livianoInZip,
                'asignacion_transcripcion' => $asTransData,
            ];
            $adjIdx++;
        }

        // Asignación de anonimización (por expediente, no por adjunto)
        $asAnon = DB::table('esclarecimiento.asignacion_anonimizacion')
            ->where('id_e_ind_fvt', $e->id_e_ind_fvt)
            ->first();
        $asAnonData = null;
        if ($asAnon) {
            $asAnonArr  = (array) $asAnon;
            $anonimizador = Entrevistador::with('rel_usuario')->find($asAnonArr['id_anonimizador'] ?? null);
            $asignadoPor  = \App\User::find($asAnonArr['id_asignado_por'] ?? null);
            $revisor      = \App\User::find($asAnonArr['id_revisor'] ?? null);
            $asAnonData   = array_merge(
                $this->sinColumnas($asAnonArr, ['id_asignacion', 'id_e_ind_fvt', 'id_anonimizador', 'id_asignado_por', 'id_revisor']),
                [
                    'anonimizador_email' => $anonimizador?->rel_usuario?->email,
                    'asignado_por_email' => $asignadoPor?->email,
                    'revisor_email'      => $revisor?->email,
                ]
            );
        }

        // Entidades detectadas
        $entidades = DB::table('esclarecimiento.entidad_detectada')
            ->where('id_e_ind_fvt', $e->id_e_ind_fvt)
            ->get()
            ->map(fn($row) => $this->sinColumnas((array) $row, ['id_entidad', 'id_e_ind_fvt']))
            ->toArray();

        // Pivots de la entrevista
        return [
            'source_id'             => $e->id_e_ind_fvt,
            'source_codigo'         => $e->entrevista_codigo,
            'entrevistador_email'   => $entrevistadorEmail,
            'entrevistador_numero'  => $entrevistadorNumero,
            'e_ind_fvt'             => $eData,
            'consentimiento'        => $consentData,
            'contenido'             => $contenidoData,
            'personas'              => $personas,
            'adjuntos'              => $adjuntos,
            'asignacion_anonimizacion' => $asAnonData,
            'entidades_detectadas'  => $entidades,
            'pivot_formatos'        => $e->rel_formatos()->pluck('id_item')->toArray(),
            'pivot_modalidades'     => $e->rel_modalidades()->pluck('id_item')->toArray(),
            'pivot_necesidades'     => $e->rel_necesidades_reparacion()->pluck('id_item')->toArray(),
            'pivot_idiomas'         => $e->rel_idiomas()->pluck('id_item')->toArray(),
            'pivot_areas'           => $e->rel_areas_compatibles()->pluck('id_item')->toArray(),
        ];
    }

    private function sinColumnas(array $attrs, array $excluir): array
    {
        return array_diff_key($attrs, array_flip($excluir));
    }
}
