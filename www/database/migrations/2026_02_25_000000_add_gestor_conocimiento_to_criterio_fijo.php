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
            INSERT INTO catalogos.criterio_fijo (id_grupo, id_opcion, descripcion, habilitado, orden)
            VALUES (1, 5, 'Gestor de Conocimiento', 1, 5)
            ON CONFLICT (id_grupo, id_opcion) DO NOTHING
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
