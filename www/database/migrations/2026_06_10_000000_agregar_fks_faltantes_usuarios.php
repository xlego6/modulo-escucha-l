<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Agrega las claves foráneas faltantes hacia public.users (config/fks_pendientes.php).
 *
 * Son columnas nullable de "quién realizó la acción / creado por" que nunca tuvieron
 * constraint. La migración es:
 *   - Aditiva: no toca datos, solo agrega integridad referencial.
 *   - Idempotente: omite la FK si ya existe (seguro re-ejecutar tras rollback).
 *   - Reversible: down() elimina exactamente las FKs que crea.
 *
 * IMPORTANTE: si alguna columna tiene valores huérfanos, el ADD CONSTRAINT falla y la
 * migración se revierte completa (DDL transaccional en PostgreSQL). Ejecute antes:
 *     php artisan fk:verificar
 */
class AgregarFksFaltantesUsuarios extends Migration
{
    public function up()
    {
        $cfg = config('fks_pendientes');
        $ref = "\"{$cfg['ref_esquema']}\".\"{$cfg['ref_tabla']}\"";
        $onDelete = $cfg['on_delete'];

        foreach ($cfg['fks'] as $fk) {
            $tabla   = "\"{$fk['esquema']}\".\"{$fk['tabla']}\"";
            $nombre  = "{$fk['tabla']}_{$fk['columna']}_fkey";

            if ($this->existeConstraint($fk['esquema'], $fk['tabla'], $nombre)) {
                echo "  OMITE (ya existe): {$nombre}\n";
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE %s ADD CONSTRAINT "%s" FOREIGN KEY ("%s") '
                . 'REFERENCES %s ("%s") ON UPDATE CASCADE ON DELETE %s',
                $tabla, $nombre, $fk['columna'], $ref, $cfg['ref_columna'], $onDelete
            ));
            echo "  AGREGA: {$nombre}\n";
        }
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
