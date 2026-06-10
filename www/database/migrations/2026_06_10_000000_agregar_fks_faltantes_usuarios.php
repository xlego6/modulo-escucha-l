<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Agrega las claves foráneas faltantes hacia public.users (config/fks_pendientes.php).
 *
 * Son columnas nullable de "quién realizó la acción / creado por" que nunca tuvieron
 * constraint. La migración es:
 *   - Aditiva: no toca datos, solo agrega integridad referencial.
 *   - Idempotente: delega en `fk:aplicar`, que omite las FKs ya existentes.
 *   - Reversible: down() elimina exactamente las FKs definidas en el config.
 *
 * Como la lógica vive en `fk:aplicar`, agregar entradas al config NO requiere una nueva
 * migración: basta `php artisan fk:aplicar` (esta migración ya figura como ejecutada).
 *
 * IMPORTANTE: si alguna columna tiene valores huérfanos, el ADD CONSTRAINT falla y la
 * migración se revierte completa (DDL transaccional en PostgreSQL). Ejecute antes:
 *     php artisan fk:verificar
 */
class AgregarFksFaltantesUsuarios extends Migration
{
    public function up()
    {
        Artisan::call('fk:aplicar');
    }

    public function down()
    {
        $cfg = config('fks_pendientes');

        foreach ($cfg['fks'] as $fk) {
            $tabla  = "\"{$fk['esquema']}\".\"{$fk['tabla']}\"";
            $nombre = "{$fk['tabla']}_{$fk['columna']}_fkey";

            if ($this->existeConstraint($fk['esquema'], $fk['tabla'], $nombre)) {
                DB::statement(sprintf('ALTER TABLE %s DROP CONSTRAINT "%s"', $tabla, $nombre));
            }
        }
    }

    private function existeConstraint(string $esquema, string $tabla, string $nombre): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conrelid = ?::regclass AND conname = ?',
            ["\"{$esquema}\".\"{$tabla}\"", $nombre]
        ) !== null;
    }
}
