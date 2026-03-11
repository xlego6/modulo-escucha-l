<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddIdAdjuntoToAsignacionTranscripcion extends Migration
{
    public function up()
    {
        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_transcripcion
            ADD COLUMN IF NOT EXISTS id_adjunto INTEGER REFERENCES esclarecimiento.adjunto(id_adjunto)
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_asignacion_transcripcion_adjunto
            ON esclarecimiento.asignacion_transcripcion(id_adjunto)
        ");
    }

    public function down()
    {
        DB::statement("DROP INDEX IF EXISTS esclarecimiento.idx_asignacion_transcripcion_adjunto");
        DB::statement("ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS id_adjunto");
    }
}
