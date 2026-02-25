<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddGestorConocimientoToCriterioFijo extends Migration
{
    /**
     * Run the migrations.
     * Agrega el nivel 5 = Gestor de Conocimiento al catálogo de niveles (id_grupo = 1).
     */
    public function up()
    {
        DB::statement("
              INSERT INTO catalogos.criterio_fijo (id_opcion, id_grupo, descripcion, habilitado, orden)
              SELECT 5, 1, 'Gestor de Conocimiento', 1, 5
              WHERE NOT EXISTS (
                 SELECT 1
                 FROM catalogos.criterio_fijo
                 WHERE id_grupo = 1 AND id_opcion = 5
              )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("
            DELETE FROM catalogos.criterio_fijo WHERE id_grupo = 1 AND id_opcion = 5
        ");
    }
}
