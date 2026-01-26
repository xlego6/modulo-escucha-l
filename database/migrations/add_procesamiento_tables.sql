-- Migración: Tablas y campos para procesamiento de testimonios
-- Incluye: entidades detectadas, estado de procesamiento, cola de trabajos

-- =============================================
-- CAMPOS DE ESTADO EN ENTREVISTAS
-- =============================================

-- Agregar campos de estado de procesamiento a e_ind_fvt
ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS transcripcion_completada_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS entidades_detectadas_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS anonimizacion_completada_at TIMESTAMP;

-- Índices para búsquedas de estado
CREATE INDEX IF NOT EXISTS idx_entrevista_transcripcion ON esclarecimiento.e_ind_fvt(transcripcion_completada_at);
CREATE INDEX IF NOT EXISTS idx_entrevista_entidades ON esclarecimiento.e_ind_fvt(entidades_detectadas_at);
CREATE INDEX IF NOT EXISTS idx_entrevista_anonimizacion ON esclarecimiento.e_ind_fvt(anonimizacion_completada_at);

-- =============================================
-- TABLA DE ENTIDADES DETECTADAS
-- =============================================

CREATE TABLE IF NOT EXISTS esclarecimiento.entidad_detectada (
    id_entidad SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER NOT NULL REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    tipo VARCHAR(20) NOT NULL,           -- PER, LOC, ORG, DATE, EVENT, etc.
    texto VARCHAR(500) NOT NULL,         -- Texto original de la entidad
    texto_anonimizado VARCHAR(100),      -- Texto reemplazo (ej: [PER_1])
    posicion_inicio INTEGER,             -- Posición en el texto
    posicion_fin INTEGER,
    confianza DECIMAL(5,4),              -- Score de confianza del modelo
    verificado BOOLEAN DEFAULT FALSE,    -- Si fue verificado por humano
    excluir_anonimizacion BOOLEAN DEFAULT FALSE, -- No anonimizar esta entidad
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para entidades
CREATE INDEX IF NOT EXISTS idx_entidad_entrevista ON esclarecimiento.entidad_detectada(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_entidad_tipo ON esclarecimiento.entidad_detectada(tipo);
CREATE INDEX IF NOT EXISTS idx_entidad_texto ON esclarecimiento.entidad_detectada(texto);

-- Comentarios
COMMENT ON TABLE esclarecimiento.entidad_detectada IS 'Entidades nombradas detectadas por NER en testimonios';
COMMENT ON COLUMN esclarecimiento.entidad_detectada.tipo IS 'Tipo de entidad: PER(persona), LOC(lugar), ORG(organización), DATE(fecha), EVENT(evento)';

-- =============================================
-- TABLA DE TRABAJOS DE PROCESAMIENTO
-- =============================================

CREATE TABLE IF NOT EXISTS esclarecimiento.trabajo_procesamiento (
    id_trabajo SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    tipo VARCHAR(50) NOT NULL,           -- transcripcion, deteccion_entidades, anonimizacion
    estado VARCHAR(20) DEFAULT 'pendiente', -- pendiente, procesando, completado, fallido
    progreso INTEGER DEFAULT 0,          -- Porcentaje 0-100
    mensaje TEXT,                        -- Mensaje de estado o error
    parametros JSONB,                    -- Parámetros del trabajo
    resultado JSONB,                     -- Resultado del procesamiento
    intentos INTEGER DEFAULT 0,          -- Número de intentos
    max_intentos INTEGER DEFAULT 3,
    prioridad INTEGER DEFAULT 5,         -- 1-10, menor = mayor prioridad
    id_usuario INTEGER,                  -- Usuario que inició el trabajo
    iniciado_at TIMESTAMP,
    completado_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para trabajos
CREATE INDEX IF NOT EXISTS idx_trabajo_entrevista ON esclarecimiento.trabajo_procesamiento(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_trabajo_tipo ON esclarecimiento.trabajo_procesamiento(tipo);
CREATE INDEX IF NOT EXISTS idx_trabajo_estado ON esclarecimiento.trabajo_procesamiento(estado);
CREATE INDEX IF NOT EXISTS idx_trabajo_prioridad ON esclarecimiento.trabajo_procesamiento(prioridad, created_at);

-- Comentarios
COMMENT ON TABLE esclarecimiento.trabajo_procesamiento IS 'Cola de trabajos de procesamiento (transcripción, NER, anonimización)';

-- =============================================
-- TABLA DE SEGMENTOS DE TRANSCRIPCIÓN
-- =============================================

CREATE TABLE IF NOT EXISTS esclarecimiento.transcripcion_segmento (
    id_segmento SERIAL PRIMARY KEY,
    id_e_ind_fvt INTEGER NOT NULL REFERENCES esclarecimiento.e_ind_fvt(id_e_ind_fvt) ON DELETE CASCADE,
    tiempo_inicio DECIMAL(10,3),         -- Tiempo inicio en segundos
    tiempo_fin DECIMAL(10,3),            -- Tiempo fin en segundos
    texto TEXT NOT NULL,                 -- Texto del segmento
    hablante VARCHAR(50),                -- Identificador del hablante (SPEAKER_00, etc.)
    confianza DECIMAL(5,4),              -- Score de confianza
    editado BOOLEAN DEFAULT FALSE,       -- Si fue editado manualmente
    orden INTEGER,                       -- Orden del segmento
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Índices para segmentos
CREATE INDEX IF NOT EXISTS idx_segmento_entrevista ON esclarecimiento.transcripcion_segmento(id_e_ind_fvt);
CREATE INDEX IF NOT EXISTS idx_segmento_tiempo ON esclarecimiento.transcripcion_segmento(tiempo_inicio);
CREATE INDEX IF NOT EXISTS idx_segmento_hablante ON esclarecimiento.transcripcion_segmento(hablante);

-- Comentarios
COMMENT ON TABLE esclarecimiento.transcripcion_segmento IS 'Segmentos de transcripción con timestamps y hablantes';

-- =============================================
-- VISTA PARA ESTADÍSTICAS DE PROCESAMIENTO
-- =============================================

CREATE OR REPLACE VIEW esclarecimiento.v_estadisticas_procesamiento AS
SELECT
    COUNT(*) as total_entrevistas,
    COUNT(*) FILTER (WHERE EXISTS (
        SELECT 1 FROM esclarecimiento.adjunto a
        WHERE a.id_e_ind_fvt = e.id_e_ind_fvt
        AND a.tipo_mime LIKE '%audio%' OR a.tipo_mime LIKE '%video%'
    )) as con_audio,
    COUNT(*) FILTER (WHERE transcripcion_completada_at IS NOT NULL) as transcritas,
    COUNT(*) FILTER (WHERE entidades_detectadas_at IS NOT NULL) as con_entidades,
    COUNT(*) FILTER (WHERE anonimizacion_completada_at IS NOT NULL) as anonimizadas,
    COUNT(*) FILTER (WHERE transcripcion_completada_at IS NULL AND EXISTS (
        SELECT 1 FROM esclarecimiento.adjunto a
        WHERE a.id_e_ind_fvt = e.id_e_ind_fvt
        AND (a.tipo_mime LIKE '%audio%' OR a.tipo_mime LIKE '%video%')
    )) as pendientes_transcripcion
FROM esclarecimiento.e_ind_fvt e
WHERE e.id_activo = 1;
