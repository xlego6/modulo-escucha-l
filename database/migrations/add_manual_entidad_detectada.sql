-- Migracion: Agregar campo manual a entidad_detectada
-- Fecha: 2024-12-29
-- Permite distinguir entidades agregadas manualmente vs detectadas por NER

ALTER TABLE esclarecimiento.entidad_detectada
ADD COLUMN IF NOT EXISTS manual BOOLEAN DEFAULT FALSE;

-- Indice para filtrar por manuales
CREATE INDEX IF NOT EXISTS idx_entidad_manual ON esclarecimiento.entidad_detectada(manual);

-- Comentario
COMMENT ON COLUMN esclarecimiento.entidad_detectada.manual IS 'Si la entidad fue agregada manualmente por el usuario';
