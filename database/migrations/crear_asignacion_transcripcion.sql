-- Migración: Sistema de asignación de transcripciones
-- Fecha: 2024-12-23
-- Flujo: asignada → en_edicion → enviada_revision → aprobada/rechazada

-- Tabla de asignaciones de transcripción
CREATE TABLE IF NOT EXISTS esclarecimiento.asignacion_transcripcion (
    id_asignacion SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER NOT NULL REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_transcriptor INTEGER NOT NULL REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_asignado_por INTEGER NOT NULL REFERENCES public.users(id),

    -- Estado: asignada, en_edicion, enviada_revision, aprobada, rechazada
    estado VARCHAR(20) NOT NULL DEFAULT 'asignada',

    -- Fechas del flujo
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio_edicion TIMESTAMP,
    fecha_envio_revision TIMESTAMP,
    fecha_revision TIMESTAMP,

    -- Revisión
    id_revisor INTEGER REFERENCES public.users(id),
    comentario_revision TEXT,

    -- Transcripción
    transcripcion_editada TEXT,

    -- Auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices
CREATE INDEX IF NOT EXISTS idx_asignacion_entrevista ON esclarecimiento.asignacion_transcripcion(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_asignacion_transcriptor ON esclarecimiento.asignacion_transcripcion(id_transcriptor);
CREATE INDEX IF NOT EXISTS idx_asignacion_estado ON esclarecimiento.asignacion_transcripcion(estado);

-- Agregar campo de transcripción final a e_ind_fvt si no existe
ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS transcripcion_final TEXT;

ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS transcripcion_final_at TIMESTAMP;

ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS transcripcion_final_por INTEGER REFERENCES public.users(id);

-- Verificar
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'esclarecimiento'
AND table_name = 'asignacion_transcripcion';
