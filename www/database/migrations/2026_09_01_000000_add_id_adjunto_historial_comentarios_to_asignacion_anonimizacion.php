<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_anonimizacion
            ADD COLUMN IF NOT EXISTS id_adjunto INTEGER REFERENCES esclarecimiento.adjunto(id_adjunto)
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_asignacion_anonimizacion_adjunto
            ON esclarecimiento.asignacion_anonimizacion(id_adjunto)
        ");

        DB::statement("
            ALTER TABLE esclarecimiento.asignacion_anonimizacion
            ADD COLUMN IF NOT EXISTS historial_comentarios jsonb NOT NULL DEFAULT '[]'::jsonb
        ");
    }

    public function down(): void
    {
        DB::statement("DROP INDEX IF EXISTS esclarecimiento.idx_asignacion_anonimizacion_adjunto");
        DB::statement("ALTER TABLE esclarecimiento.asignacion_anonimizacion DROP COLUMN IF EXISTS id_adjunto");
        DB::statement("ALTER TABLE esclarecimiento.asignacion_anonimizacion DROP COLUMN IF EXISTS historial_comentarios");
    }
};
