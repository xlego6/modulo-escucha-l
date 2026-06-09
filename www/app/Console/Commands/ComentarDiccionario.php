<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aplica las descripciones del diccionario (archivo versionado en el repo)
 * a la base de datos como COMMENT ON TABLE / COMMENT ON COLUMN.
 *
 * Es la fuente única de las descripciones: al ejecutarse en pruebas y en
 * producción deja ambas bases con comentarios idénticos, de modo que
 * `dic:generar` produce el mismo Markdown en las dos máquinas.
 *
 * Idempotente: re-ejecutar simplemente reescribe el mismo texto.
 * Tolera drift: si una tabla o columna no existe, la omite con aviso.
 */
class ComentarDiccionario extends Command
{
    protected $signature = 'dic:comentar
                            {--archivo=database/diccionario/descripciones.php : Ruta (relativa a base_path) del archivo de descripciones}
                            {--dry-run : Muestra lo que haría sin escribir en la BD}';

    protected $description = 'Aplica las descripciones del diccionario a la BD como COMMENT ON (siembra reproducible)';

    public function handle()
    {
        $ruta = base_path($this->option('archivo'));
        if (!file_exists($ruta)) {
            $this->error("No existe el archivo de descripciones: $ruta");
            return 1;
        }

        $desc = require $ruta;
        if (!is_array($desc)) {
            $this->error('El archivo de descripciones debe retornar un array.');
            return 1;
        }

        $dry = $this->option('dry-run');
        $aplicados = 0; $omitidos = 0;

        foreach ($desc as $clave => $texto) {
            $partes = explode('.', $clave);
            $texto  = (string) $texto;

            if (count($partes) === 2) {
                [$esq, $tab] = $partes;
                if (!$this->existeTabla($esq, $tab)) {
                    $this->warn("  OMITE (tabla inexistente): {$clave}");
                    $omitidos++; continue;
                }
                $sql = sprintf('COMMENT ON TABLE %s.%s IS %s',
                    $this->id($esq), $this->id($tab), $this->lit($texto));
            } elseif (count($partes) === 3) {
                [$esq, $tab, $col] = $partes;
                if (!$this->existeColumna($esq, $tab, $col)) {
                    $this->warn("  OMITE (columna inexistente): {$clave}");
                    $omitidos++; continue;
                }
                $sql = sprintf('COMMENT ON COLUMN %s.%s.%s IS %s',
                    $this->id($esq), $this->id($tab), $this->id($col), $this->lit($texto));
            } else {
                $this->warn("  OMITE (clave inválida): {$clave}");
                $omitidos++; continue;
            }

            if ($dry) {
                $this->line($sql);
            } else {
                DB::statement($sql);
            }
            $aplicados++;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "Comentarios aplicados: {$aplicados} | Omitidos: {$omitidos}");
        if (!$dry) {
            $this->info('Ejecute `php artisan dic:generar` para regenerar el Markdown.');
        }
        return 0;
    }

    private function existeTabla(string $esq, string $tab): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$esq, $tab]) !== null;
    }

    private function existeColumna(string $esq, string $tab, string $col): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?',
            [$esq, $tab, $col]) !== null;
    }

    /** Identificador citado de forma segura. */
    private function id(string $s): string
    {
        return '"' . str_replace('"', '""', $s) . '"';
    }

    /** Literal de cadena para SQL (COMMENT ON no admite parámetros enlazados). */
    private function lit(string $s): string
    {
        return "'" . str_replace("'", "''", $s) . "'";
    }
}
