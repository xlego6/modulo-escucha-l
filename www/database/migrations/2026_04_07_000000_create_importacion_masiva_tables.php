<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateImportacionMasivaTables extends Migration
{
    public function up()
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.importaciones_masivas (
                id_importacion      SERIAL PRIMARY KEY,
                id_usuario          INTEGER NOT NULL,
                nombre_archivo      VARCHAR(500),
                ruta_csv            VARCHAR(1000),
                estado              VARCHAR(50) DEFAULT 'pendiente',
                total_expedientes   INTEGER DEFAULT 0,
                procesados          INTEGER DEFAULT 0,
                con_error           INTEGER DEFAULT 0,
                configuracion       JSONB DEFAULT '{}',
                created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_importaciones_masivas_usuario
            ON esclarecimiento.importaciones_masivas(id_usuario)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_importaciones_masivas_estado
            ON esclarecimiento.importaciones_masivas(estado)");

        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.importacion_expedientes (
                id_imp_expediente   SERIAL PRIMARY KEY,
                id_importacion      INTEGER NOT NULL
                    REFERENCES esclarecimiento.importaciones_masivas(id_importacion) ON DELETE CASCADE,
                id_csv              VARCHAR(100),
                id_e_ind_fvt        INTEGER
                    REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE SET NULL,
                estado              VARCHAR(50) DEFAULT 'pendiente',
                datos_csv           JSONB DEFAULT '{}',
                filas_originales    JSONB DEFAULT '[]',
                archivos            JSONB DEFAULT '[]',
                advertencias        JSONB DEFAULT '[]',
                error_mensaje       TEXT,
                created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_importacion_expedientes_importacion
            ON esclarecimiento.importacion_expedientes(id_importacion)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_importacion_expedientes_estado
            ON esclarecimiento.importacion_expedientes(estado)");
    }

    public function down()
    {
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.importacion_expedientes");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.importaciones_masivas");
    }
}
