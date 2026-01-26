-- =============================================
-- Migración: Agregar columnas de procesamiento de transcripción
-- =============================================

-- Columnas de transcripción
ALTER TABLE esclarecimiento.e_ind_fvt
ADD COLUMN IF NOT EXISTS transcripcion_completada_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS transcripcion_aprobada_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS transcripcion_aprobada_por INTEGER,
ADD COLUMN IF NOT EXISTS transcripcion_final_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS transcripcion_final_por INTEGER,
ADD COLUMN IF NOT EXISTS entidades_detectadas_at TIMESTAMP,
ADD COLUMN IF NOT EXISTS anonimizacion_final_at TIMESTAMP;

COMMENT ON COLUMN esclarecimiento.e_ind_fvt.transcripcion_completada_at IS 'Fecha de completitud de transcripción automática';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.transcripcion_aprobada_at IS 'Fecha de aprobación de transcripción';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.transcripcion_aprobada_por IS 'Usuario que aprobó la transcripción';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.transcripcion_final_at IS 'Fecha de transcripción final';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.transcripcion_final_por IS 'Usuario que finalizó la transcripción';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.entidades_detectadas_at IS 'Fecha de detección de entidades NER';
COMMENT ON COLUMN esclarecimiento.e_ind_fvt.anonimizacion_final_at IS 'Fecha de anonimización final';

-- Índices para búsquedas
CREATE INDEX IF NOT EXISTS idx_entrevista_transcripcion_completada ON esclarecimiento.e_ind_fvt(transcripcion_completada_at);
CREATE INDEX IF NOT EXISTS idx_entrevista_entidades_detectadas ON esclarecimiento.e_ind_fvt(entidades_detectadas_at);
