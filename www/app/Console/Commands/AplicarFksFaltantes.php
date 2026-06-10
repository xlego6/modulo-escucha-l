<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aplica las claves foráneas faltantes definidas en config/fks_pendientes.php.
 *
 * Idempotente: omite la FK si ya existe (por nombre), así que es seguro re-ejecutar
 * cada vez que se agreguen entradas al config, sin necesidad de nuevas migraciones.
 * La migración 2026_06_10_000000_agregar_fks_faltantes_usuarios delega en este comando.
 *
 * Antes de correrlo conviene `php artisan fk:verificar`: si hay huérfanos, el
 * ADD CONSTRAINT falla (PostgreSQL revierte el statement).
 */
class AplicarFksFaltantes extends Command
{
    protected $signature = 'fk:aplicar {--dry-run : Muestra los ALTER TABLE sin ejecutarlos}';

    protected $description = 'Crea las FKs faltantes (config/fks_pendientes.php); idempotente';

    public function handle()
    {
        $cfg = config('fks_pendientes');
        $ref = "\"{$cfg['ref_esquema']}\".\"{$cfg['ref_tabla']}\"";
        $dry = $this->option('dry-run');
        $creadas = 0; $omitidas = 0;

        foreach ($cfg['fks'] as $fk) {
            $tabla  = "\"{$fk['esquema']}\".\"{$fk['tabla']}\"";
            $nombre = "{$fk['tabla']}_{$fk['columna']}_fkey";

            if ($this->existeConstraint($fk['esquema'], $fk['tabla'], $nombre)) {
                $this->line("  OMITE (ya existe): {$nombre}");
                $omitidas++;
                continue;
            }

            $sql = sprintf(
                'ALTER TABLE %s ADD CONSTRAINT "%s" FOREIGN KEY ("%s") '
                . 'REFERENCES %s ("%s") ON UPDATE CASCADE ON DELETE %s',
                $tabla, $nombre, $fk['columna'], $ref, $cfg['ref_columna'], $cfg['on_delete']
            );

            if ($dry) {
                $this->line($sql);
            } else {
                DB::statement($sql);
                $this->info("  AGREGA: {$nombre}");
            }
            $creadas++;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY-RUN] ' : '') . "FKs creadas: {$creadas} | Omitidas (ya existían): {$omitidas}");
        return 0;
    }

    private function existeConstraint(string $esquema, string $tabla, string $nombre): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conrelid = ?::regclass AND conname = ?',
            ["\"{$esquema}\".\"{$tabla}\"", $nombre]
        ) !== null;
    }
}
