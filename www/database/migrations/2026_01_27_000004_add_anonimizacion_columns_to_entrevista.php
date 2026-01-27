<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddAnonimizacionColumnsToEntrevista extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt ADD COLUMN IF NOT EXISTS anonimizacion_completada_at TIMESTAMP');
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt ADD COLUMN IF NOT EXISTS anonimizacion_final TEXT');
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt ADD COLUMN IF NOT EXISTS anonimizacion_final_por INTEGER');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt DROP COLUMN IF EXISTS anonimizacion_completada_at');
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt DROP COLUMN IF EXISTS anonimizacion_final');
        DB::statement('ALTER TABLE esclarecimiento.e_ind_fvt DROP COLUMN IF EXISTS anonimizacion_final_por');
    }
}
