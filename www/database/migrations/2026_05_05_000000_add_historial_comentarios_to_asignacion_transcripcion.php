<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddHistorialComentariosToAsignacionTranscripcion extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE esclarecimiento.asignacion_transcripcion ADD COLUMN IF NOT EXISTS historial_comentarios jsonb NOT NULL DEFAULT \'[]\'::jsonb');
    }

    public function down()
    {
        DB::statement('ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS historial_comentarios');
    }
}
