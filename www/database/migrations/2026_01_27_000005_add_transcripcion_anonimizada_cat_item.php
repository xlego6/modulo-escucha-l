<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTranscripcionAnonimizadaCatItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            INSERT INTO catalogos.cat_item (id_item, id_cat, descripcion, abreviado, habilitado, orden)
            VALUES (314, 19, 'Transcripción pública anonimizada', 'TRANS_ANON_PUB', 1, 3)
            ON CONFLICT (id_item) DO NOTHING
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DELETE FROM catalogos.cat_item WHERE id_item = 314");
    }
}
