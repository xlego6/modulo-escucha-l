<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Remapea las etiquetas de entidad_detectada.tipo (y el placeholder guardado en
 * texto_anonimizado) de la taxonomia anterior (PER/LOC/ORG/DATE) a la nueva
 * (PERSONA/LUGAR/ORGANIZACION/FECHA), para datos de ejercicios piloto que hayan
 * quedado con la taxonomia vieja. Solo se remapean los tipos con equivalente
 * claro y directo; EVENT, GUN y MISC no tienen equivalente en la taxonomia
 * nueva y se dejan intactos a proposito para revision manual.
 *
 * Se hace fila por fila en PHP (no con regexp_replace/backreferences de SQL)
 * para evitar problemas de escapado de "\1" entre PHP/PDO/Postgres.
 */
return new class extends Migration
{
    private array $mapeo = [
        'PER' => 'PERSONA',
        'LOC' => 'LUGAR',
        'ORG' => 'ORGANIZACION',
        'DATE' => 'FECHA',
    ];

    public function up(): void
    {
        $this->remapear($this->mapeo);
    }

    public function down(): void
    {
        $this->remapear(array_flip($this->mapeo));
    }

    private function remapear(array $mapeo): void
    {
        foreach ($mapeo as $tipoOrigen => $tipoDestino) {
            $filas = DB::table('esclarecimiento.entidad_detectada')
                ->where('tipo', $tipoOrigen)
                ->get(['id_entidad', 'texto_anonimizado']);

            foreach ($filas as $fila) {
                $nuevoPlaceholder = $fila->texto_anonimizado;
                if ($nuevoPlaceholder !== null) {
                    $nuevoPlaceholder = preg_replace(
                        '/^\[' . preg_quote($tipoOrigen, '/') . '(_\d+)?\]$/',
                        '[' . $tipoDestino . '$1]',
                        $nuevoPlaceholder
                    );
                }

                DB::table('esclarecimiento.entidad_detectada')
                    ->where('id_entidad', $fila->id_entidad)
                    ->update([
                        'tipo' => $tipoDestino,
                        'texto_anonimizado' => $nuevoPlaceholder,
                    ]);
            }
        }
    }
};
