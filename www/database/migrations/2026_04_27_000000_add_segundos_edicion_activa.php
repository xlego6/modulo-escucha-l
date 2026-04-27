<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_transcripcion
            ADD COLUMN IF NOT EXISTS segundos_edicion_activa INTEGER NOT NULL DEFAULT 0
        ");

        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_anonimizacion
            ADD COLUMN IF NOT EXISTS segundos_edicion_activa INTEGER NOT NULL DEFAULT 0
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE esclarecimiento.asignacion_transcripcion DROP COLUMN IF EXISTS segundos_edicion_activa");
        DB::statement("ALTER TABLE esclarecimiento.asignacion_anonimizacion DROP COLUMN IF EXISTS segundos_edicion_activa");
    }
};
