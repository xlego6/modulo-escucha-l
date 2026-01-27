<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddFechaColumnsToPermisoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS fecha_desde DATE NULL');
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS fecha_hasta DATE NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS fecha_desde');
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS fecha_hasta');
    }
}
