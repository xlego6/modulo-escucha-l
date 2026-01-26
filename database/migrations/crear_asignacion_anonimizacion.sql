-- Migracion: Sistema de asignacion de anonimizacion
-- Fecha: 2024-12-23
-- Flujo: asignada -> en_edicion -> enviada_revision -> aprobada/rechazada

-- Tabla de asignaciones de anonimizacion
CREATE TABLE IF NOT EXISTS esclarecimiento.asignacion_anonimizacion (
    id_asignacion SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER NOT NULL REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt),
    id_anonimizador INTEGER NOT NULL REFERENCES esclarecimiento.entrevistador(id_entrevistador),
    id_asignado_por INTEGER NOT NULL REFERENCES public.users(id),

    -- Estado: asignada, en_edicion, enviada_revision, aprobada, rechazada
    estado VARCHAR(20) NOT NULL DEFAULT 'asignada',

    -- Fechas del flujo
    fecha_asignacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_inicio_edicion TIMESTAMP,
    fecha_envio_revision TIMESTAMP,
    fecha_revision TIMESTAMP,

    -- Revision
    id_revisor INTEGER REFERENCES public.users(id),
    comentario_revision TEXT,

    -- Configuracion de anonimizacion
    tipos_anonimizar VARCHAR(100) DEFAULT 'PER,LOC', -- Tipos de entidades a anonimizar
    formato_reemplazo VARCHAR(20) DEFAULT 'brackets', -- brackets, numbered, redacted, asterisks

    -- Texto anonimizado editado
    texto_anonimizado TEXT,

    -- Auditoria
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Indices
CREATE INDEX IF NOT EXISTS idx_asig_anon_entrevista ON esclarecimiento.asignacion_anonimizacion(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_asig_anon_anonimizador ON esclarecimiento.asignacion_anonimizacion(id_anonimizador);
CREATE INDEX IF NOT EXISTS idx_asig_anon_estado ON esclarecimiento.asignacion_anonimizacion(estado);

-- Agregar campo de anonimizacion final a e_ind_fvt si no existe
ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS anonimizacion_final TEXT;

ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS anonimizacion_final_at TIMESTAMP;

ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS anonimizacion_final_por INTEGER REFERENCES public.users(id);

-- Verificar
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'esclarecimiento'
AND table_name = 'asignacion_anonimizacion';
