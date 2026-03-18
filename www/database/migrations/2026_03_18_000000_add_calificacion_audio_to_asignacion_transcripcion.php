<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCalificacionAudioToAsignacionTranscripcion extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_transcripcion
            ADD COLUMN IF NOT EXISTS calificacion_audio SMALLINT CHECK (calificacion_audio BETWEEN 1 AND 5),
            ADD COLUMN IF NOT EXISTS observaciones_envio TEXT
        ");
    }

    public function down()
    {
        DB::statement("ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS calificacion_audio");
        DB::statement("ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS observaciones_envio");
    }
}
