<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS public.traza_actividad (
                id_traza_actividad BIGSERIAL PRIMARY KEY,
                fecha_hora         TIMESTAMP(6) DEFAULT CURRENT_TIMESTAMP,
                id_usuario         INTEGER REFERENCES public.users(id),
                accion             VARCHAR(100),
                objeto             VARCHAR(100),
                id_registro        INTEGER,
                referencia         VARCHAR(500),
                codigo             VARCHAR(100),
                ip                 VARCHAR(45),
                id_personificador  INTEGER REFERENCES public.users(id)
            )
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS public.traza_actividad');
    }
};
