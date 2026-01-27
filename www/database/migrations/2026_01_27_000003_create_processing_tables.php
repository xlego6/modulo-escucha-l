<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateProcessingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Tabla de asignaciones de transcripción
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.asignacion_transcripcion (
                id_asignacion SERIAL PRIMARY KEY,
                id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
                id_transcriptor INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
                id_asignado_por INTEGER,
                estado VARCHAR(50),
                fecha_asignacion TIMESTAMP,
                fecha_inicio_edicion TIMESTAMP,
                fecha_envio_revision TIMESTAMP,
                fecha_revision TIMESTAMP,
                id_revisor INTEGER,
                comentario_revision TEXT,
                transcripcion_editada TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_asignacion_transcripcion_estado ON esclarecimiento.asignacion_transcripcion(estado)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_asignacion_transcripcion_transcriptor ON esclarecimiento.asignacion_transcripcion(id_transcriptor)");

        // Tabla de asignaciones de anonimización
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.asignacion_anonimizacion (
                id_asignacion SERIAL PRIMARY KEY,
                id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
                id_anonimizador INTEGER REFERENCES esclarecimiento.entrevistador(id_entrevistador),
                id_asignado_por INTEGER,
                estado VARCHAR(50),
                fecha_asignacion TIMESTAMP,
                fecha_inicio_edicion TIMESTAMP,
                fecha_envio_revision TIMESTAMP,
                fecha_revision TIMESTAMP,
                id_revisor INTEGER,
                comentario_revision TEXT,
                tipos_anonimizar VARCHAR(100),
                formato_reemplazo VARCHAR(50),
                texto_anonimizado TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_asignacion_anonimizacion_estado ON esclarecimiento.asignacion_anonimizacion(estado)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_asignacion_anonimizacion_anonimizador ON esclarecimiento.asignacion_anonimizacion(id_anonimizador)");

        // Tabla de cola de trabajos de procesamiento
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.trabajo_procesamiento (
                id_trabajo SERIAL PRIMARY KEY,
                id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
                tipo VARCHAR(50),
                estado VARCHAR(50),
                progreso INTEGER DEFAULT 0,
                parametros JSONB,
                id_usuario INTEGER,
                mensaje TEXT,
                resultado JSONB,
                iniciado_at TIMESTAMP,
                completado_at TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_trabajo_procesamiento_estado ON esclarecimiento.trabajo_procesamiento(estado)");

        // Tabla de entidades detectadas (NER)
        DB::statement("
            CREATE TABLE IF NOT EXISTS esclarecimiento.entidad_detectada (
                id_entidad SERIAL PRIMARY KEY,
                id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
                tipo VARCHAR(50),
                texto TEXT,
                texto_anonimizado VARCHAR(100),
                posicion_inicio INTEGER,
                posicion_fin INTEGER,
                confianza DECIMAL(5,4),
                verificado BOOLEAN DEFAULT FALSE,
                excluir_anonimizacion BOOLEAN DEFAULT FALSE,
                manual BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement("CREATE INDEX IF NOT EXISTS idx_entidad_detectada_entrevista ON esclarecimiento.entidad_detectada(id_e_ind_fvt)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_entidad_detectada_tipo ON esclarecimiento.entidad_detectada(tipo)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.entidad_detectada");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.trabajo_procesamiento");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.asignacion_anonimizacion");
        DB::statement("DROP TABLE IF EXISTS esclarecimiento.asignacion_transcripcion");
    }
}
