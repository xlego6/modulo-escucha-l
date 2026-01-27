<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddIdEstadoToPermisoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS id_estado INTEGER DEFAULT 1');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS id_estado');
    }
}
