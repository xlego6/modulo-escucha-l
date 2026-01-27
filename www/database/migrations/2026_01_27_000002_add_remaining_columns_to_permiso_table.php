<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRemainingColumnsToPermisoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS id_adjunto INTEGER NULL');
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS id_revocado_por INTEGER NULL');
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS fecha_revocado TIMESTAMP NULL');
        DB::statement('ALTER TABLE esclarecimiento.permiso ADD COLUMN IF NOT EXISTS codigo_entrevista VARCHAR(100) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS id_adjunto');
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS id_revocado_por');
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS fecha_revocado');
        DB::statement('ALTER TABLE esclarecimiento.permiso DROP COLUMN IF EXISTS codigo_entrevista');
    }
}
