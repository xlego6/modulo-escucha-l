<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deuda técnica (2): blinda los "valores mágicos" de esclarecimiento.permiso.
 *
 *   id_tipo   ∈ {1=lectura, 2=escritura, 3=completo}   (Permiso::TIPO_*)
 *   id_estado ∈ {1=vigente, 2=revocado}                (Permiso::ESTADO_*)
 *
 * Hoy son enteros sin validación. Se agregan CHECK que permiten esos valores o NULL
 * (NULL IN (...) → desconocido → el CHECK no falla, así que la nulabilidad se respeta).
 *
 * Verificado el 2026-06-10: en pruebas y producción todos los valores existentes caen
 * dentro del rango y no hay NULLs, por lo que los ADD CONSTRAINT no fallan.
 *
 * NO se usa FK a criterio_fijo a propósito: id_opcion 1/2/3 de ese catálogo son
 * "niveles de usuario", no tipos de permiso (el belongsTo del modelo apunta al catálogo
 * equivocado: bug latente a corregir por separado).
 *
 * Idempotente (omite el CHECK si ya existe) y reversible.
 */
class AddChecksPermisoTipoEstado extends Migration
{
    private array $checks = [
        'permiso_id_tipo_check'   => 'id_tipo IN (1, 2, 3)',
        'permiso_id_estado_check' => 'id_estado IN (1, 2)',
    ];

    public function up()
    {
        foreach ($this->checks as $nombre => $condicion) {
            if ($this->existeConstraint($nombre)) {
                echo "  OMITE (ya existe): {$nombre}\n";
                continue;
            }
            DB::statement(sprintf(
                'ALTER TABLE "esclarecimiento"."permiso" ADD CONSTRAINT "%s" CHECK (%s)',
                $nombre, $condicion
            ));
            echo "  AGREGA: {$nombre}\n";
        }
    }

    public function down()
    {
        foreach (array_keys($this->checks) as $nombre) {
            if ($this->existeConstraint($nombre)) {
                DB::statement(sprintf(
                    'ALTER TABLE "esclarecimiento"."permiso" DROP CONSTRAINT "%s"', $nombre
                ));
            }
        }
    }

    private function existeConstraint(string $nombre): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM pg_constraint WHERE conrelid = ?::regclass AND conname = ?',
            ['"esclarecimiento"."permiso"', $nombre]
        ) !== null;
    }
}
