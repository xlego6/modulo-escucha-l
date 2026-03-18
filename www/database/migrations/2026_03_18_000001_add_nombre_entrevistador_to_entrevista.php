<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddNombreEntrevistadorToEntrevista extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE esclarecimiento.e_ind_fvt
            ADD COLUMN IF NOT EXISTS nombre_entrevistador TEXT
        ");
    }

    public function down()
    {
        DB::statement("ALTER TABLE esclarecimiento.e_ind_fvt DROP COLUMN IF EXISTS nombre_entrevistador");
    }
}
