-- Migración para agregar columnas de texto extraído a la tabla adjunto
-- Estas columnas permiten almacenar el texto extraído de documentos para búsqueda

ALTER TABLE esclarecimiento.adjunto
ADD COLUMN IF NOT EXISTS texto_extraido TEXT;

ALTER TABLE esclarecimiento.adjunto
ADD COLUMN IF NOT EXISTS texto_extraido_at TIMESTAMP;

-- Índice para mejorar búsquedas en texto extraído
CREATE INDEX IF NOT EXISTS idx_adjunto_texto_extraido
ON esclarecimiento.adjunto USING gin(to_tsvector('spanish', coalesce(texto_extraido, '')));

COMMENT ON COLUMN esclarecimiento.adjunto.texto_extraido IS 'Texto extraído del documento adjunto para búsqueda';
COMMENT ON COLUMN esclarecimiento.adjunto.texto_extraido_at IS 'Fecha y hora de extracción del texto';
