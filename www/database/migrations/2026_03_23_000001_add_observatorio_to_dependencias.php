<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddObservatorioToDependencias extends Migration
{
    public function up()
    {
        DB::statement("
            INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, habilitado, orden)
            VALUES (330, 4, 'Observatorio de Memoria y Conflicto', 'OMC', 1, 99)
            ON CONFLICT (id_item) DO NOTHING
        ");
    }

    public function down()
    {
        DB::statement("DELETE FROM catalogos.cat_item WHERE id_item = 330");
    }
}
