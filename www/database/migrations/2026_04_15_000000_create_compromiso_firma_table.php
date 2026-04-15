<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.compromiso_firma (
                id SERIAL PRIMARY KEY,
                id_entrevistador INTEGER NOT NULL
                    REFERENCES esclarecimiento.entrevistador(id_entrevistador) ON DELETE CASCADE,
                tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('acceso', 'reserva')),
                version_texto VARCHAR(50) NOT NULL,
                fecha_firma TIMESTAMP NOT NULL,
                texto_firmado TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT NOW(),
                updated_at TIMESTAMP DEFAULT NOW()
            )
        ");

        DB::statement("
            COMMENT ON TABLE esclarecimiento.compromiso_firma IS
            'Historial de compromisos firmados por cada usuario. Almacena el texto exacto que fue aceptado en cada firma.'
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_compromiso_firma_entrevistador
            ON esclarecimiento.compromiso_firma(id_entrevistador)
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS idx_compromiso_firma_tipo
            ON esclarecimiento.compromiso_firma(tipo, fecha_firma)
        ");
    }

    public function down()
    {
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.compromiso_firma");
    }
};
