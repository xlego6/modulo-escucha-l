<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddTranscripcionAnotadaToAsignacionTranscripcion extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_transcripcion
            ADD COLUMN IF NOT EXISTS transcripcion_anotada TEXT
        ");
    }

    public function down()
    {
        DB::statement("ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS transcripcion_anotada");
    }
}
