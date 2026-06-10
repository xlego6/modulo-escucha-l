<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Verifica, antes de agregar las claves foráneas faltantes (config/fks_pendientes.php),
 * que no existan valores huérfanos: filas cuyo valor NO NULL no tiene correspondencia
 * en la tabla destino. Si los hubiera, el ADD CONSTRAINT de la migración fallaría.
 *
 * Solo lee; no modifica nada. Devuelve código de salida 1 si encuentra huérfanos,
 * para poder encadenarlo con la migración de forma segura.
 */
class VerificarHuerfanosFk extends Command
{
    protected $signature = 'fk:verificar {--detalle : Muestra hasta 10 valores huérfanos por columna}';

    protected $description = 'Verifica valores huérfanos en las columnas que recibirán FK (config/fks_pendientes.php)';

    public function handle()
    {
        $cfg = config('fks_pendientes');
        $ref = "{$cfg['ref_esquema']}.{$cfg['ref_tabla']}";
        $refCol = $cfg['ref_columna'];

        $this->info("Verificando huérfanos contra {$ref}({$refCol})");
        $this->newLine();

        $filas = [];
        $totalHuerfanos = 0;

        foreach ($cfg['fks'] as $fk) {
            $tabla = "{$fk['esquema']}.{$fk['tabla']}";
            $col   = $fk['columna'];

            $huerfanos = DB::selectOne("
                SELECT COUNT(*) AS n
                FROM {$tabla} t
                WHERE t.{$col} IS NOT NULL
                  AND NOT EXISTS (
                      SELECT 1 FROM {$ref} r WHERE r.{$refCol} = t.{$col}
                  )
            ")->n;

            $totalHuerfanos += $huerfanos;
            $filas[] = ["{$tabla}.{$col}", $huerfanos, $huerfanos == 0 ? 'OK' : '⚠ HUÉRFANOS'];

            if ($huerfanos > 0 && $this->option('detalle')) {
                $ids = DB::select("
                    SELECT DISTINCT t.{$col} AS v
                    FROM {$tabla} t
                    WHERE t.{$col} IS NOT NULL
                      AND NOT EXISTS (SELECT 1 FROM {$ref} r WHERE r.{$refCol} = t.{$col})
                    LIMIT 10
                ");
                $this->line("  {$tabla}.{$col} → valores sin destino: "
                    . implode(', ', array_map(fn($x) => $x->v, $ids)));
            }
        }

        $this->table(['Columna', 'Huérfanos', 'Estado'], $filas);

        if ($totalHuerfanos === 0) {
            $this->info('Sin huérfanos. Es seguro aplicar la migración de FKs.');
            return 0;
        }

        $this->error("Se encontraron {$totalHuerfanos} valor(es) huérfano(s). "
            . 'La migración de FKs fallaría. Corrija los datos (poner NULL o reasignar) '
            . 'antes de migrar. Use --detalle para ver los valores.');
        return 1;
    }
}
