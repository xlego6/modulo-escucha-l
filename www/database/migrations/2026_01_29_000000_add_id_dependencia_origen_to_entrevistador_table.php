<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddIdDependenciaOrigenToEntrevistadorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.entrevistador ADD COLUMN IF NOT EXISTS id_dependencia_origen INTEGER REFERENCES catalogos.cat_item(id_item)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.entrevistador DROP COLUMN IF EXISTS id_dependencia_origen');
    }
}
