-- Migración: Agregar campo id_dependencia_origen a entrevistador
-- Fecha: 2024-12-23
-- Reemplaza el uso de territorio por dependencia de origen

-- Agregar columna id_dependencia_origen
ALTER TABLE esclarecimiento.entrevistador
ADD COLUMN IF NOT EXISTS id_dependencia_origen INTEGER REFERENCES catalogos.cat_item(id_item);

-- Verificar
SELECT column_name, data_type
FROM information_schema.columns
WHERE table_schema = 'esclarecimiento'
AND table_name = 'entrevistador'
AND column_name = 'id_dependencia_origen';
